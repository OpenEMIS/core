# OpenEMIS Alerts — Technical Implementation Guide

> **Branch:** POCOR-9509 · **Last updated:** 2026-03-13

This document is the technical complement to [README.md](README.md). It covers the internal architecture, artisan command inventory, threshold deep-dive, and activation/testing procedures for developers and advanced administrators.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Two Dispatch Paths](#two-dispatch-paths)
3. [Three Command Maps](#three-command-maps)
4. [Full Command Inventory](#full-command-inventory)
5. [Threshold Deep-Dive](#threshold-deep-dive)
6. [Activation Checklist](#activation-checklist)
7. [Testing Any Alert Directly](#testing-any-alert-directly)
8. [Not Implemented](#not-implemented)

---

## Architecture Overview

All alert processing has been ported from CakePHP daemon shells to clean Laravel artisan commands under `api/app/Console/Commands/Alerts/`.

The base class `AlertCommandBase` provides a template-method pattern:
- Abstract methods `getPendingItems()` and `fillPlaceholders()` must be implemented per command
- `runFeatureAlert()` orchestrates the full flow: fetch rule → fetch items → resolve recipients → queue delivery rows → update `system_processes`

```
CakePHP afterSave  ───────────►  AlertLogsTable::triggerLaravelAlertFromCakePHP()
                                          │
                                          ▼
                                  artisan command dispatched
                                          │
CheckAndQueueAlerts (cron) ──────►        ▼
                                  AlertCommandBase::handle()
                                          │
                                  ┌───────┴────────┐
                                  │                │
                            getPendingItems()  resolveRecipients()
                                  │                │
                                  └───────┬────────┘
                                          │
                                  alert_queue rows inserted
                                          │
                                  ProcessAlertQueue sends email/SMS
```

---

## Two Dispatch Paths

| Path | Trigger | Commands |
|------|---------|---------|
| **Event-based** | CakePHP model `afterSave` → `AlertLogsTable::triggerLaravelAlertFromCakePHP()` | StudentAbsence, StudentAdmission, StudentEnrolment, StudentStatusChange |
| **Scheduled** | `alerts:check` (cron / hourly) | RetirementWarning, StaffEmployment, StaffLeave, StaffType, SystemUpdates, CaseEscalation, LicenseValidity, LicenseRenewal, ScholarshipApplication, ScholarshipDisbursement |

---

## Three Command Maps

When adding a new alert command, three places must be updated:

### 1. `AlertLogsTable::triggerAlertCommand()` (CakePHP)
`plugins/Alert/src/Model/Table/AlertLogsTable.php`

Maps `process_name` → artisan command for event-based alerts dispatched from CakePHP.

### 2. `CheckAndQueueAlerts::queueAlertCommand()` (Laravel)
`api/app/Console/Commands/CheckAndQueueAlerts.php`

Maps `process_name` → artisan command for **scheduled** alerts only. Event-based commands are commented out in this map.

### 3. `AlertTriggerService::triggerAlertCommand()` (Laravel)
`api/app/Services/AlertTriggerService.php`

Maps process names for event-based commands triggered from the Laravel side. Only event-based commands belong here.

---

## Full Command Inventory

### Dispatch: Event-Based

#### `alerts:student-absence`
- **File:** `AlertStudentAbsenceCommand.php`
- **Feature:** `StudentAttendance` / process `AlertStudentAbsence`
- **Trigger:** `InstitutionStudentAbsencesTable::afterSave()`
- **Threshold:** simple integer (absence day count)
- **Recipients:** role-based per institution + class
- **Test:**
  ```bash
  php artisan alerts:student-absence \
    --user_id=1 --rule_id=<id> --process_id=0 \
    --student_id=<id> --academic_period_id=<id>
  ```

#### `alerts:student-admission`
- **File:** `AlertStudentAdmissionCommand.php`
- **Feature:** `StudentAdmission` / process `AlertStudentAdmission`
- **Trigger:** `StudentAdmissionTable::afterSave()`
- **Threshold:** none
- **Recipients:** role-based (global)
- **Test:**
  ```bash
  php artisan alerts:student-admission --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:student-enrolment`
- **File:** `AlertStudentEnrolmentCommand.php`
- **Feature:** `StudentEnrolment` / process `AlertStudentEnrolment`
- **Trigger:** `StudentEnrolmentTable::afterSave()`
- **Threshold:** none
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:student-enrolment --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:student-status-change`
- **File:** `AlertStudentStatusChangeCommand.php`
- **Feature:** `StudentStatus` / process `AlertStudentStatus`
- **Trigger:** student status update `afterSave()`
- **Threshold:** `{"old_status_id": X, "new_status_id": Y}`
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:student-status-change --user_id=1 --rule_id=<id> --process_id=0
  ```

---

### Dispatch: Scheduled (cron via `alerts:check`)

#### `alerts:retirement-warning`
- **File:** `AlertRetirementWarningCommand.php`
- **Feature:** `RetirementWarning` / process `AlertRetirementWarning`
- **Threshold:** `{"value": N}` — days before retirement date
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:retirement-warning --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:staff-employment`
- **File:** `AlertStaffEmploymentCommand.php`
- **Feature:** `StaffEmployment` / process `AlertStaffEmployment`
- **Threshold:** `{"value": N}` — days before employment end date
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:staff-employment --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:staff-leave`
- **File:** `AlertStaffLeaveCommand.php`
- **Feature:** `StaffLeave` / process `AlertStaffLeave`
- **Threshold:** `{"value": N, "staff_leave_type": X}` — days before approved leave end date; optional leave type filter
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:staff-leave --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:staff-type`
- **File:** `AlertStaffTypeCommand.php`
- **Feature:** `StaffType` / process `AlertStaffType`
- **Threshold:** `{"value": N, "staff_type_id": X}` — days before relevant date; staff type to monitor
- **Recipients:** role-based per institution
- **Test:**
  ```bash
  php artisan alerts:staff-type --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:system-updates`
- **File:** `AlertSystemUpdatesCommand.php`
- **Feature:** `SystemUpdates` / process `AlertSystemUpdates`
- **Threshold:** none
- **Recipients:** role-based (global, no institution)
- **Note:** Only alert with `Daily` frequency enabled by default
- **Placeholders:**
  | Placeholder | Example | Source |
  |-------------|---------|--------|
  | `${new_version}` | `5.7.0` | Version API |
  | `${release_date}` | `11.12.2025` | Version API `date_released` (formatted `dd.mm.yyyy`) |
  | `${current_version}` | `5.5.0` | `config_items.db_version` |
- **Example message:**
  > `Version ${new_version} was released on ${release_date}. Your current version is ${current_version}. Please update the system.`
- **Test:**
  ```bash
  php artisan alerts:system-updates --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:case-escalation` ⭐ New in POCOR-9509
- **File:** `AlertCaseEscalationCommand.php`
- **Feature:** `CaseEscalation` / process `AlertCaseEscalation`
- **Threshold:** `{"value": N, "workflow_steps": [step_id1, ...]}`
  - `value` = days a case must be open (since created) before escalating
  - `workflow_steps` = array of `workflow_steps.id` to monitor (typically "Open" step)
- **Logic:** Finds `institution_cases` where `status_id IN (workflow_steps)` AND `modified IS NULL` AND `modified_user_id IS NULL` AND `DATEDIFF(NOW(), created) > value`
- **Recipients:** role-based per institution
- **Find workflow step IDs:**
  ```sql
  SELECT ws.id, ws.name, w.name FROM workflow_steps ws
  JOIN workflows w ON w.id = ws.workflow_id
  WHERE w.name LIKE '%Case%';
  ```
- **Test:**
  ```bash
  php artisan alerts:case-escalation --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:license-validity` ⭐ New in POCOR-9509
- **File:** `AlertLicenseValidityCommand.php`
- **Feature:** `LicenseValidity` / process `AlertLicenseValidity`
- **Threshold:** `{"value": N, "license_type": X, "condition": C}`
  - `condition` 1 = expiring within value days · 2 = expired within last value days
- **Logic:** Queries `staff_licenses`, then expands to one item per `institution_staff` ASSIGNED record
- **Recipients:** role-based per institution (via `institution_staff` lookup)
- **Test:**
  ```bash
  php artisan alerts:license-validity --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:license-renewal` ⭐ New in POCOR-9509
- **File:** `AlertLicenseRenewalCommand.php`
- **Feature:** `LicenseRenewal` / process `AlertLicenseRenewal`
- **Threshold:** `{"value": N, "license_type": X, "condition": 1, "training_categories": [id1,...], "hour": H}`
- **Logic (two-step):**
  1. Find `staff_licenses` expiring within `value` days
  2. Sum `staff_trainings.credit_hours` within license validity period filtered by `training_categories`
  3. Skip if total hours `>= threshold.hour` — alert only if below requirement
- **Recipients:** role-based per institution (via `institution_staff` ASSIGNED lookup)
- **Test:**
  ```bash
  php artisan alerts:license-renewal --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:scholarship-application` ⭐ New in POCOR-9509
- **File:** `AlertScholarshipApplicationCommand.php`
- **Feature:** `ScholarshipApplication` / process `AlertScholarshipApplication`
- **Threshold:** `{"value": N, "condition": C, "category": "WORKFLOW_CATEGORY"}`
  - `category` = workflow step category (e.g. `"PENDING"`)
- **Logic:** Finds `scholarship_applications` where `scholarships.application_close_date` is within N days AND workflow step category matches
- **Recipients:** ⚠️ DIRECT to the application's `assignee` only — **not** role-based
- **Test:**
  ```bash
  php artisan alerts:scholarship-application --user_id=1 --rule_id=<id> --process_id=0
  ```

#### `alerts:scholarship-disbursement` ⭐ New in POCOR-9509
- **File:** `AlertScholarshipDisbursementCommand.php`
- **Feature:** `ScholarshipDisbursement` / process `AlertScholarshipDisbursement`
- **Threshold:** `{"value": N, "condition": C}`
  - `condition` 1 = before disbursement date · 2 = after disbursement date
- **Logic:** Finds `scholarship_recipient_payment_structure_estimates` matching the date window
- **Recipients:** role-based, **no institution filter** (global roles only)
- **Test:**
  ```bash
  php artisan alerts:scholarship-disbursement --user_id=1 --rule_id=<id> --process_id=0
  ```

---

## Threshold Deep-Dive

### Condition field (used by LicenseValidity, LicenseRenewal, ScholarshipApplication, ScholarshipDisbursement)

| Value | Direction | SQL pattern |
|-------|-----------|-------------|
| `1` | Before date | `DATEDIFF(target_date, NOW()) BETWEEN 0 AND value` |
| `2` | After date | `DATEDIFF(NOW(), target_date) BETWEEN 0 AND value` |

Condition `1` is "upcoming window" (alert before the date). Condition `2` is "overdue window" (alert after the date has passed).

### workflow_steps (CaseEscalation)

The `workflow_steps` array must contain IDs from the `workflow_steps` table, not step names. IDs are deployment-specific. Always query your database before configuring:

```sql
SELECT ws.id, ws.name, w.name AS workflow
FROM workflow_steps ws
JOIN workflows w ON w.id = ws.workflow_id
ORDER BY w.name, ws.name;
```

### training_categories (LicenseRenewal)

Only trainings in the specified categories count towards the `hour` requirement. Categories that are not in the list are ignored, even if the staff member completed training in them. Query:

```sql
SELECT id, name FROM staff_training_categories ORDER BY name;
```

### Multiple condition rules

For alerts that support `condition`, it is valid and recommended to create two separate rules:
- One rule with `condition: 1` for the approaching window
- One rule with `condition: 2` for the overdue window

Both rules reference the same feature key but serve different purposes with different messaging and potentially different audiences.

### staff_leave_type (StaffLeave)

Omitting `staff_leave_type` from the threshold causes the command to fire for all leave types. Including it restricts the alert to a specific leave type only. This allows per-leave-type rules with appropriate messaging.

```sql
SELECT id, name FROM staff_leave_types ORDER BY name;
```

---

## Activation Checklist

To activate any scheduled alert:

1. **Set frequency** in `alerts` table (UI: **Administration → Alerts**)
   - Change from `Never` to `Daily`, `Weekly`, or `Monthly`

2. **Create an alert rule** in `alert_rules` table (UI: **Administration → Alert Rules → Add**):
   - `feature` = feature name exactly as listed in this guide (e.g., `CaseEscalation`)
   - `enabled` = `Yes`
   - `threshold` = JSON per command format (see Full Command Inventory above)
   - `subject` / `message` = templates with `${placeholder}` tokens
   - `security_roles` = assign recipient roles
   - Give the rule a descriptive `name` — you may have multiple rules per feature

3. **Run scheduler** (cron calls this hourly):
   ```bash
   php artisan alerts:check --user_id=1 --sync
   ```
   Or force-run all now for immediate testing:
   ```bash
   php artisan alerts:check --user_id=1 --force --sync
   ```

---

## Testing Any Alert Directly

Run any command directly inside Docker, bypassing the scheduler:

```bash
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan alerts:<command-name> \
   --user_id=1 --rule_id=<alert_rules.id> --process_id=0"
```

Then check the results:
```sql
-- Check what was queued:
SELECT * FROM alert_queue ORDER BY created DESC LIMIT 20;

-- Check process record:
SELECT * FROM system_processes ORDER BY created DESC LIMIT 5;
```

### Preparing Test Data — Dev/Test Databases Only

> ⚠️ **Run these on dev/test databases only.** Never on production.

The alert pipeline resolves recipients from `security_users.email` and `security_users.mobile_number`. On fresh or anonymised databases many users have these fields empty, which means no recipients are resolved and no alerts are dispatched — making end-to-end testing impossible.

The senders have built-in safety blockers to prevent accidental delivery:
- `EmailSender` skips any address ending in `.comz` (logs "Skipped fake email address")
- `SmsSender` skips any number ending in `zz` (logs "Skipped fake phone number")

This means you can fill every user with fake-but-unique values and the full pipeline — queuing, recipient resolution, placeholder replacement, sender — will run completely **without sending a single real email or SMS**.

Run these two queries to populate missing contacts:

```sql
-- Fill missing or invalid emails (no '@') with a fake-but-unique address
UPDATE security_users
SET email = CONCAT(
        IF(REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '') = '', id, REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '')),
        '@gmail.comz'
            )
WHERE email IS NULL OR email NOT LIKE '%@%';

-- Fill missing mobile numbers with a fake-but-unique number
UPDATE security_users
SET mobile_number = CONCAT(
        IF(REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '') = '', id, REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '')),
        'zz'
                    )
WHERE mobile_number IS NULL OR mobile_number = '';
```

**How the values are constructed:**
- `REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '')` strips all non-alphanumeric characters from `openemis_no` (removes dashes, spaces, dots, etc.)
- If the result is empty (e.g. `openemis_no` is `NULL` or pure symbols), `id` is used instead — guaranteed unique and numeric
- The `.comz` / `zz` suffixes are what the senders check to block delivery

After running these, re-run your alert command. Recipients will be resolved, the queue will be populated, and you can verify the full flow up to — but not including — real delivery.

> ⚠️ **If the anonymised database still contains real email addresses or phone numbers** (partial anonymisation, copied from production, etc.) — it is the tester's or deployer's responsibility to verify this before running any alert command. A single test run can dispatch a large volume of messages to real people. Check with:
> ```sql
> SELECT COUNT(*) FROM security_users WHERE email NOT LIKE '%@%.comz' AND email LIKE '%@%';
> SELECT COUNT(*) FROM security_users WHERE mobile_number IS NOT NULL AND mobile_number NOT LIKE '%zz';
> ```
> If either returns non-zero, anonymise those rows first or disconnect the mail/SMS provider.

### Cron Schedule — Respect Working Hours

The alert queue is processed by `alerts:send` every minute via Laravel's scheduler. **The system scheduler itself should be restricted to working hours and working days.** A misconfigured schedule will deliver emails and SMS at 3 am on a Monday or on a Saturday — disruptive and damaging to user trust.

The standard Laravel scheduler cron entry (runs every minute, all day):
```cron
* * * * *  cd /var/www/html/emis/core/api && php artisan schedule:run >> /dev/null 2>&1
```

To restrict to working hours in `Kernel.php`, add `->weekdays()->between('08:00', '17:00')`:
```php
// POCOR-9509: Alert queue processing — weekdays, working hours only
$schedule->command('alerts:send', ['--limit=' . config('alerts.process_limit', 50)])
    ->everyMinute()
    ->weekdays()
    ->between('08:00', '17:00')
    ->withoutOverlapping();
```

Adjust the window to the target country's working hours and weekend definition (e.g. Friday–Saturday weekend).

### Throttling — Slow Down If Overspam Occurs

`alerts:send` processes a fixed number of queue rows per run, controlled by `ALERTS_PROCESS_LIMIT` in `.env`. Default is `50` per minute run (~3,000/hour at full load).

If the system is sending too many messages too fast, **lower this value without touching code**:

```env
# .env — default 20, safe for free-tier providers that cap at ~20 msg/min
ALERTS_PROCESS_LIMIT=20
```

Then clear config cache:
```bash
php artisan config:cache
```

No deployment or code change needed. The scheduler picks up the new limit on the next run.

| `ALERTS_PROCESS_LIMIT` | Max messages/hour | Use case |
|------------------------|-------------------|----------|
| `20` (default) | ~1,200 | Safe for free-tier mail/SMS providers |
| `50` | ~3,000 | High-throughput production with paid provider |
| `5` | ~300 | Throttled / overspam investigation |
| `0` | 0 | Emergency pause (queue accumulates, resumes when raised) |

> Setting `ALERTS_PROCESS_LIMIT=0` pauses processing without disabling the cron — messages accumulate in `alert_queue` and will be delivered once the limit is raised again.

---

## Not Implemented

| Feature | Process name | Reason |
|---------|-------------|--------|
| `StaffAttendance` | `AlertStaffAbsence` | No CakePHP shell was ever written; no Laravel command created. Locked to `Never` in `AlertsTable::NON_IMPLEMENTED_ALERTS`. See [alerts/staff-attendance.md](alerts/staff-attendance.md). |

---

*For user-facing documentation, alert type explanations, and worked examples, see [README.md](README.md) and the individual files in [alerts/](alerts/).*
