# POCOR-9509 — Alerts Infrastructure Port and New Alert Types

**Release date:** 2026-04-15 · **Audience:** Ministry IT staff, deployment engineers.

This release ports the OpenEMIS alerts subsystem from legacy CakePHP shell scripts to Laravel artisan commands, adds five new alert types, and introduces an Alert Queue screen with mass-delete support. Follow the deployment checklist below to have alerts running safely within 15 minutes.

---

## 1. What Changed

- **Five new alert types** — Case Escalation, License Validity, License Renewal, Scholarship Application, Scholarship Disbursement. Each ships with a dedicated artisan command and default recipient-resolver logic.
- **New Alert Queue screen** and **mass-delete** on both Alert Queue and Alert Logs. Administrators can monitor pending/sent/failed messages and bulk-delete entries produced by a misconfigured rule.
- **Laravel runtime with working-hours throttling** — all 14 alert commands live under `api/app/Console/Commands/Alerts/`. The `ALERTS_PROCESS_LIMIT` environment variable caps messages per scheduler tick, and `Kernel.php` restricts dispatch to weekdays and working hours.
- **Critical fixes (2026-04-16):** Fixed `self::FAILURE`/`self::SUCCESS` constant usage across all 13 alert commands (removed redundant `use Illuminate\Console\Command` imports from subclasses); standardized all alert templates to dot-notation placeholders (`${student.name}` etc.) — updated `alert_rules` DB template for StudentStatus from the old underscore format; fixed `AlertRetirementWarningCommand` fatal error (`Command::FAILURE` without import); enabled RetirementWarning in migration with `INSERT IGNORE`; added `UNIQUE` indexes on `alerts.name` and `alerts.process_name` with automatic deduplication (keeps lowest id per duplicate); cleaned TEMP-LOG debug calls from all command classes.

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
3. **Clear caches** for both frameworks:
   ```bash
   docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && php bin/cake.php cache clear_all"
   docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core/api && php artisan config:cache && php artisan cache:clear"
   ```
4. **Set throttle** in `api/.env` (start conservative — free-tier mail providers often cap at 20 msg/min):
   ```env
   ALERTS_PROCESS_LIMIT=20
   ```
   Then rerun `php artisan config:cache`. Use `0` to pause dispatch without disabling the cron job.
5. **Install the host crontab** (every minute; the Laravel scheduler handles the rest):
   ```cron
   * * * * *  cd /var/www/html/emis/core/api && php artisan schedule:run >> /dev/null 2>&1
   ```
6. **Restrict to working hours** in `api/app/Console/Kernel.php` using `->weekdays()->between('08:00', '17:00')`. Adjust the window to local working hours; swap `weekdays()` for `->days([0,1,2,3,4])` where the weekend falls on Friday–Saturday.
7. **Verify anonymisation** before enabling on production-copy data. See [Manual §14.4](POCOR-9509/MANUAL.md#14-testing-and-dry-run-procedures) and run the two `SELECT COUNT(*)` queries.
8. **Smoke-test** one scheduled alert on a dev/test database:
   ```bash
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core/api && php artisan alerts:check-and-queue --user_id=1 --sync"
   ```

> **Warning:** Never run step 8 on a database containing real contact data unless you have completed step 7. One misconfigured rule can dispatch thousands of real emails or SMS messages.

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
| `ALERTS_PROCESS_LIMIT` | `api/.env` | Max messages per scheduler tick. Default `20`. Set `0` to pause. |
| `->weekdays()->between(...)` | `api/app/Console/Kernel.php` | Restrict dispatch to working hours. Required for production. |
| `* * * * * php artisan schedule:run` | Host crontab | Drives the Laravel scheduler. Every minute. |
| `Alert.AlertQueue` | CakePHP Table Registry | Plugin alias for queue access. Not `AlertQueue`. |
| `NON_IMPLEMENTED_ALERTS` | `plugins/Alert/src/Model/Table/AlertsTable.php` | Contains only `StaffAttendance` after this release. |

---

## 5. Files Changed

### Files Changed Summary
- **Modified:** 14 files (13 alert commands + migration)
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

---

*POCOR-9509 · 2026-04-15*
