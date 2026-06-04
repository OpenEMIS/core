# POCOR-9509 — Alerts Infrastructure Port and New Alert Types

**Release date:** 2026-04-15 · **Audience:** Ministry IT staff, deployment engineers.

This release ports the OpenEMIS alerts subsystem from legacy CakePHP shell scripts to Laravel artisan commands, adds five new alert types, and introduces an Alert Queue screen with mass-delete support. Follow the deployment checklist below to have alerts running safely within 15 minutes.

---

## 1. What Changed

- **Five new alert types** — Case Escalation, License Validity, License Renewal, Scholarship Application, Scholarship Disbursement. Each ships with a dedicated artisan command and default recipient-resolver logic.
- **New Alert Queue screen** and **mass-delete** on both Alert Queue and Alert Logs. Administrators can monitor pending/sent/failed messages and bulk-delete entries produced by a misconfigured rule.
- **Laravel runtime with working-hours throttling** — all 14 alert commands live under `api/app/Console/Commands/Alerts/`. The `ALERTS_PROCESS_LIMIT` environment variable caps messages per scheduler tick, and `Kernel.php` restricts dispatch to weekdays and working hours.
- **Critical fixes (2026-04-16):** Fixed `self::FAILURE`/`self::SUCCESS` constant usage across all 13 alert commands (removed redundant `use Illuminate\Console\Command` imports from subclasses); standardized all alert templates to dot-notation placeholders (`${student.name}` etc.) — updated `alert_rules` DB template for StudentStatus from the old underscore format; fixed `AlertRetirementWarningCommand` fatal error (`Command::FAILURE` without import); enabled RetirementWarning in migration with `INSERT IGNORE`; added `UNIQUE` indexes on `alerts.name` and `alerts.process_name` with automatic deduplication (keeps lowest id per duplicate); cleaned TEMP-LOG debug calls from all command classes.
- **Process completion fix (2026-04-24):** Fixed `system_processes` rows getting stuck at `status=1` when a second artisan command spawns for the same alert and finds no pending items — now always calls `completeProcess()` even when `getPendingItems()` returns empty (prevents concurrency guard `activeCount >= 2` from silently blocking all future alert spawns).
- **Duplicate-check status filter (2026-04-24):** Added `status=1` filter to the duplicate-check query in `triggerAlertSystemProcess()` — now only active (status=1) processes block new triggers. Previously, completed (status=3) and failed (status=-2) rows also matched the checksum, which prevented each new absence from triggering its own alert.
- **Early-return remnants fix (2026-04-27):** `AlertCommandBase::prepareContext()` and per-command `handle()` had five paths that returned `FAILURE` *before* `completeProcess()` or `failProcess()` was ever called (missing `--user_id`/`--rule_id`, rule not found, no roles assigned, missing `--student_id`/`--academic_period_id`, staff record not found). All five now route through a new `markProcessFailed(string $reason)` helper that sets `status=-2`, fills `end_date`, and writes a `system_errors` row with `code='ALERT_FAIL'` for forensic inspection. The "no roles assigned" path calls `completeProcess()` instead — misconfiguration is not an error.
- **Global stale-process sweep (2026-04-27):** `CheckAndQueueAlerts::handle()` now opens with a global `UPDATE system_processes SET status=-1 WHERE status IN (1,2) AND created <= NOW()-INTERVAL 1 DAY`. The 10-minute `alerts:check` cron means any stale row gets reaped within ~24h10m worst case, regardless of which features are still active. Defence in depth against any uncaught path that escapes the per-command terminal write.
- **Laravel queue refactor (2026-04-27):** Replaced per-trigger `exec("php artisan alerts:<feature> …")` with a queue-mediated flow. `AlertLogsTable::triggerAlertCommand()` now calls a new thin `alerts:enqueue` artisan command (~150ms boot+dispatch+exit), which enqueues a `RunAlertJob` queueable. A `php artisan queue:work --queue=alerts` daemon drains the `jobs` table at controlled rate, calling the existing per-feature artisan command via `Artisan::call`. The hardcoded `>= 2` (later `>= 5`) concurrency cap was removed — backpressure now comes from queue depth + worker count, not row counting. Why: at country scale (5k schools × 30 students × N rules in a 90-min window) the previous design exhausted the PHP-FPM pool. Full rationale in `tmp/POCOR-9509/laravel-queue-rationale.{md,jira.md}`.
- **StudentAbsence: `calculate_daily_attendance` rule honour (2026-04-30):** `AlertStudentAbsenceCommand::getPendingItems()` now reads `config_items.code='calculate_daily_attendance'`. When value=2 ("Mark present if one or more records present"), drops dates where `absent_count < marked_count` — i.e. any present indicator anywhere in the day's marked atomic units pulls the day out of `total_days`. When value=1, keeps current behaviour. Check applies uniformly across DAY/SUBJECT/DAY_AND_SUBJECT modes since `student_attendance_marked_records` is keyed on both `period` and `subject_id`. `total_times` (raw absence-row count) is unchanged — only `total_days` is adjusted. Multi-period enqueue dedup deferred pending design clarification on day-completion semantics.

