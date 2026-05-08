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

h3. 4.1 Pin Docker PHP to 8.4.x

Restores root composer to a working state. Without this, {{composer require}} on root fails because host PHP 8.5.5 is past the upper bound of {{phpoffice/phpspreadsheet}} and {{laminas/laminas-diactoros}}.

* {{Dockerfile}} / {{docker-compose.yml}} updated to PHP 8.4.x base image.
* {{README.md}} carries a one-line note on the pinned PHP version and why.

h3. 4.2 Remove {{exec()}} from {{WebhookRepository.php}}

The fork-from-FPM anti-pattern is replaced by enqueueing through the existing scheduler-backed processing path. Webhook delivery now follows a queue-driven flow with no in-request subprocess spawn.

* {{api/app/Repositories/WebhookRepository.php}} — {{exec()}} call replaced with a queue insert. The OpenEMIS Runtime drains the queue every minute.
* No new tables, no new commands, no new env knobs introduced by this change alone.

h3. 4.3 OpenEMIS Queue tables (parallel/shadow data layer)

Three new OpenEMIS-owned tables are introduced as the platform-named surface for async work. They are populated by Laravel-side write paths *in parallel* with the existing {{alert_queue}} / {{webhook_queue}} / {{jobs}} / {{failed_jobs}} tables — dual-write — so the new admin pages have a stable, OpenEMIS-vocabulary source while existing CakePHP code paths remain untouched.

|| Table || Purpose ||
| {{system_queue}} | Pending OpenEMIS Tasks (one row per enqueued unit of work). Status enum mirrors {{system_processes}} convention: 0=NEW, 1=PROCESSING, 2=DONE, -1=ABORT, -2=FAILED. Carries {{task_type}} ({{alert}}, {{webhook}}, {{export}}, …), {{payload}} JSON, {{available_at}}, {{retry_count}}. |
| {{system_queue_logs}} | Per-attempt log entries — start, end, duration, outcome, message preview. One queue row may have many log rows. |
| {{system_queue_failures}} | Failure detail rows — exception class, message, stack trace, originating queue id, retry-allowed flag. |

*Dual-write rules:*
* New Laravel write paths (queue-aware services touched by Phase 1) write to *both* legacy tables AND the {{system_queue*}} tables.
* Existing CakePHP write paths are left as-is. They continue to write to legacy tables only. They may or may not eventually integrate with the new tables — that is v6 work.
* The Phase 1 admin pages (§4.5) read from the {{system_queue*}} tables only, plus {{system_processes}} for stuck-process detection.
* Migration follows the repo's backup convention ({{z_9694_*}} backup tables for any altered table; {{down()}} restores).

h3. 4.4 OpenEMIS Runtime entry-point: {{openemis-core:run}}

A single OpenEMIS-branded scheduler entry-point is added. Internally it invokes the Laravel scheduler tick; ops teams interact only with the OpenEMIS name.

* New artisan command: {{php artisan openemis-core:run}} — registered in {{api/app/Console/Kernel.php}}.
* Internally calls the scheduler so existing scheduled commands ({{webhooks:process}}, {{alerts:check}}, {{alerts:send}}) continue to fire on their declared intervals.
* The legacy {{php artisan schedule:run}} continues to function and is *synced* with {{openemis-core:run}} — calling either one dispatches the same scheduler tick. Operationally, ministries are advised to switch to {{openemis-core:run}} for all new deployments; existing deployments may keep {{schedule:run}} until their next ops-doc refresh.
* Canonical cron line going forward:
{code}
* * * * * cd /var/www/html/emis/core/api && php artisan openemis-core:run >> /dev/null 2>&1
{code}

h3. 4.5 Administration → System → OpenEMIS Runtime (section with multiple pages)

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

Backend: {{api/app/Http/Controllers/Api5/SystemRuntimeController.php}} + {{api/app/Services/RuntimeHealthService.php}}.
Frontend: {{frontend/src/app/system-runtime/}} (Angular module with one component per page, mirroring the Webhooks / Alerts module structure).

*Permissions:* the section is gated by {{System.SystemRuntime.view}}; retry / force-abort require {{System.SystemRuntime.execute}}. Both ship in the seed data and are assigned to {{super_admin}} by default. The view permission is decoupled from per-feature permissions — an operator can see the Runtime without having Webhook / Alert configuration permissions.

*Audit:* every retry / force-abort writes a row to {{audit_logs}} with the acting {{security_user_id}}, target {{system_queue.id}}, and action.

h3. 4.6 Governance constraints (binding, codified in {{docs/v6-transition-rules.md}})

The reviewer's constraints become repo-wide rules:

# All new async functionality uses *Laravel-side scheduling and queue processing*.
# *No new CakePHP commands or shells.*
# *No new {{exec()}} / {{shell_exec()}} implementations.*
# *No new CakePHP-side queue infrastructure.* The {{system_queue*}} tables are the OpenEMIS-owned target; legacy CakePHP queue tables ({{alert_queue}}, {{webhook_queue}}) are frozen.
# *No request-triggered background processing.* Angular v20 SPA cannot reliably trigger work; retire the pattern formally.
# *Operational documentation uses OpenEMIS vocabulary*: OpenEMIS Runtime, OpenEMIS Queue, OpenEMIS Tasks, OpenEMIS Workers, OpenEMIS Failures. Framework names are implementation details only.

{{docs/v6-transition-rules.md}} is developer-facing and cross-linked from {{CONTRIBUTING.md}} and {{README.md}} so it applies regardless of which AI assistant or editor a contributor uses.

----

h2. 5. Files Changed Summary

|| Area || Files ||
| Docker / runtime | {{Dockerfile}}, {{docker-compose.yml}}, {{README.md}} |
| Webhook anti-pattern fix | {{api/app/Repositories/WebhookRepository.php}} |
| OpenEMIS Queue tables — migration | {{config/Migrations/<timestamp>_POCOR9694.php}} (creates {{system_queue}}, {{system_queue_logs}}, {{system_queue_failures}}; backup pattern follows repo convention) |
| OpenEMIS Queue — Laravel models | {{api/app/Models/Api5/SystemQueue.php}}, {{api/app/Models/Api5/SystemQueueLogs.php}}, {{api/app/Models/Api5/SystemQueueFailures.php}} |
| Dual-write integration | {{api/app/Services/AlertTriggerService.php}}, {{api/app/Services/WebhookSender.php}}, {{api/app/Jobs/RunAlertJob.php}}, {{api/app/Console/Commands/Alerts/AlertCommandBase.php}} (write to {{system_queue*}} alongside legacy tables) |
| OpenEMIS Runtime entry-point | {{api/app/Console/Commands/OpenemisCoreRunCommand.php}}, {{api/app/Console/Kernel.php}} (registration) |
| Admin Runtime section (backend) | {{api/app/Http/Controllers/Api5/SystemRuntimeController.php}}, {{api/app/Services/RuntimeHealthService.php}}, {{api/routes/api.php}} |
| Admin Runtime section (frontend) | {{frontend/src/app/system-runtime/}} — Angular module, one component per page (Tasks, Failures, Queue, Scheduler) |
| Permissions seed | {{config/Seeds/POCOR9694SecuritySeed.php}} (System.SystemRuntime.view + execute) |
| Documentation | {{docs/v6-transition-rules.md}} (governance), {{api/storage/release-docs/POCOR-9509-README.md}} (single-cron note pointing to {{openemis-core:run}}) |

----

h2. 6. Database Migrations

{{config/Migrations/<timestamp>_POCOR9694.php}}.

* Creates {{system_queue}}, {{system_queue_logs}}, {{system_queue_failures}} (all new — no destructive changes to existing tables).
* {{up()}} first calls {{backupTables()}} for the new table names (no-op on first run; defensive for re-runs).
* {{down()}} drops the three new tables only — no restore of existing tables. Existing {{alert_queue}}, {{webhook_queue}}, {{jobs}}, {{failed_jobs}}, {{system_processes}} are *not touched* by this migration.
* Indexes: {{(status, available_at)}} on {{system_queue}}; {{(system_queue_id, created)}} on logs and failures.

----

h2. 7. Deployment Instructions

# *Pull the branch and rebuild containers* — Docker base image is now PHP 8.4.x:

{code:bash}
git checkout POCOR-9694
docker compose down
docker compose up --build -d
{code}

# *Run migrations:*

{code:bash}
docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && php bin/cake.php migrations migrate"
{code}

# *Verify composer is unblocked:*

{code:bash}
docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && composer validate --no-check-publish"
{code}

# *Switch the cron entry to the OpenEMIS Runtime:*

{code}
# Recommended canonical cron (replaces the three POCOR-9509 entries for new deployments):
* * * * * cd /var/www/html/emis/core/api && php artisan openemis-core:run >> /dev/null 2>&1

# Existing POCOR-9509 deployments may keep their direct alerts:check / alerts:send entries
# until their next ops-doc refresh. {{openemis-core:run}} and {{schedule:run}} are synced —
# either dispatches the same scheduler tick.
{code}

# *Smoke-test the OpenEMIS Runtime section:*
* Login as admin → Administration → System → OpenEMIS Runtime
* All four pages should render: Tasks, Failures, Queue, Scheduler.
* Trigger a webhook with a deliberately-bad URL → it should appear under *Failures* within ≤ 60 seconds (next OpenEMIS Runtime tick).
* Click *Retry* → the row returns to NEW; on next tick it transitions through PROCESSING and back to FAILED (or DONE if you fix the URL between attempts).

