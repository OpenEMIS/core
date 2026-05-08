# OpenEMIS Core — v6 Transition Rules

> Developer-facing rules for any background, async, or scheduled work added to OpenEMIS Core
> while we phase CakePHP out and migrate the platform to Laravel + Angular v20 in Core v6.
>
> These rules apply to **all** developers regardless of editor or AI assistant
> (VS Code, JetBrains, Codex, Gemini, Claude, Antigravity, plain vim …).
> They are NOT model-specific. Treat them as a standing pull-request review checklist.

Established by **POCOR-9694 (Phase 1 — OpenEMIS Runtime & Queue Framework)**.

---

## 1. No `exec()` from request lifecycle

**Rule.** Code that runs inside a web request (controller, repository, table, service called
from FPM) must never call `exec()`, `shell_exec()`, `passthru()`, `system()`, or `proc_open()`
to spawn `php artisan …` / `php bin/cake.php …`.

**Why.** Forking PHP from FPM is unsupervised, untraceable, and racy. We cannot retry it,
inspect it, or back-pressure it. POCOR-9694 removed the last instance from
`api/app/Repositories/WebhookRepository.php`.

**Apply by.** Insert a row into the legacy queue table (`webhook_queue`, `alert_queue`)
**and** dual-write via `App\Services\OpenemisRuntime\TasksRecorder::recordEnqueue()`.
The single-cron `openemis-core:run` will pick it up within ~60 seconds.

---

## 2. Single-cron entry-point

**Rule.** Production deployments install **exactly one** cron line:

```
*  *  *  *  *  www-data  cd /var/www/html/core/api && /usr/local/bin/php artisan openemis-core:run >> /var/www/html/core/api/storage/logs/openemis-core-run.log 2>&1
```

Existing `webhooks:process`, `alerts:check`, `alerts:send` cron entries — if installed
manually per the POCOR-9509 release-doc — are **deprecated** in favour of the single cron.
Leave them in place for now; remove them when migrating that environment to v6.

**Why.** One cron line keeps ops-ergonomics low. The Laravel scheduler dispatches everything
under one tick. Sysadmins debug ONE place.

**Apply by.** Adding a new scheduled job means editing
`api/app/Console/Kernel.php::schedule()` — never adding a new cron line.

---

## 3. Dual-write via the OpenEMIS Tasks abstraction

**Rule.** Any new async work must:

1. Insert into the legacy queue (e.g. `webhook_queue`, `alert_queue`) — **authoritative for execution**.
2. Call `TasksRecorder::recordEnqueue($taskType, $payload, $sourceTable, $sourceId)` — projects into `tasks`.
3. On execution, call `recordStart($taskId)` → `recordSuccess(…)` / `recordFailure(…)`.

**Why.** The `tasks` / `task_jobs` / `task_failures` triple is the OpenEMIS-branded shadow.
The Administration → System → OpenEMIS Runtime UI reads only from these tables, so the UI
remains stable even if the underlying engine is swapped (database driver → Redis →
something else) in v6. The abstraction also lets v6 expose Tasks via MCP without exposing
Laravel internals.

**Apply by.** Always go through `TasksRecorder`. The recorder swallows its own exceptions
on purpose — the legacy write must remain authoritative; the projection must never break a
business operation.

---

## 4. New code goes in Laravel, not CakePHP

**Rule.** All new HTTP endpoints, business logic, scheduled jobs, and CLI commands land
in `api/` (Laravel 8 → 11 → v6). Do not extend a CakePHP table, controller, or shell with
new behaviour unless the work is explicitly a back-port to the existing CakePHP UI.

**Why.** CakePHP is on its way out. Adding to `src/` or `plugins/` extends migration debt.
Composer there is fragile; PHP 8.4 + CakePHP 5 + 70+ plugins is a maintenance burden we
intend to retire in Core v6.

**Apply by.** New CRUD → register in `api/app/Models/Api5/<Model>.php` and let
`CrudApiController` route it. New business logic → service in `api/app/Services/`.
New CLI → `php artisan` command in `api/app/Console/Commands/`.

---

## 5. CLI for execution, REST for state

**Rule.** Anything that **does** work (run a job, regenerate a cache, send an alert, build a
report) ships as `php artisan <name>`. Anything that **reads or mutates state**
(get/list/post/put/delete a resource) goes through `/api/v5/` via `CrudApiController`.

**Why.** This is the Command-Query Separation that makes Core MCP-ready in v6: artisan
commands map to MCP tools, v5 endpoints map to MCP resources. Cleaner audit, cleaner
permission scope, cleaner contract.

**Apply by.** Don't add controller actions that perform side-effects beyond the resource
they own. Don't add artisan commands that are really just data fetches with `--json`.

### 5a. Every v5 model ships with factory + PHPDoc + 100%-passing feature test

**Rule.** When you add a model to `CrudApiController::$allowedResources`, the *same commit* must include all three of:

1. **Factory** under `api/database/factories/Api5/<Model>Factory.php` — extends `Illuminate\Database\Eloquent\Factories\Factory`, declares `$model`, returns a row from `definition()` that respects every NOT NULL column and FK-shape link.
2. **PHPDoc** on the model — class docblock describing the resource, plus per-property entries covering `$table`, `$fillable`, `$casts`, status enums, and Eloquent relationships (semantics, not just types).
3. **Feature test** under `api/tests/Feature/<Model>ApiTest.php` — `DatabaseTransactions` + `WithFaker`, authenticates as `SecurityUsers id=2` via `JWTAuth::fromUser`, exercises GET list / GET by id / POST / PUT / DELETE against `/api/v5/<resource>` and **passes 100%**.

**Why.** Every other v5 resource on the codebase ships with this triple. A new resource without it breaks the uniformity that makes v5 predictable, leaves QA without a regression hook, and silently blocks future MCP exposure (an MCP resource needs deterministic CRUD behaviour to be wrapped). The user has been clear this is non-negotiable.

**Apply by.** A v5 resource is not "done" until `php artisan test tests/Feature/<Model>ApiTest.php` returns green. If a feature test fails, fix the model / factory / migration — never weaken the test. POCOR-9694 verified for Phase 1a:

```
PASS Tests\Feature\TasksApiTest        (5 passed)
PASS Tests\Feature\TaskJobsApiTest     (5 passed)
PASS Tests\Feature\TaskFailuresApiTest (5 passed)
                                       15 / 15 passed
```

### 5b. `/api/v5/` is uniform CRUD only — bespoke endpoints belong under `/api/v4/`

**Rule.** New uniform GET/POST/PUT/DELETE on a resource: register the model in
`CrudApiController::$allowedResources`. **Do not** add a hand-written controller under
v5. New bespoke endpoints (file tails, cross-table aggregates, executable actions like
`retry` / `abort`, anything that doesn't map cleanly to one resource) go under v4 in a
purpose-built controller with full Swagger annotations.

**Why.** v5 is the predictable contract — same query language, same containment, same
pagination across every resource. Adding hand-written v5 routes erodes that uniformity
and the v6 catch-all route at the bottom of the v5 group will eventually swallow them
unless someone remembers to register them above the catch-all.

**Apply by.** If you can model it as a resource, add to `$allowedResources` and stop. If
not, name the endpoint clearly under `/api/v4/<feature>/<action>` and document it with
`@OA\Get` / `@OA\Post` blocks. POCOR-9694 follows this rule:

- `tasks`, `task-jobs`, `task-failures` → registered in `$allowedResources` (v5).
- `system-runtime/logs`, `system-runtime/queue`, `system-runtime/scheduler`,
  `system-runtime/tasks/{id}/retry`, `system-runtime/tasks/{id}/abort` → bespoke,
  hand-written under v4 in `SystemRuntimeController`.

---

## 6. No Laravel-internal vocabulary in OpenEMIS UI

**Rule.** The Administration UI must never say "queue worker", "failed job",
"Horizon", "supervisor", "schedule:run". OpenEMIS-branded vocabulary only:
"OpenEMIS Runtime", "Tasks", "Failures", "Logs", "Queue", "Scheduler".

**Why.** Operators are ministry sysadmins, not Laravel developers. Vocabulary leaks make
the system look like a Laravel app instead of an OpenEMIS product, and tie us to a single
engine.

**Apply by.** UI labels reference OpenEMIS concepts. If you need a Laravel construct
internally, hide it behind a service in `api/app/Services/OpenemisRuntime/`.

---

## 7. Migrations are reversible and back up tables

**Rule.** Any migration that alters or modifies data in an existing table must:

- Call `backupTables()` as the **first** statement in `up()`.
- Have `down()` that calls `restoreTables()` and **nothing else**.
- Backup table naming: `z_<ticket>_<original_table>`.

New-table-only migrations (like POCOR-9694's `tasks`/`task_jobs`/`task_failures`) skip
the backup; their `down()` simply drops the new tables.

**Why.** OpenEMIS has been reverted in production before. Recovery requires every migration
to be cleanly reversible without losing user-created rows.

**Apply by.** Follow the template in CLAUDE.md "Migrations" section. Never write a
`down()` that contains `// no-op` or `// not reversible`.

---

## When this document changes

- Phase 2 (Core v6) will add rules around the OpenEMIS MCP server boundary,
  Angular v20 frontend conventions, and removal of the dual-write (when the
  abstraction tables become primary).
- Until then, this is the standing reference for any PR touching async/background work.
- Tickets that materially change one of the rules above must update this file in the
  same commit.

---

## Cited code (POCOR-9694 — Phase 1 reference implementation)

- `api/app/Console/Commands/OpenemisCoreRunCommand.php` — single-cron entry-point
- `api/app/Services/OpenemisRuntime/TasksRecorder.php` — dual-write helper
- `api/app/Models/Api5/Tasks.php`, `TaskJobs.php`, `TaskFailures.php` — abstraction tables
- `api/app/Http/Controllers/Administration/SystemRuntimeController.php` — Runtime UI backend
- `api/app/Repositories/WebhookRepository.php` — `exec()` removal example
- `docker-config/cron/openemis-core` — production cron config
- `config/Migrations/20260508143848_POCOR9694.php` — abstraction tables migration