---

## 2. Deployment Checklist

1. **Pull the branch:**
   ```bash
   git pull origin POCOR-9509
   ```
2. **Run migrations** (adds the POCOR-9509 migration file; no schema change to `alert_queue`):
   ```bash
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core && php bin/cake.php migrations migrate"
   ```
3. **Clear caches** for both frameworks (and **reload PHP-FPM / restart container** so OPcache drops the old bytecode — without this, the early-return / queue fixes from 2026-04-27 will not run even though the files on disk are correct):
   ```bash
   docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && php bin/cake.php cache clear_all"
   docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core/api && php artisan optimize:clear"
   # On the host or inside the container, reload PHP-FPM (or restart the container)
   sudo systemctl reload php-fpm   # or:  docker compose restart poe-application
   ```
4. **Set throttle** in `api/.env` (start conservative — free-tier mail providers often cap at 20 msg/min):
   ```env
   ALERTS_PROCESS_LIMIT=20
   ```
   Then rerun `php artisan config:cache`. Use `0` to pause dispatch without disabling the cron job.
5. **Set up the Laravel queue (NEW — 2026-04-27).** Alerts now dispatch via Laravel's queue (`jobs` / `failed_jobs` tables — already created by stock Laravel migrations, no new schema). Without this step `jobs` rows pile up forever and no alerts are sent.

   ```env
   # api/.env
   QUEUE_CONNECTION=database
   ```

   Then start a long-running queue worker as a daemon. The simplest production setup is a systemd unit:

   ```ini
   # /etc/systemd/system/openemis-queue.service
   [Unit]
   Description=OpenEMIS Laravel Queue Worker (alerts queue)
   After=mysql.service php-fpm.service

   [Service]
   User=www-data
   Group=www-data
   Restart=always
   RestartSec=5
   ExecStart=/usr/bin/php /var/www/html/emis/core/api/artisan queue:work --queue=alerts --tries=3 --timeout=120 --sleep=3

   [Install]
   WantedBy=multi-user.target
   ```

   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable --now openemis-queue
   sudo systemctl status openemis-queue
   ```

   Inspect:
   ```sql
   SELECT COUNT(*) FROM jobs WHERE queue='alerts';   -- pending
   SELECT id, exception FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
   ```

   Retry failed jobs after fixing the underlying issue:
   ```bash
   php artisan queue:retry all
   ```

6. **One-time cleanup of pre-existing stuck rows** (run once after the deploy):
   ```sql
   UPDATE system_processes
      SET status = -1, end_date = NOW(), modified = NOW()
    WHERE status = 1 AND end_date IS NULL;
   ```
   The 1-day stale-process sweep would catch them on the next `alerts:check` tick anyway, but no need to wait.

7. **Install two direct cron entries** for the alert checker and sender.

   > ### ⚠️ CRITICAL — Read before touching cron
   >
   > **Wrong user = broken logs and silent failures.**
   > The cron job MUST run as the same OS user as the web server process.
   > On Ubuntu/Debian that is `www-data`. On CentOS/RHEL it is `apache`.
   > Running as `root` or your personal SSH user will create log files owned by the wrong user,
   > causing subsequent web-triggered artisan calls to crash with permission errors that are
   > nearly impossible to diagnose after the fact.
   >
   > **No `flock` = duplicate sends.**
   > Without `flock -n`, if a run takes longer than the cron interval the next fire overlaps.
   > Two simultaneous `alerts:send` processes will each claim the same pending rows and send
   > duplicate emails/SMS to every recipient. `flock -n` skips the second run entirely.
   >
   > **No `timeout` = runaway process.**
   > A DB deadlock or network hang in `alerts:check` can leave the process running for hours,
   > holding the `flock` lock and blocking all subsequent runs until the server is rebooted.
   > Always wrap with `timeout <seconds>` matching your overlap lock window.
   >
   > **Do NOT use `schedule:run` in the crontab for alerts.**
   > If `ALERT_CHECK_DAILY` / `ALERT_SEND_DAILY` are both `true` in `.env` AND you also add
   > a `* * * * * php artisan schedule:run` cron entry, every command fires twice — once from
   > the Laravel scheduler and once from the direct cron entry. Use one or the other, never both.
   >
   > **Times are server OS timezone, not UTC.**
   > Run `timedatectl` on the server to confirm the active timezone before setting times.

   Create `/etc/cron.d/openemis-alerts` (requires root, file is owned by root, no `sudo crontab -e`):
   ```bash
   sudo tee /etc/cron.d/openemis-alerts << 'EOF'
   # OpenEMIS alert checker — populates alert_queue (off-peak, weekdays)
   0 2 * * 1-5 www-data timeout 21600 flock -n /tmp/alerts-check.lock bash -c "cd /var/www/html/emis/core/api && php artisan alerts:check --sync >> /var/log/alerts-check.log 2>&1"

   # OpenEMIS alert sender — drains alert_queue and fires email/SMS (morning, weekdays)
   0 7 * * 1-5 www-data timeout 7200 flock -n /tmp/alerts-send.lock bash -c "cd /var/www/html/emis/core/api && php artisan alerts:send --limit=50 >> /var/log/alerts-send.log 2>&1"
   EOF
   sudo chmod 644 /etc/cron.d/openemis-alerts
   ```
   Adjust `0 2` and `0 7` to local off-peak and morning hours. The `timeout` values are 6 hours for
   the checker and 2 hours for the sender — size them to your largest expected run time.
8. **Verify anonymisation** before enabling on production-copy data. See [Manual §14.4](POCOR-9509/MANUAL.md#14-testing-and-dry-run-procedures) and run the two `SELECT COUNT(*)` queries.
9. **Smoke-test** one scheduled alert on a dev/test database, then drain the queue with a one-shot worker to confirm the new path works end-to-end:
   ```bash
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core/api && php artisan alerts:check --user_id=1 --sync"
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core/api && php artisan queue:work --queue=alerts --once --stop-when-empty"
   ```
   Then check `system_processes` rows transitioned to `status=3` and `failed_jobs` is empty.

> **Warning:** Never run step 9 on a database containing real contact data unless you have completed step 8. One misconfigured rule can dispatch thousands of real emails or SMS messages.

---

## 3. Getting Started

| Topic | Manual Section | Language |
|-------|----------------|----------|
| Enable or disable an alert type | §4 Managing Alert Schedules | [EN](POCOR-9509/MANUAL.md#4-managing-alert-schedules) · [RU](POCOR-9509/MANUAL_RU.md#4-managing-alert-schedules) · [HI](POCOR-9509/MANUAL_HI.md#4-managing-alert-schedules) · [AR](POCOR-9509/MANUAL_AR.md#4-managing-alert-schedules) |
| Create an alert rule | §5 Alert Rules | [EN](POCOR-9509/MANUAL.md#alert-rules-configuring-what-to-send) · [RU](POCOR-9509/MANUAL_RU.md#5-alert-rules--configuring-what-to-send) · [HI](POCOR-9509/MANUAL_HI.md#5-alert-rules--configuring-what-to-send) · [AR](POCOR-9509/MANUAL_AR.md#5-alert-rules--configuring-what-to-send) |
| Look up a placeholder | §6 Placeholders | [EN](POCOR-9509/MANUAL.md#6-placeholders) · [RU](POCOR-9509/MANUAL_RU.md#6-placeholders) · [HI](POCOR-9509/MANUAL_HI.md#6-placeholders) · [AR](POCOR-9509/MANUAL_AR.md#6-العناصر-النائبة) |
| Configure a threshold | §7 Thresholds | [EN](POCOR-9509/MANUAL.md#7-thresholds) · [RU](POCOR-9509/MANUAL_RU.md#7-thresholds) · [HI](POCOR-9509/MANUAL_HI.md#7-thresholds) · [AR](POCOR-9509/MANUAL_AR.md#7-الحدود-الدنيا) |
| Reference an alert type | §8 Alert Types Reference | [EN](POCOR-9509/MANUAL.md#8-alert-types-reference) · [RU](POCOR-9509/MANUAL_RU.md#8-alert-types-reference) · [HI](POCOR-9509/MANUAL_HI.md#8-alert-types-reference) · [AR](POCOR-9509/MANUAL_AR.md#8-مرجع-أنواع-التنبيهات) |
| Throttle sending rate | §13.2 `ALERTS_PROCESS_LIMIT` | [EN](POCOR-9509/MANUAL.md#13-operational-configuration) |
| Safe dev-DB testing | §14.3 Safe-Suffix Trick | [EN](POCOR-9509/MANUAL.md#14-testing-and-dry-run-procedures) |
| Troubleshoot a missing email | §15 Troubleshooting | [EN](POCOR-9509/MANUAL.md#15-troubleshooting) · [RU](POCOR-9509/MANUAL_RU.md#15-troubleshooting) |

---

## 4. Key Configuration

| Key | Location | Purpose |
|-----|----------|---------|
| `ALERTS_PROCESS_LIMIT` | `api/.env` | Max messages per sender run. Default `20`. Set `0` to pause sending. |
| `ALERT_CHECK_DAILY` | `api/.env` | `true` = run `alerts:check` once daily (default). `false` = run every hour. |
| `ALERT_CHECK_DAILY_TIME` | `api/.env` | Time for daily check run in `HH:MM` format. Default `02:00`. Ignored when `ALERT_CHECK_DAILY=false`. |
| `ALERT_SEND_DAILY` | `api/.env` | `true` = run `alerts:send` once daily (default). `false` = run every hour. |
| `ALERT_SEND_DAILY_TIME` | `api/.env` | Time for daily send run in `HH:MM` format. Default `07:00`. Ignored when `ALERT_SEND_DAILY=false`. |
| `Alert.AlertQueue` | CakePHP Table Registry | Plugin alias for queue access. Not `AlertQueue`. |
| `NON_IMPLEMENTED_ALERTS` | `plugins/Alert/src/Model/Table/AlertsTable.php` | Contains only `StaffAttendance` after this release. |
| `QUEUE_CONNECTION` | `api/.env` | **Must be `database`** after the 2026-04-27 queue refactor (was `sync`). Without this, `RunAlertJob::dispatch()` runs inline in the trigger path and blocks. |

After changing any `.env` value always run:
```bash
cd /var/www/html/emis/core/api && php artisan config:cache
```

---

## 5. Files Changed

### Files Changed Summary
- **Modified:** 16 files (14 alert commands + migration + AlertLogsTable)
- **Added:** 0 files
- **Removed:** 0 files

### Detailed Changes

| Area | Path | Summary |
|------|------|---------|
| Artisan commands | `api/app/Console/Commands/Alerts/` | 14 commands covering every alert type |
| Command fixes (2026-04-16) | 13 Alert*Command.php + AlertCommandBase | `self::FAILURE`/`self::SUCCESS` everywhere; removed redundant `use Illuminate\Console\Command` from subclasses |
| StudentStatus placeholders | `AlertStudentStatusChangeCommand.php` + `alert_rules` DB | Standardized to dot notation — updated DB template from old underscore format |
| RetirementWarning | `AlertRetirementWarningCommand.php` + migration | Fixed `Command::FAILURE` fatal error; `user.` prefix for placeholders; added to migration |
| alerts table integrity | `config/Migrations/20260415030200_POCOR9509.php` | UNIQUE indexes on `alerts.name` and `alerts.process_name`; deduplication DELETE before indexing |
| CakePHP Alert plugin | `plugins/Alert/src/Model/Table/` | Consolidated `AlertQueueTable`; cleaned `NON_IMPLEMENTED_ALERTS` |
| Behaviors | `src/Model/Behavior/AlertQueueBehavior.php` | Uses plugin alias `Alert.AlertQueue` |
| Angular frontend | `frontend/src/` → `webroot/js/angular/dist/` | Alert Queue screen, mass-delete controls |
| Migration | `config/Migrations/20260415030200_POCOR9509.php` | Backup tables for `institution_students_report_cards`, `security_functions`; added RetirementWarning |
| Debug cleanup | `plugins/Institution/src/Model/Table/StudentsTable.php` | Removed TEMP-LOG calls |
| Process completion fix (2026-04-24) | `api/app/Console/Commands/Alerts/AlertCommandBase.php` | Call `completeProcess()` even when `getPendingItems()` is empty — prevents `system_processes` rows stuck at status=1 |
| Duplicate-check status filter (2026-04-24) | `plugins/Alert/src/Model/Table/AlertLogsTable.php` | Added `status=1` filter to `triggerAlertSystemProcess()` duplicate-check query — only active processes block new triggers |
| Early-return remnants fix (2026-04-27) | `api/app/Console/Commands/Alerts/AlertCommandBase.php` + `AlertStudentAbsenceCommand.php` + `AlertStaffTypeCommand.php` | New `markProcessFailed(string $reason)` helper called from all five early-return paths; "no roles assigned" routes to `completeProcess()` instead of leaking. Forensic SQL for finding remaining stale rows lives in the same memo as the rationale. |
| Global stale-process sweep (2026-04-27) | `api/app/Console/Commands/CheckAndQueueAlerts.php` | Aborts any `system_processes` row at status 1/2 older than 1 day at the top of every `alerts:check` run; ~24h10m worst case for stuck rows. |
| Queue refactor (2026-04-27) | NEW `api/app/Jobs/RunAlertJob.php` (76 LOC) + NEW `api/app/Console/Commands/Alerts/EnqueueAlertCommand.php` (46 LOC) + `plugins/Alert/src/Model/Table/AlertLogsTable.php` (-44/+20) + `api/.env` + `api/.env.example` | Trigger path now enqueues to Laravel's `jobs` table (~150ms exec); `queue:work --queue=alerts` daemon drains. Concurrency cap removed. Per-feature artisan commands kept — `RunAlertJob` invokes them via `Artisan::call`, so all recipient/placeholder/`alert_queue` logic and the `system_processes` 1→2→3/-2 lifecycle stay untouched. |
| StudentAbsence rule honour (2026-04-30) | `api/app/Console/Commands/Alerts/AlertStudentAbsenceCommand.php` | `getPendingItems()` reads `config_items.code='calculate_daily_attendance'`. Value=2 drops dates with `absent_count < marked_count` (day has present indicator). Value=1 keeps current behaviour. Adjusts `total_days` only; `total_times` stays untouched. Multi-period enqueue dedup deferred pending design clarification. |
| Removed | `src/Model/Table/AlertsQueueTable.php` | Duplicate replaced by plugin table |

---

## 6. Troubleshooting Quick Reference

| Symptom | One-line Fix |
|---------|--------------|
| `Table 'Alert.AlertQueue' not found` | Verify `plugins/Alert/src/Model/Table/AlertQueueTable.php` exists; clear caches. |
| Alert rule enabled but never fires | Check that at least one role is assigned and the alert type frequency is not `Never`. |
| Placeholder tokens appear literally in sent emails | Confirm the token spelling against [Manual §6](POCOR-9509/MANUAL.md#6-placeholders) — tokens are case-sensitive. |
| Queue backing up, messages not sending | Inspect `ALERTS_PROCESS_LIMIT`; if `0`, restore a positive value and rerun `php artisan config:cache`. |
| Duplicate alerts in queue | Expected when multiple rules match the same record. See [Manual §5.4](POCOR-9509/MANUAL.md#alert-rules-configuring-what-to-send). |
| Markings happen but no alerts dispatched (2026-04-27 onward) | `queue:work --queue=alerts` daemon is not running. Check `systemctl status openemis-queue` and `SELECT COUNT(*) FROM jobs WHERE queue='alerts'`. If the count is climbing, the worker is down. |
| `system_processes` rows stuck at `status=1` after deploy | OPcache still holds old bytecode. Run `php artisan optimize:clear` and reload PHP-FPM (or restart the container). The 2026-04-27 `markProcessFailed` fix only applies to newly spawned PHP processes. |
| Repeated rule (e.g. id=1) keeps creating stuck rows | Rule has identical text across recipients (no `${student.name}` etc.) — alert dedup at `alert_logs` insert silently skips. Either disable the rule (`UPDATE alert_rules SET enabled=0 WHERE id=1`) or add per-recipient placeholders to its subject + message. |

Advanced diagnostics: `api/storage/logs/laravel.log`, `logs/hin-debug.log`, `logs/alert_<command>.log`, `logs/system_processes/<id>.log`.

To pause all dispatch immediately:
```bash
# .env → ALERTS_PROCESS_LIMIT=0
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan config:cache"
```

To purge a runaway queue (use with care):
```sql
DELETE FROM alert_queue WHERE status = 0;
```

---

## 7. Full Documentation

| Document | Path |
|----------|------|
| Administrator Manual (English) | [POCOR-9509/MANUAL.md](POCOR-9509/MANUAL.md) |
| Руководство администратора (Russian) | [POCOR-9509/MANUAL_RU.md](POCOR-9509/MANUAL_RU.md) |
| प्रशासक मैनुअल (Hindi) | [POCOR-9509/MANUAL_HI.md](POCOR-9509/MANUAL_HI.md) |
| دليل المسؤول (Arabic) | [POCOR-9509/MANUAL_AR.md](POCOR-9509/MANUAL_AR.md) |
| Technical Implementation Guide | [POCOR-9509/ALERTS_GUIDE.md](POCOR-9509/ALERTS_GUIDE.md) |
| Threshold Configuration Reference | [POCOR-9509/thresholds.md](POCOR-9509/thresholds.md) |
| Laravel Queue Rationale (2026-04-27) | `tmp/POCOR-9509/laravel-queue-rationale.md` (Markdown) · `.jira.md` (Jira wiki) — explains why we routed alert dispatch through Laravel's existing `jobs` table at country scale |

---

## 8. Future Improvements

### RetirementWarning — per-staff deduplication
Currently `AlertRetirementWarningCommand` re-queries all country staff on every daily run and re-queues
anyone still above the retirement age threshold. The send-time checksum dedup in `ProcessAlertQueue`
prevents re-sending identical messages, but the query still runs and `alert_queue` accumulates rows daily.

**Recommended fix (next version):**
Add a `subject_id` column (INT, nullable) to `alert_logs` to record which staff member triggered
the alert. After sending, a `(feature, subject_id)` lookup can skip already-alerted staff entirely
at query time — no re-queuing, no growing table.

### RetirementWarning — scheduling
Should run once per day at night (e.g. `02:00`), since it scans all institution staff across the
country. Daytime runs waste PHP-FPM workers. Set `frequency = 'Daily'` in the `alerts` table and
point the cron entry to run at `02:00`.

### RetirementWarning — school-based pre-filter
Recipients are resolved per institution (HR manager, principal — whoever holds the configured roles).
Before scanning all staff, pre-select only institutions that have at least one staff member approaching
retirement age, then resolve recipients only for those institutions. This avoids querying contacts for
every school in the country when only a handful have retiring staff.

### Direct INSERT into Laravel `jobs` table (queue path optimization)
The 2026-04-27 queue refactor still spawns one fast PHP-FPM process per trigger to call
`alerts:enqueue` (~150ms each). At very high burst (5k+ markings within a few seconds) those quick
processes still accumulate. A v2 ticket can replace the `exec()` in
`AlertLogsTable::triggerAlertCommand()` with a direct `INSERT INTO jobs` from CakePHP, eliminating
the PHP-FPM hop entirely. ~50 LOC of Laravel-payload-format helper code with minor coupling to
Laravel queue internals (mitigatable with a round-trip unit test). Only worth doing once we observe
real burst loads above a few hundred triggers/second.

### Laravel Horizon dashboard
If the deployment moves to Redis as the queue driver, Laravel Horizon gives a live worker UI,
metrics, and per-queue throughput without any code changes.

### Per-priority queues
`alerts:high` / `alerts:bulk` named queues if there's ever a need to prioritise some rules over
others. The current refactor uses a single `alerts` queue; splitting is a one-line change in
`RunAlertJob::__construct` plus an additional `queue:work --queue=alerts:high,alerts` daemon.

---

*POCOR-9509 · 2026-04-15 (initial) · 2026-04-27 (queue refactor + early-return + stale-sweep)*