----

h2. 8. System Administrator Guide

*Administration → System → OpenEMIS Runtime* surfaces operational health. Use it to:

* *Confirm the Runtime is alive* — the Scheduler page shows last successful run per scheduled OpenEMIS Task. If a task's age exceeds its expected interval, the cron line is missing or the Runtime is not draining.
* *Triage stuck OpenEMIS Tasks* — the Tasks page shows {{system_queue}} rows in NEW or PROCESSING for more than 1 hour. Use *Force Abort* to mark them as ABORT so subsequent runs can re-process the underlying work.
* *Recover from failures* — the Failures page lists all OpenEMIS Failures with exception detail. Use *Retry* to push the source row back to NEW for the next Runtime tick.
* *Spot a backlog* — the Queue page shows backlog depth per task type and oldest-pending age. Persistent backlog usually means a Runtime tick is timing out; check stuck processes first.

*Permissions:* {{System.SystemRuntime.view}} for read, {{System.SystemRuntime.execute}} for retry / force-abort. Both ship pre-assigned to {{super_admin}}; ministry deployments should grant view to operations staff and reserve execute for senior admins.

*Audit:* every retry / force-abort writes a row to {{audit_logs}}.

----

h2. 9. Decisions / context not to re-litigate

* *No "Async Runtime v2" rewrite.* Endorsed by the review.
* *Single OpenEMIS Runtime entry-point: {{openemis-core:run}}.* Synced with the Laravel scheduler tick; either dispatches the same work.
* *{{system_queue*}} tables are the OpenEMIS-branded target.* Legacy queue tables ({{alert_queue}}, {{webhook_queue}}, {{jobs}}, {{failed_jobs}}) remain in place for v5 — Laravel writes to both. CakePHP code is not refactored.
* *{{system_processes}} stays as the cross-stack execution-tracking contract.*
* *No request-triggered background processing.* The hybrid web-trigger fallback explored during the audit was rejected on review — Angular SPA cannot reliably trigger background work, and the precedent ("trigger on login") was already commented out in {{src/Controller/DashboardController.php}} as part of the POCOR-9509 migration.
* *Operational documentation uses OpenEMIS vocabulary.* Framework names ({{queue:work}}, Horizon, Supervisor, Redis, Laravel jobs, {{schedule:run}}) are implementation details, not platform architecture.
* *CakePHP phase-out is happening in Core v6.* No migration of the 73 legacy Shells, no refactoring of the 16 duplicate CakePHP alert commands, no in-place fixes for the 127 {{exec()}} callsites in CakePHP code — those are v6-cycle work.

----

h2. 10. Where We Are Heading (Phase 2 — Core v6)

Phase 1 (this ticket) lays the foundation. Phase 2 finishes the OpenEMIS Runtime & Queue Framework as part of the Core v6 cycle (Angular v20 frontend + CakePHP retirement). Captured here so the platform direction is recorded officially:

|| Phase 2 deliverable || Form ||
| Event-driven processing | Queue items become events ({{student.absence.threshold_reached}}, {{webhook.delivery.requested}}, …) dispatched to typed handlers (AlertHandler, WebhookHandler, ExportHandler, …). Replaces the current 15-Laravel-alert-commands pattern. |
| Full OpenEMIS Queue ownership | Legacy {{alert_queue}} / {{webhook_queue}} / {{jobs}} / {{failed_jobs}} retired. {{system_queue*}} becomes the only async data layer. |
| OpenEMIS Workers as a formal subsystem | {{openemis-core:run}} fans out to typed workers; observability per worker. |
| Admin section split into 4 separate pages | What ships in Phase 1 as one section with 4 sub-pages may evolve into separate Administration sections in v6: Runtime, Queue, Failed Tasks, Integrations — following whatever Angular v20 SPA navigation pattern emerges. |
| OpenEMIS Platform Services formalised | Notification / Integration / Reporting / Workflow / Queue / Identity / Audit — each documented as a platform subsystem with a stable contract. |
| 127 {{exec()}} callsites migrated | Each becomes either an OpenEMIS Task enqueue or a synchronous service call, depending on duration. |
| 73 legacy CakePHP Shells deleted or rewritten as Laravel commands | CakePHP exits Core. |

The 7 platform standards in {{docs/v6-transition-rules.md}} (governance §4.6) apply from Phase 1 onward and are the gate for accepting Phase 2 work.

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
* {{docs/v6-transition-rules.md}} — governance (§4.6) in developer-facing form
