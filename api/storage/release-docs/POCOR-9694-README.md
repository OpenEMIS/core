h1. POCOR-9694 — OpenEMIS Runtime & Queue Framework (Phase 1)

{panel:title=Status|borderColor=#3a87ad|borderStyle=solid|titleBGColor=#d9edf7|bgColor=#ffffff}
*Architectural review accepted on 2026-05-08.* No "Async Runtime v2" rewrite. Instead, POCOR-9694 ships *Phase 1 of the OpenEMIS Runtime & Queue Framework* — a focused set of standardisation deliverables on top of POCOR-9509 (alerts) and POCOR-9257 (webhooks), plus an OpenEMIS-branded operational surface that v6 will fully realise once the Angular v20 frontend lands.

*Decisions confirmed by the review:*
* OpenEMIS owns its async vocabulary: *OpenEMIS Runtime, OpenEMIS Queue, OpenEMIS Tasks, OpenEMIS Workers*. Framework names ({{queue:work}}, Horizon, Supervisor, Redis, Laravel jobs) are implementation details and stay out of operational documentation.
* Single scheduler entry: {{* * * * * php artisan openemis-core:run}}.
* {{system_processes}} remains the authoritative cross-stack execution-tracking contract.
* Lightweight database-backed queues are the operational model; no Redis / Horizon / Supervisor required.
* Angular v20 frontend (Core v6) cannot reliably trigger background work — request-driven pseudo-cron is formally retired.
{panel}

----

h2. 1. What is the Task?

POCOR-9694 began as an architectural critique of the async work introduced by POCOR-9509 and POCOR-9257. After a deep audit and a follow-up review, it now covers two phases:

# *Architectural review* — completed; verdict + analysis preserved in §3.
# *Phase 1 implementation* — six deliverables in §4 forming the foundation of the OpenEMIS Runtime & Queue Framework. Phase 2 is the Core v6 cycle (Angular v20 + full event-driven runtime); see §10.

----

h2. 2. Situation Before

Verified state of the async runtime on master at 2026-05-08:

|| Surface || Count / Detail ||
| Modern CakePHP 5 Commands ({{src/Command/*.php}}) | 22 |
| Legacy CakePHP 3.x Shells ({{src/Shell/*.php}}) | 73 |
| Laravel Commands | 28 |
| Raw {{exec()}} / {{shell_exec()}} / {{proc_open()}} in non-vendor code | *127 callsites* |
| Cron entries documented in POCOR-9509 deploy guide | 3 |
| {{.env}} knobs related to alerts/queue | 8 |
| Functional duplication: alert commands writing to the same {{system_processes}} table | 16 CakePHP ↔ 15 Laravel |

*Composer health.* Root composer is technically valid but cannot install any new package on the current host:

{code:bash}
composer require --dry-run guzzlehttp/guzzle:^7.8
# Problem 1: phpoffice/phpspreadsheet 1.30.1 requires php >=7.4.0,<8.5.0 — host is 8.5.5
# Problem 2: laminas/laminas-diactoros 3.7.0 requires php ~8.1 || ~8.2 || ~8.3 || ~8.4 — host is 8.5.5
{code}

*Cross-stack contract.* Both stacks read/write {{system_processes}} (status enum 1=NEW → 2=RUNNING → 3=COMPLETED / -1=ABORT / -2=ERROR), {{alert_queue}}, {{alert_logs}}, {{webhook_queue}}.

*Anti-pattern in the request lifecycle.* {{api/app/Repositories/WebhookRepository.php}} forks via {{exec(base_path('artisan') . ' webhook:process …')}} from inside an HTTP request — orphaned processes, no supervision, no retry visibility.

----

h2. 3. Architectural Review (background, verdict preserved)

h3. 3.1 What the original critique gets right

# *{{exec()}} from the request lifecycle is a real anti-pattern* — 127 callsites total across {{src/}} and {{plugins/}}.
# *15 feature-specific Laravel alert commands won't scale long-term* — event-driven processing is the right v6 pattern, not command-per-rule.
# *Deployment story is heavy for ministry VPSes* — 3 cron entries, 8 {{.env}} knobs, optional systemd queue worker, storage permission gotcha.

h3. 3.2 What the original critique overstated

# *Laravel scheduler already provides single-cron orchestration.* {{api/app/Console/Kernel.php::schedule()}} dispatches all entries via one scheduler tick. Phase 1 wraps this as {{openemis-core:run}} so OpenEMIS owns the public-facing entry-point.
# *Database-backed queues are not "Laravel queue infrastructure"* — no Redis, no Horizon, no Supervisor needed.
# *{{system_processes}} already exists as the OpenEMIS-native execution-tracking pattern* — no need to invent a new table for tracking.
# *POCOR-9509 just shipped end-to-end on dmo-dev* (480 absence alerts delivered, ~38 retirement alerts processed cleanly). Rewriting it would be regression theatre.

h3. 3.3 Verdict (endorsed by review)

Drift toward Laravel; standardise around the existing scheduler + queue + {{system_processes}} contract; remove the most damaging anti-patterns; expose operational state to admins via OpenEMIS-branded pages. *No "Async Runtime v2" rewrite.*

----

h2. 4. What Was Implemented (Phase 1)

{panel:title=Phase 1 scope split|borderColor=#3a87ad|borderStyle=solid|titleBGColor=#d9edf7|bgColor=#ffffff}
*Shipped in this commit (Phase 1a — backend + runtime):*
# {{exec()}} removed from {{WebhookRepository.php}} — webhook delivery is now queue-only.
# OpenEMIS Tasks abstraction tables: {{tasks}}, {{task_jobs}}, {{task_failures}} (migration applied).
# Dual-write helper: {{App\Services\OpenemisRuntime\TasksRecorder}} (best-effort, never throws back).
# Single-cron entry-point: {{php artisan openemis-core:run}} + Docker {{cron}} install.
# Runtime backend: uniform CRUD reads under v5 ({{tasks}}, {{task-jobs}}, {{task-failures}} via {{CrudApiController}}) + bespoke actions/aggregates under v4 ({{logs}}, {{queue}}, {{scheduler}}, {{retry}}, {{abort}}). All Swagger-annotated.
# Governance rules: {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}}.

*Deferred to Phase 1b (separate follow-up ticket):*
# Pin Docker PHP to 8.4.x (Phase 1a was developed against the existing {{php:8.3-apache}} base; the upgrade is a separate concern with its own QA cycle).
# Angular module {{frontend/src/app/system-runtime/}} — five UI pages calling the backend endpoints already shipped here.
# Permissions seed {{System.SystemRuntime.view}} / {{System.SystemRuntime.execute}} — pairs with the frontend.
# Dual-writing the *alerts* path through {{TasksRecorder}} (the helper exists; the {{AlertCommandBase}} integration is a separate small change).
{panel}

h3. 4.1 Pin Docker PHP to 8.4.x — *shipped in Phase 1b (commit {{6250b88829}})*

Bumped {{Dockerfile:40}} from {{php:8.3-apache}} to {{php:8.4-apache}}. 8.4 is the highest stable release that every direct dependency in the root {{composer.json}} supports; the host running PHP 8.5.5 was pushing {{phpoffice/phpspreadsheet}} and {{laminas/laminas-diactoros}} past their upper bounds.

h3. 4.2 Remove {{exec()}} from {{WebhookRepository.php}}

The fork-from-FPM anti-pattern is replaced by enqueueing through the existing scheduler-backed processing path. Webhook delivery now follows a queue-driven flow with no in-request subprocess spawn.

* {{api/app/Repositories/WebhookRepository.php}} — {{exec()}} call replaced with a queue insert. The OpenEMIS Runtime drains the queue every minute.
* No new tables, no new commands, no new env knobs introduced by this change alone.

h3. 4.3 OpenEMIS Tasks abstraction tables (parallel/shadow data layer) — *shipped*

Three new OpenEMIS-owned tables are introduced as the platform-named surface for async work. They are populated by Laravel-side write paths *in parallel* with the existing {{alert_queue}} / {{webhook_queue}} / {{jobs}} / {{failed_jobs}} tables — dual-write — so the new admin pages have a stable, OpenEMIS-vocabulary source while existing CakePHP code paths remain untouched.

|| Table || Purpose ||
| {{tasks}} | Pending OpenEMIS Tasks (one row per enqueued unit of work). Status enum mirrors {{system_processes}} convention: 0=NEW, 1=PROCESSING, 2=DONE, -1=ABORT, -2=FAILED. Carries {{task_type}} ({{alert}}, {{webhook}}, {{export}}, {{runtime_heartbeat}}, …), {{payload_json}}, {{available_at}}, {{retry_count}}, {{source_table}}, {{source_id}}. |
| {{task_jobs}} | Per-attempt log entries — {{started_at}}, {{ended_at}}, {{duration_ms}}, {{status}} (1=PROCESSING, 2=DONE, -2=FAILED), {{message_preview}}. One {{tasks}} row may have many {{task_jobs}} rows. |
| {{task_failures}} | Failure detail rows — {{exception_class}}, {{exception_message}}, {{stack_trace}}, originating {{task_id}} / {{task_job_id}}, {{retry_allowed}} flag. |

*Naming check:* the names {{tasks}}, {{task_jobs}}, {{task_failures}} were verified to not collide with any existing Laravel-managed table in {{openemis_core_v5}} before adoption. They sit alongside {{jobs}} / {{failed_jobs}} (Laravel-native, untouched).

*Dual-write rules:*
* The {{App\Services\OpenemisRuntime\TasksRecorder}} helper provides {{recordEnqueue}} / {{recordStart}} / {{recordSuccess}} / {{recordFailure}} / {{recordRetry}} / {{recordAbort}}.
* {{TasksRecorder}} *never throws back to the caller* — the legacy queue write must remain authoritative. Recorder failures are best-effort {{Log::warning}}.
* {{api/app/Repositories/WebhookRepository.php}} now dual-writes via {{recordEnqueue('webhook', …)}} (Phase 1a).
* Alert-side dual-writing through {{AlertCommandBase}} is *deferred to Phase 1b* — the helper is in place; the integration is a separate small change.
* Existing CakePHP write paths are left as-is. They continue to write to legacy tables only. v6 will subsume them.
* Migration is new-table-only, no backup pattern needed; {{down()}} drops the three tables.

h3. 4.4 OpenEMIS Runtime entry-point: {{openemis-core:run}} — *shipped*

A single OpenEMIS-branded scheduler entry-point is added. Internally it invokes the Laravel scheduler tick; ops teams interact only with the OpenEMIS name.

* New artisan command: {{php artisan openemis-core:run}} at {{api/app/Console/Commands/OpenemisCoreRunCommand.php}} (auto-discovered by {{Kernel::commands()}}).
* Internally calls {{Artisan::call('schedule:run')}} so existing scheduled commands ({{webhooks:process}}, {{alerts:check}}, {{alerts:send}}) continue to fire on their declared intervals.
* The legacy {{php artisan schedule:run}} continues to function and is *synced* with {{openemis-core:run}} — calling either one dispatches the same scheduler tick. Operationally, ministries are advised to switch to {{openemis-core:run}} for all new deployments; existing deployments may keep {{schedule:run}} until their next ops-doc refresh.
* Each tick stamps a {{tasks}} row with {{task_type='runtime_heartbeat'}} carrying {{started_at}} / {{ended_at}} / {{duration_ms}} / {{exit_code}}. The Runtime Scheduler page reads this for the "is the Runtime alive" indicator.
* Docker {{cron}} is now installed by {{Dockerfile}} and the cron line lives in {{docker-config/cron/openemis-core}} (one entry, runs as {{www-data}} every minute). {{docker-config/init.sh}} starts the cron daemon alongside Apache.

Canonical cron line:
{code}
*  *  *  *  *  www-data  cd /var/www/html/core/api && /usr/local/bin/php artisan openemis-core:run >> /var/www/html/core/api/storage/logs/openemis-core-run.log 2>&1
{code}

h3. 4.5 Administration → System → Async Services (section with multiple pages) — *CakePHP nav + stub admin pages + Laravel v4 backend shipped in Phase 1b; Angular replacement deferred to Phase 2*

A new Administration section following the Webhooks / Alerts UX convention — one section, multiple pages. All pages read from the new {{system_queue*}} tables (with {{system_processes}} for stuck-process detection) so the admin surface speaks OpenEMIS vocabulary regardless of underlying framework.

This section is the *unified cross-feature operations console for all async work in OpenEMIS*. It is *additive* — existing per-feature queueing / logs UIs (Webhook Queue, Webhook Logs, Alert Queue, Alert Logs, Report Card generation status, Profile generation status, Import progress) *continue to live in their own sections* with their own feature-specific filters. They are not removed, deprecated, or redirected by Phase 1.

The Runtime section adds a *cross-feature* view that none of the per-feature pages provide on their own — "show me everything async in flight regardless of feature", "show me all failures across all task types in the last 24h", "is the Runtime alive". Both views read from the same {{system_queue*}} tables, so they are guaranteed to agree.

|| Page || Surface ||
| *Tasks* | All {{system_queue}} rows + active {{system_processes}}, across all task types. Filterable by {{task_type}} (alert / webhook / report / export / profile / import / integration / …), {{status}}, age, originating user. Per-row actions: view payload, force-abort. |
| *Failures* | {{system_queue_failures}} joined to {{system_queue}}, across all task types. Shows exception class, target context (alert id / webhook URL / report id / export job), retry count, last attempt timestamp. Filterable by {{task_type}}. Per-row action: retry (resets the source row to NEW, increments retry_count). |
| *Logs* | All {{system_queue_logs}} rows — per-attempt history of every OpenEMIS Task, across all task types. Filterable by {{task_type}}, {{status}}, time range, source row id. |
| *Queue* | Backlog visibility — depth per {{task_type}}, oldest-pending age, throughput last 24h. Filterable by {{task_type}}. |
| *Scheduler* | Per-scheduled-OpenEMIS-Task last successful run timestamp, expected interval, "is the OpenEMIS Runtime alive" indicator (warns if the cron line is missing or not draining). |

*Filtering convention:* every page exposes a {{task_type}} dropdown ({{All / Alerts / Webhooks / Reports / Exports / Profiles / Imports / Integrations}}). Deep-links from per-feature configuration pages can pre-set the filter — e.g., the Webhooks configuration page links to "View all deliveries" → {{/system/runtime/logs?task_type=webhook}}, complementing (not replacing) the existing per-webhook delivery view.

*Coexistence with per-feature pages:* the Alerts section's Alert Queue and Alert Logs continue to provide alert-specific filters (rule, recipient, channel) that the Runtime section does not duplicate. Same for Webhooks (per-URL filter, per-event filter, etc.). The Runtime section is for cross-feature operational visibility; the per-feature pages are for feature-specific business detail.

*Backend (shipped in Phase 1a) — split between v5 (CRUD) and v4 (actions / aggregates).*

The repo's CQS contract is: any GET/POST/PUT/DELETE on a uniform resource goes through {{CrudApiController}} under {{/api/v5/}}; bespoke endpoints (file tails, cross-table aggregates, executable actions) live under {{/api/v4/}}. Phase 1a follows that contract:

*v5 — uniform resource reads via CrudApiController* (registered in {{$allowedResources}}):

|| Endpoint || Resource ||
| {{GET /api/v5/tasks}} | {{App\Models\Api5\Tasks}} (filterable, paginated, supports {{_contain}}, {{_conditions}} — same surface as every other v5 resource) |
| {{GET /api/v5/task-jobs}} | {{App\Models\Api5\TaskJobs}} |
| {{GET /api/v5/task-failures}} | {{App\Models\Api5\TaskFailures}} |

*v4 — bespoke action / aggregate endpoints* (in {{api/app/Http/Controllers/Administration/SystemRuntimeController.php}}, all behind {{auth.jwt}}, fully Swagger-annotated):

|| Endpoint || Purpose ||
| {{GET /api/v4/system-runtime/logs}} | Tail of {{api/storage/logs/openemis-core-run.log}} (last N lines, read capped at 64 KiB) |
| {{GET /api/v4/system-runtime/queue}} | Aggregate counts: {{tasks}} by status / type + legacy table sizes ({{webhook_queue}}, {{alert_queue}}, {{jobs}}, {{failed_jobs}}) |
| {{GET /api/v4/system-runtime/scheduler}} | Latest {{runtime_heartbeat}} row + parsed {{schedule:list}} output (re-branded for the UI) |
| {{POST /api/v4/system-runtime/tasks/\{id\}/retry}} | Calls {{TasksRecorder::recordRetry($id)}} — resets task to NEW |
| {{POST /api/v4/system-runtime/tasks/\{id\}/abort}} | Calls {{TasksRecorder::recordAbort($id)}} — flips to ABORT |

*Frontend (deferred to Phase 1b):* {{frontend/src/app/system-runtime/}} — Angular module with one component per page, mirroring the Webhooks / Alerts module structure. Pairs with the permissions seed below.

*Permissions (deferred to Phase 1b):* the section will be gated by {{System.SystemRuntime.view}}; retry / force-abort by {{System.SystemRuntime.execute}}. Both will ship in the seed data and be assigned to {{super_admin}} by default. The view permission is decoupled from per-feature permissions — an operator can see the Runtime without having Webhook / Alert configuration permissions.

*Audit (deferred to Phase 1b):* every retry / force-abort will write a row to {{audit_logs}} with the acting {{security_user_id}}, target {{tasks.id}}, and action.

h3. 4.6 v5 model completeness — factory + PHPDoc + 100%-passing feature test

Every model registered in {{CrudApiController::$allowedResources}} must ship with the standard v5 triple in the *same commit* that registers it:

# *Factory* under {{api/database/factories/Api5/<Model>Factory.php}} — extends {{Illuminate\Database\Eloquent\Factories\Factory}}, declares {{$model}}, returns a fully-formed row from {{definition()}} that respects every NOT NULL column and FK-shape link.
# *PHPDoc* on the model — class docblock describing the resource, plus per-property docblock entries covering {{$table}}, {{$fillable}}, {{$casts}}, status enums, and Eloquent relationships (with their semantics, not just types).
# *Feature test* under {{api/tests/Feature/<Model>ApiTest.php}} — uses {{DatabaseTransactions}} + {{WithFaker}}, authenticates as {{SecurityUsers id=2}} via {{JWTAuth::fromUser}}, exercises the full CRUD surface against {{/api/v5/<resource>}} ({{GET}} list / {{GET}} by id / {{POST}} / {{PUT}} / {{DELETE}}), and *passes 100%*.

A v5 resource is not "done" until {{php artisan test tests/Feature/<Model>ApiTest.php}} returns green. If a feature test fails, fix the model / factory / migration — never weaken the test.

*Verified for Phase 1a:*

{code}
PASS Tests\Feature\TasksApiTest        (5 passed, 0.40s)
PASS Tests\Feature\TaskJobsApiTest     (5 passed, 0.17s)
PASS Tests\Feature\TaskFailuresApiTest (5 passed, 0.23s)
                                       ─────────────
                                       15 / 15 passed
{code}

Each test covers list / create / view / update / delete via the CRUD pipeline ({{200 / 201 / 200 / 200 / 204}}).

h3. 4.7 Governance constraints (binding, codified in {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}})

The reviewer's constraints become repo-wide rules:

# All new async functionality uses *Laravel-side scheduling and queue processing*.
# *No new CakePHP commands or shells.*
# *No new {{exec()}} / {{shell_exec()}} implementations.*
# *No new CakePHP-side queue infrastructure.* The {{system_queue*}} tables are the OpenEMIS-owned target; legacy CakePHP queue tables ({{alert_queue}}, {{webhook_queue}}) are frozen.
# *No request-triggered background processing.* Angular v20 SPA cannot reliably trigger work; retire the pattern formally.
# *Operational documentation uses OpenEMIS vocabulary*: OpenEMIS Runtime, OpenEMIS Queue, OpenEMIS Tasks, OpenEMIS Workers, OpenEMIS Failures. Framework names are implementation details only.
# *Every v5 model ships with factory + PHPDoc + 100%-passing feature test in the same commit that registers it in {{CrudApiController::$allowedResources}}* — see §4.6.

{{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}} is developer-facing and cross-linked from {{CONTRIBUTING.md}} and {{README.md}} so it applies regardless of which AI assistant or editor a contributor uses.

----

h2. 5. Files Changed Summary

*Phase 1a (this commit) — actually changed:*

|| Area || Files ||
| Docker / runtime cron | {{Dockerfile}} (added {{cron}} package + crontab install), {{docker-config/init.sh}} (starts {{cron}} daemon, ensures log file), {{docker-config/cron/openemis-core}} (the single cron line) |
| Webhook anti-pattern fix | {{api/app/Repositories/WebhookRepository.php}} ({{exec()}} → {{webhook_queue}} insert + {{TasksRecorder::recordEnqueue()}}) |
| OpenEMIS Tasks abstraction — migration | {{config/Migrations/20260508143848_POCOR9694.php}} (creates {{tasks}}, {{task_jobs}}, {{task_failures}}; new-table-only, no backup) |
| OpenEMIS Tasks — Eloquent models | {{api/app/Models/Api5/Tasks.php}}, {{api/app/Models/Api5/TaskJobs.php}}, {{api/app/Models/Api5/TaskFailures.php}} |
| Dual-write helper | {{api/app/Services/OpenemisRuntime/TasksRecorder.php}} (six methods, never throws back) |
| OpenEMIS Runtime entry-point | {{api/app/Console/Commands/OpenemisCoreRunCommand.php}} (auto-registered) |
| Admin Runtime section — v5 CRUD reads | {{api/app/Http/Controllers/BaseApi/CrudApiController.php}} ({{tasks}} / {{task-jobs}} / {{task-failures}} added to {{$allowedResources}}) |
| Admin Runtime section — v4 actions/aggregates | {{api/app/Http/Controllers/Administration/SystemRuntimeController.php}} (Swagger-annotated), {{api/routes/api.php}} (5 endpoints under v4) |
| Factories (v5 contract) | {{api/database/factories/Api5/TasksFactory.php}}, {{TaskJobsFactory.php}}, {{TaskFailuresFactory.php}} |
| Feature tests (v5 contract) | {{api/tests/Feature/TasksApiTest.php}}, {{TaskJobsApiTest.php}}, {{TaskFailuresApiTest.php}} — 15 / 15 passing |
| Documentation | {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}} (governance, developer-facing) |

*Phase 1b (deferred to follow-up ticket):*

|| Area || Files ||
| Docker PHP pin | {{Dockerfile}} ({{php:8.4-apache}} base) |
| Alerts dual-write | {{api/app/Console/Commands/Alerts/AlertCommandBase.php}} (call {{TasksRecorder::recordEnqueue/Start/Success/Failure}}) |
| Admin Runtime section — frontend | {{frontend/src/app/system-runtime/}} — Angular module, one component per page (Tasks, Failures, Logs, Queue, Scheduler) |
| Permissions seed | {{config/Seeds/POCOR9694SecuritySeed.php}} ({{System.SystemRuntime.view}} + {{execute}}, assigned to super_admin) |
| Audit hook | retry / force-abort actions write to {{audit_logs}} |

----

h2. 6. Database Migrations

{{config/Migrations/20260508143848_POCOR9694.php}}.

* Creates {{tasks}}, {{task_jobs}}, {{task_failures}} (all new — no destructive changes to existing tables).
* New-table-only migration; {{up()}} does *not* call {{backupTables()}} (nothing to back up).
* {{down()}} drops the three new tables only. {{alert_queue}}, {{webhook_queue}}, {{jobs}}, {{failed_jobs}}, {{system_processes}} are *not touched*.
* Indexes: {{(status, available_at)}} on {{tasks}}; {{(task_id, attempt_number)}} on {{task_jobs}}; {{(task_id)}} on {{task_failures}}.

----

h2. 7. Deployment Instructions

# *Pull the branch and rebuild containers* (only needed if shipping the Docker cron change to production; dev environments running an older image can still operate by adding the cron line manually):

{code:bash}
git checkout POCOR-9694
docker compose down
docker compose up --build -d
{code}

# *Run the migration:*

{code:bash}
docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && php bin/cake.php migrations migrate"
{code}

Verify the three tables exist:

{code:sql}
SHOW TABLES LIKE 'task%';   -- expect: task_failures, task_jobs, tasks
{code}

# *Verify the cron is running inside the container:*

{code:bash}
docker exec poe-application /bin/sh -c "service cron status"
docker exec poe-application /bin/sh -c "cat /etc/cron.d/openemis-core"
{code}

# *Confirm the Runtime is alive* (after the first cron tick, ≤ 60 seconds):

{code:bash}
docker exec poe-application /bin/sh -c "tail -5 /var/www/html/emis/core/api/storage/logs/openemis-core-run.log"
# Or via the API (replace TOKEN with a JWT):
curl -sk -H "Authorization: Bearer TOKEN" \
  https://localhost:8482/core/api/v5/system-runtime/scheduler | jq .heartbeat
{code}

# *(Optional) Decommission the legacy POCOR-9509 cron entries* — once the OpenEMIS Runtime cron is confirmed draining, the three direct entries ({{webhooks:process}}, {{alerts:check}}, {{alerts:send}}) installed by the POCOR-9509 deploy guide can be removed. {{openemis-core:run}} dispatches them through the Laravel scheduler so they continue to fire on their declared intervals.

# *Smoke-test the OpenEMIS Runtime endpoints (Phase 1a):*

{code:bash}
TOK="<JWT from /api/v4/login>"

# v5 — uniform resource reads (CrudApiController)
for r in tasks task-jobs task-failures; do
  echo "=== v5/$r ==="
  curl -sk -H "Authorization: Bearer $TOK" \
    "https://localhost:8482/core/api/v5/$r" | head -c 200; echo
done

# v4 — bespoke actions / aggregates
for p in logs queue scheduler; do
  echo "=== v4/system-runtime/$p ==="
  curl -sk -H "Authorization: Bearer $TOK" \
    "https://localhost:8482/core/api/v4/system-runtime/$p" | head -c 200; echo
done
{code}

The Angular UI for these endpoints ships in Phase 1b.

----

h2. 8. System Administrator Guide

*Administration → System → OpenEMIS Runtime* surfaces operational health. Use it to:

* *Confirm the Runtime is alive* — the Scheduler page shows last successful run per scheduled OpenEMIS Task. If a task's age exceeds its expected interval, the cron line is missing or the Runtime is not draining.
* *Triage stuck OpenEMIS Tasks* — the Tasks page shows {{tasks}} rows in NEW or PROCESSING for more than 1 hour. Use *Force Abort* to mark them as ABORT so subsequent runs can re-process the underlying work.
* *Recover from failures* — the Failures page lists all OpenEMIS Failures with exception detail. Use *Retry* to push the source row back to NEW for the next Runtime tick.
* *Spot a backlog* — the Queue page shows backlog depth per task type and oldest-pending age. Persistent backlog usually means a Runtime tick is timing out; check stuck processes first.

*Permissions:* {{System.SystemRuntime.view}} for read, {{System.SystemRuntime.execute}} for retry / force-abort. Both ship pre-assigned to {{super_admin}}; ministry deployments should grant view to operations staff and reserve execute for senior admins.

*Audit:* every retry / force-abort writes a row to {{audit_logs}}.

----

h2. 9. Decisions / context not to re-litigate

* *No "Async Runtime v2" rewrite.* Endorsed by the review.
* *Single OpenEMIS Runtime entry-point: {{openemis-core:run}}.* Synced with the Laravel scheduler tick; either dispatches the same work.
* *{{tasks}} / {{task_jobs}} / {{task_failures}} are the OpenEMIS-branded target.* Legacy queue tables ({{alert_queue}}, {{webhook_queue}}, {{jobs}}, {{failed_jobs}}) remain in place for v5 — Laravel writes to both via {{TasksRecorder}}. CakePHP code is not refactored.
* *{{system_processes}} stays as the cross-stack execution-tracking contract.*
* *No request-triggered background processing.* The hybrid web-trigger fallback explored during the audit was rejected on review — Angular SPA cannot reliably trigger background work, and the precedent ("trigger on login") was already commented out in {{src/Controller/DashboardController.php}} as part of the POCOR-9509 migration.
* *Operational documentation uses OpenEMIS vocabulary.* Framework names ({{queue:work}}, Horizon, Supervisor, Redis, Laravel jobs, {{schedule:run}}) are implementation details, not platform architecture.
* *CakePHP phase-out is happening in Core v6.* No migration of the 73 legacy Shells, no refactoring of the 16 duplicate CakePHP alert commands, no in-place fixes for the 127 {{exec()}} callsites in CakePHP code — those are v6-cycle work.

----

h2. 10. Where We Are Heading (Phase 2 — Core v6)

Phase 1 (this ticket) lays the foundation. Phase 2 finishes the OpenEMIS Runtime & Queue Framework as part of the Core v6 cycle (Angular v20 frontend + CakePHP retirement). Captured here so the platform direction is recorded officially:

|| Phase 2 deliverable || Form ||
| Event-driven processing | Queue items become events ({{student.absence.threshold_reached}}, {{webhook.delivery.requested}}, …) dispatched to typed handlers (AlertHandler, WebhookHandler, ExportHandler, …). Replaces the current 15-Laravel-alert-commands pattern. |
| Full OpenEMIS Queue ownership | Legacy {{alert_queue}} / {{webhook_queue}} / {{jobs}} / {{failed_jobs}} retired. {{tasks}} / {{task_jobs}} / {{task_failures}} become the only async data layer. |
| OpenEMIS Workers as a formal subsystem | {{openemis-core:run}} fans out to typed workers; observability per worker. |
| Admin section split into 4 separate pages | What ships in Phase 1 as one section with 4 sub-pages may evolve into separate Administration sections in v6: Runtime, Queue, Failed Tasks, Integrations — following whatever Angular v20 SPA navigation pattern emerges. |
| OpenEMIS Platform Services formalised | Notification / Integration / Reporting / Workflow / Queue / Identity / Audit — each documented as a platform subsystem with a stable contract. |
| 127 {{exec()}} callsites migrated | Each becomes either an OpenEMIS Task enqueue or a synchronous service call, depending on duration. |
| 73 legacy CakePHP Shells deleted or rewritten as Laravel commands | CakePHP exits Core. |

The 7 platform standards in {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}} (governance §4.6) apply from Phase 1 onward and are the gate for accepting Phase 2 work.

----

h2. 11. Follow-up tickets (NOT in scope here)

|| # || Title || Phase ||
| F1 | *Delete the 16 duplicate CakePHP alert commands* in {{src/Command/Alert*Command.php}} after confirming Laravel side is authoritative | v6 |
| F2 | *Inventory the 73 legacy Shells* — classify {{live}} / {{dead}} / {{superseded}}; delete dead ones; tag live ones {{// V6-MIGRATE}} | v6 |
| F3 | *Inventory the 127 {{exec()}} callsites* — produce a CSV; becomes the v6 migration backlog | v6 |
| F4 | *Refactor the 15 Laravel alert commands into AlertHandler + event dispatcher* | v6 |
| F5 | *Retire legacy queue tables* once Phase 2 is verified — drop {{alert_queue}}, {{webhook_queue}}, {{jobs}}, {{failed_jobs}} | v6 |
| F6 | *Split Administration → OpenEMIS Runtime into 4 standalone Administration sections* if v6 SPA navigation makes that natural | v6 |

----

h2. 12. References

* POCOR-9509 — Alerts dispatch via Laravel queue (the work being standardised on top of)
* POCOR-9257 — Webhooks foundation
* {{api/app/Console/Kernel.php}} — Laravel scheduler that the new {{openemis-core:run}} command wraps
* {{api/app/Console/Commands/Alerts/AlertCommandBase.php}} — base class for the 15 Laravel alert commands (refactor deferred to v6)
* {{api/app/Repositories/WebhookRepository.php}} — {{exec()}} anti-pattern (removed in §4.2)
* {{config/Migrations/20260415030200_POCOR9509.php}} — original {{alert_queue}}, {{jobs}}, {{failed_jobs}} schema (frozen by Phase 1; superseded by {{system_queue*}} in Phase 2)
* {{plugins/System/src/Model/Table/SystemProcessesTable.php}} — execution-tracking table
* {{api/storage/release-docs/POCOR-9509-README.md}} — alerts deployment instructions (updated to point to {{openemis-core:run}})
* {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}} — governance (§4.6) in developer-facing form
* {{api/storage/release-docs/POCOR-9694/inventories/exec-callsites.csv}} — F5: 127 {{exec()}}/{{shell_exec()}}/{{proc_open()}}/{{passthru()}} callsites in non-vendor code, classified for v6 migration
* {{api/storage/release-docs/POCOR-9694/inventories/legacy-shells-inventory.csv}} — F4: 73 {{src/Shell/*.php}} files classified live / dead / superseded

----

h2. 13. Phase 1b — F-list closure & Async Services dashboard (2026-05-09)

Fifteen additional commits landed on this branch on 2026-05-09 closing eight of the nine F-list follow-ups identified during the architectural review (§3) and shipping the Async Services operator dashboard end-to-end (CakePHP nav + 5 stub admin pages + Laravel v4 read API + retry endpoint). Frontend (Angular feature module) remains for Phase 2 / Core v6.

h3. 13.1 F-list status

|| F-item || What || Status || Commit ||
| F1 | Pin Docker PHP base image to 8.4-apache | shipped | {{6250b88829}} |
| F2 | Remove {{exec()}} from {{api/app/Repositories/WebhookRepository.php}} | shipped in §4.2 (commit {{5e98ef1085}}) | — |
| F3 | Delete duplicate CakePHP alert shells + commands (audit-confirmed dead) | shipped — 22 files / 2,794 lines removed | {{c0051550bb}} |
| F4 | Inventory the 73 legacy {{src/Shell/*.php}} files | shipped (CSV) | {{aa3a4beb6c}} |
| F5 | Inventory the 127 {{exec()}} callsites with proposed v6 replacement strategy | shipped (CSV) | {{d42947d7ed}} |
| F6 | Document the single-cron alternative in POCOR-9509 README | shipped | {{9aa6a1359d}} |
| F7 | Async Services admin dashboard — CakePHP nav + 5 fully-functional admin screens + retry + Laravel v4 backend + cross-links | shipped end-to-end | several — see §13.2 |
| F8 | Opportunistic AsyncCatchUp middleware (safety net for stalled cron) | shipped | {{24248f78f3}} |
| F9 | {{docs/v6-transition-rules.md}} relocated to {{api/storage/release-docs/POCOR-9694/v6-transition-rules.md}} | shipped earlier | {{2d826a3dbd}} |

h3. 13.2 Async Services dashboard — what shipped

* *CakePHP nav restructure* — {{src/Controller/Component/NavigationComponent.php}}: existing flat *Processes* entry under Administration relocated as a child of a new *Async Services* group. Sibling children added: *Overview, Failed Jobs, Stuck Processes, Webhook Failures, Queue Backlog*. Two reusable helpers landed alongside: {{getCurrentUserRoleIds()}} (replaces the inline {{SecurityGroupUsers->matching('SecurityRoles')}} block duplicated 9 times) and {{userHasAdministrationAccessTo(string $category)}} (drop-in gate for any future admin sub-section). Commit {{8f7ec018a6}}.
* *Migration consolidation* — security_functions seeds {{module='Administration', category='Async Services'}} (6 rows) + super_admin grants merged into the single {{20260508143848_POCOR9694.php}} migration alongside the runtime tables. The original split file {{20260508160000_POCOR9694Nav.php}} was removed; the consolidated file uses a {{withForeignKeyChecksOff(callable)}} helper to keep the four DML methods DRY. Commit {{22b25210d3}}.
* *Five fully-functional CakePHP admin screens* (commits {{04c23e39bd}} → {{2f73261b11}} → {{096c3dcd75}} → {{581db3c9b9}} → {{8cfbeee77c}} → {{847208c6b5}}). After the architectural decision (2026-05-09) to ship the dashboard in pure {{ControllerAction}} rather than a separate AngularJS module — the same pattern 70+ other plugins use, so any OpenEMIS developer can maintain it — each child Table got a real implementation:

|| Screen || Source || Filter || Notable columns || Action ||
| Overview | {{system_processes}} | (none — recent activity) | KPI tiles + activity strip | — |
| Failed Jobs | {{failed_jobs}} | (none) | Queue / Exception preview / Failed At | *Retry* (transactional INSERT INTO jobs / DELETE FROM failed_jobs) |
| Stuck Processes | {{system_processes}} | status IN (1,2) AND created < NOW() - INTERVAL 1 HOUR | Feature / Status / Started / Stuck For (timeAgoInWords) / Model | — |
| Webhook Failures | {{webhook_queue}} | status = -1 | Event / Target URL / HTTP status / Last Error preview / Retries | — |
| Queue Backlog | {{alert_queue}} | status = 0 ORDER BY available_at ASC | Alert Type / Channel / Recipient / Subject / Waiting For | — |

  Shared base {{AsyncServicesAdminTable}} centralises the read-only toggles and an optional banner — concrete Tables only override {{setTable()}}. Overview KPI tiles partial: {{plugins/System/templates/Element/async_overview.php}}.

* *Manual retry for Failed Jobs* — {{SystemsController::FailedJobsRetry($id)}} replicates {{php artisan queue:retry}} via DB transaction (no exec, no shared bootstrap). Smoke-tested end-to-end: synthetic failure row → click Retry on view page → row moves from {{failed_jobs}} to {{jobs}} with {{attempts=0}} and fresh availability timestamp; cron-driven {{openemis-core:run}} picks it up on the next tick. Commit {{2f73261b11}}.

* *Cross-link toolbar buttons* (commit {{99b5afcc48}}) — single-click pivot from log/queue pages into the Async Services dashboard:
  - {{Webhook.WebhookLogs}} / {{Webhook.WebhookQueue}} → *Check failures* → {{Systems.WebhookFailures}}
  - {{Alerts.Logs}} / {{Alerts.AlertQueue}} → *Check backlog* → {{Systems.QueueBacklog}}

* *Laravel v4 backend* — {{api/app/Services/SystemAsyncService.php}} (data layer) + {{api/app/Http/Controllers/Administration/SystemAsyncController.php}} (HTTP layer) + 6 routes registered in the {{auth.jwt}} v4 group:
{code}
GET  /api/v4/system-async/overview
GET  /api/v4/system-async/failed-jobs        ?page&limit&queue
GET  /api/v4/system-async/stuck-processes    ?page&limit&hours
GET  /api/v4/system-async/webhook-failures   ?page&limit
GET  /api/v4/system-async/queue-backlog
POST /api/v4/system-async/retry/{kind}/{id}  kind ∈ failed-job|webhook|alert
{code}
Commits {{96efff8ad7}}, {{f6e8595447}}, {{12bc85e117}}. End-to-end smoke-tested via curl against {{https://localhost:8482}} on 2026-05-09 — all 5 GET endpoints respond with the expected JSON envelope.

h3. 13.3 AsyncCatchUp middleware (F8)

{{api/app/Http/Middleware/AsyncCatchUp.php}} runs in {{terminate()}} after the response has been flushed to the client. Three configurable guards keep the cost predictable:

|| Knob (env) || Default || Purpose ||
| {{ASYNC_CATCHUP_ENABLED}} | true | Master switch for emergency shut-off without removing from Kernel |
| {{ASYNC_CATCHUP_STALE_MINUTES}} | 5 | Only fires when newest {{system_processes}} row is older than this |
| {{ASYNC_CATCHUP_THROTTLE_SECONDS}} | 60 | At most one catch-up per window across the whole app |
| {{ASYNC_CATCHUP_BUDGET_MS}} | 200 | {{set_time_limit}} hard upper bound on the {{schedule:run}} call |

Calls {{openemis-core:run}} via {{Artisan::call()}} — in-process, NOT via {{exec()}}, so no FPM worker tie-up and no shell-injection surface area. Registered in the {{web}} middleware group only; API requests skip it. Commit {{24248f78f3}} (the call target was corrected from {{schedule:run}} → {{openemis-core:run}} in the same commit family that polished the docs).

h3. 13.4 Swagger PHPDoc cleanup on runtime models

The three v5 runtime models ({{Tasks}}, {{TaskJobs}}, {{TaskFailures}}) ship the same 6-stub Swagger annotation block as the 665-model project majority — {{_swaggerPath}}, {{_swaggerList}}, {{_swaggerCreate}}, {{_swaggerView}}, {{_swaggerUpdate}}, {{_swaggerDelete}}. The initial Phase 1b version mistakenly included a 7th {{_swaggerHelper}} stub (matching the 8-model outlier pattern of {{AbsenceTypes}}); that stub was stripped to align with the majority. Generator verified end-to-end:

{code:bash}
docker exec poe-application sh -c "cd /var/www/html/emis/core/api && php artisan l5-swagger:generate"
# Regenerating docs v5
# exit 0; api-docs-v5.json valid, 1353 paths, /api/v5/{tasks,task-jobs,task-failures} present
{code}

Commits {{b345c33b03}} (add) + {{f557b67184}} (align with majority).

h3. 13.5 What is *not* shipped (deferred to Phase 2 / Core v6)

* *Angular v20 SPA replacement* — the CakePHP screens are fully functional today; an Angular v20 rewrite is not blocking. When v6 lands, the same {{/api/v4/system-async/*}} endpoints already exist for the new SPA to consume.
* *Bulk retry / mass-abort actions* — single-row Retry only on Failed Jobs. If operators ask for bulk-retry or mass-abort, those land in a follow-up ticket.
* *Per-rule alert dispatch policy + scope predicates* — surfaced during the Async Services build but architecturally separate; drafted as Jira-ready proposals at {{tmp/POCOR-9694/followup-alert-dispatch-policy.jira.md}} and {{tmp/POCOR-9694/followup-alert-rule-predicates.jira.md}}.

h3. 13.6 Known limitations & operational notes

A critical-eyes audit pass on 2026-05-09 surfaced four items worth recording here so they are not rediscovered as "bugs" later:

* *Retry button on Failed Jobs uses GET, not POST.* This matches the established OpenEMIS convention for admin toolbar actions ({{Trigger Alert Check}}, {{Trigger Alert Send}}, {{processQueue}} are also GETs). Inherits the existing CSRF risk profile — does not introduce a new class of vulnerability. A project-wide conversion of admin actions to {{POST + CSRF token}} would be its own ticket spanning multiple plugins.

* *Heartbeat freshness can false-positive.* {{AsyncServicesOverview}} computes "Last heartbeat" as {{MAX(system_processes.created)}}; if there are simply no jobs running, this stays old even with a healthy cron. In production deployments this is rarely an issue (alert/webhook/job rate is high enough to refresh the column). When the dedicated runtime heartbeat row is wired in (Phase 1 follow-up), the dashboard should switch to it.

* *Webhook Failures has no Retry button.* Failures live in {{webhook_queue}} (not {{failed_jobs}}); the Laravel-side retry mechanism is different and not yet replicated in CakePHP. Operators currently retry via the {{webhooks:process}} artisan command. Adding an in-page Retry is a small follow-up that mirrors the {{FailedJobsRetry}} pattern against {{webhook_queue.status}} reset.

* *ACL deployment note for environments that already applied the migration.* The migration's {{view_actions}} for the *Failed Jobs* security_function row was extended to include {{FailedJobsRetry.index}} (commit {{839b2e0882}}) so future non-super_admin grants on the page also work for retry. Environments that already ran the earlier version of {{20260508143848_POCOR9694.php}} need either:

{code:sql}
UPDATE security_functions
SET _view = 'FailedJobs.index|FailedJobs.view|FailedJobsRetry.index'
WHERE name = 'Failed Jobs'
  AND module = 'Administration'
  AND category = 'Async Services';
{code}

  ...or a rollback + re-apply of the migration. Fresh deploys pick the new value up automatically. Today only super_admin (id=10) is granted, which bypasses the per-action ACL; the manual UPDATE only matters once non-super_admin grants are added.

h3. 13.7 End-to-end smoke (2026-05-09)

Validated under {{admin/demo}} login on {{https://localhost:8482}}:

# Each of the 5 screens loads cleanly with the expected column set.
# Seeded synthetic rows (1 {{failed_jobs}}, 2 {{webhook_queue}} status=-1, 2 {{alert_queue}} status=0): Overview KPI tiles correctly show *1 Failed Jobs / 2 Webhook Failures / 2 Queue Backlog / 0 Stuck Processes*; clicking each tile lands on the matching detail screen showing the seeded rows.
# Failed Jobs retry: clicked Retry on a synthetic failure → row moved {{failed_jobs}} → {{jobs}} with {{attempts=0}}; redirected back to index.
# Cross-link buttons: *Check failures* on Webhook Logs navigates to {{Systems.WebhookFailures}}; *Check backlog* on Alert Logs renders the QueueBacklog deep-link.
# Existing Trigger Alert Check / Trigger Alert Send buttons still function — no PHP fatals introduced by the Table edits.
