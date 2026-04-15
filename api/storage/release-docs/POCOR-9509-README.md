# POCOR-9509 Release Notes: Alert System and Queue Consolidation

**Release date:** 2026-03-15

---

## What is the Task?

This release consolidates the CakePHP alert architecture: all Shell daemons have been ported to Laravel artisan commands, AlertQueue has been merged into the Alert plugin (removing the duplicate in `src/`), and NON_IMPLEMENTED_ALERTS has been cleaned to reflect current state. Event-based alerts (StudentAbsence, StudentAdmission, StudentEnrolment, StudentStatusChange) now dispatch immediately via Laravel; scheduled alerts (RetirementWarning, StaffEmployment, etc.) run via the `alerts:check-and-queue` cron task.

---

## Situation Before

- Alert system split between CakePHP Shells (legacy, no longer invoked) and partially-ported Laravel commands
- `AlertQueueTable` duplicated in both `src/Model/Table/` and `plugins/Alert/src/Model/Table/`
- References to AlertQueue split between `AlertQueue` and `Alert.AlertQueue` aliases
- NON_IMPLEMENTED_ALERTS contained entries for features now implemented (CaseEscalation, LicenseValidity, LicenseRenewal, ScholarshipApplication, ScholarshipDisbursement) and missing entry for StaffAttendance
- Alert queue state not centralized; no unified method to check queue stats

---

## What Was Implemented

### Core Changes

1. **Consolidated AlertQueue into plugin**
   - Moved `src/Model/Table/AlertQueueTable.php` → `plugins/Alert/src/Model/Table/AlertQueueTable.php`
   - Added `validationDefault()` method for queue record validation
   - Added `queueAlert()` — wraps single alert into a queue entry
   - Added `queueEmail()`, `queueSms()` — specialized queue methods for message types
   - Added `getQueueStats()` — returns `['total' => N, 'queued' => N, 'sent' => N, 'failed' => N]`

2. **Updated all references**
   - `src/Model/Behavior/AlertQueueBehavior.php`: `fetchTable('AlertQueue')` → `fetchTable('Alert.AlertQueue')`
   - `plugins/Alert/src/Model/Table/AlertLogsTable.php`: `get('AlertQueue')` → `get('Alert.AlertQueue')`

3. **Cleaned up NON_IMPLEMENTED_ALERTS**
   - Removed: CaseEscalation, LicenseValidity, LicenseRenewal, ScholarshipApplication, ScholarshipDisbursement (all now implemented)
   - Now contains only: StaffAttendance (no shell ever existed, no Laravel command created)

### Files Changed Summary

```
Added:    1 file  (plugins/Alert/src/Model/Table/AlertQueueTable.php)
Modified: 2 files (src/Model/Behavior/AlertQueueBehavior.php, plugins/Alert/src/Model/Table/AlertLogsTable.php)
Removed:  1 file  (src/Model/Table/AlertsQueueTable.php)
```

### Post-release Fixes (2026-04-09)

4. **SystemUpdates alert — extended placeholders**
   - Added `${new_version}`, `${release_date}`, `${current_version}` placeholders
   - `${version}` kept as legacy alias (not shown in UI, still works in saved templates)
   - `current_version` sourced from `config_items.db_version`
   - Behavior updated so all 3 placeholders appear in the alert rule edit window

5. **Event-based alerts — frequency corrected**
   - `AlertStudentStatus` and `AlertStaffType` added to `$oneTimeProcesses` (UI now shows Never/Once only)
   - DB corrected: StudentAbsence, StudentAdmission, StudentEnrolment, StudentStatus, StaffType set to `Once`

6. **Alert logs — status column display fixed**
   - `beforeAction` field declaration missing `'type' => 'select'` — `onGetStatus` was never called, Status column blank

7. **Alert queue throttle — `ALERTS_PROCESS_LIMIT` env var**
   - Default changed from 50 → 20 (safe for free-tier providers ~20 msg/min)
   - `config/alerts.php` exposes `ALERTS_PROCESS_LIMIT`; `Kernel.php` reads it at runtime
   - Set to `0` to pause queue without disabling cron

8. **Institution Messaging — class/subject scope role filter**
   - Added `HOMEROOM_TEACHER_ROLE (5)`, `TEACHER_ROLE (6)`, `STAFF_ROLE (7)` constants
   - Class and Subject scopes now only show: Homeroom Teacher, Teacher, Staff, Student, Guardian
   - Institution-level roles (Principal, Administrator, etc.) no longer appear for class/subject scope
   - Institution scope warning added: users without security group link to institution won't receive message

### Post-release Fixes (2026-04-15)

9. **StudentAttendance alert — role-gated, class-scoped recipient resolution**

   Previously the alert went to whoever was in `alerts_roles` looked up generically by institution. Now two distinct groups are resolved:

   - **Class staff** (primary teacher from `institution_classes.staff_id` + secondary teachers from `institution_classes_secondary_staff.secondary_staff_id`): notified only if role **Homeroom Teacher (5)** or **Teacher (6)** is present in `alerts_roles` for that rule
   - **Institution management** (Principal + Deputy Principal found via the institution's security group): notified only if role **Principal (4)** or **Deputy Principal (11)** is present in `alerts_roles` for that rule

   **Why role-based gating matters:**
   A class teacher and a principal may need to take different actions on the same absence. For example:
   - A teacher's alert message might say: *"Student ${student.name} has been absent ${total_days} days — please contact the parents."*
   - A principal's alert message might say: *"Student ${student.name} has been absent ${total_days} days — please escalate to the welfare office or contact the police if safeguarding is a concern."*
   
   By controlling which roles receive the alert via `alerts_roles`, the system administrator can configure one rule that notifies teachers only, another that notifies principals only, or both — each with its own tailored message and threshold. If a role is removed from `alerts_roles` in the UI, that group stops receiving the alert immediately with no code change.

10. **AlertRulesTable — role dropdown restricted for StudentAttendance**
    - Added `assignToClassStaffOnly()` method: filters the roles dropdown to **Principal, Deputy Principal, Homeroom Teacher, Teacher** only
    - StudentAttendance feature now routes to this method instead of `assignToAllRoles()`
    - Prevents accidental assignment of irrelevant roles (Administrator, Guardian, Student, etc.) to attendance alerts

### Database Migrations

None required. The AlertQueue table structure is unchanged; only the ORM location is consolidated.

---

## Deployment Instructions

1. **Git pull** the latest changes:
   ```bash
   git pull origin POCOR-9509
   ```

2. **Clear Laravel cache** (AlertQueue is loaded via Cake plugin discovery):
   ```bash
   php artisan config:cache
   php artisan cache:clear
   ```

3. **Verify alert table access** (no structural changes, but confirm plugin alias works):
   ```bash
   php artisan tinker
   >>> \App\Models\AlertQueue::first();  // or via CakePHP Table locator
   >>> exit
   ```

4. **Prepare test data** (dev/test databases only — never production):

   Many users on fresh or anonymised databases have no email or mobile number, causing zero recipients to be resolved. Fill them with fake-but-unique values that the built-in sender blockers will silently discard (`.comz` blocks email delivery, `zz` blocks SMS delivery):

   ```sql
   -- Fill missing or invalid emails
   UPDATE security_users
   SET email = CONCAT(
           IF(REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '') = '', id, REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '')),
           '@gmail.comz'
               )
   WHERE email IS NULL OR email NOT LIKE '%@%';

   -- Fill missing mobile numbers
   UPDATE security_users
   SET mobile_number = CONCAT(
           IF(REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '') = '', id, REGEXP_REPLACE(openemis_no, '[^a-zA-Z0-9]', '')),
           'zz'
                       )
   WHERE mobile_number IS NULL OR mobile_number = '';
   ```

   This lets the entire pipeline run (queuing → recipient resolution → placeholder replacement → sender) without sending any real email or SMS.

   > ⚠️ **If the anonymised database still contains real email addresses or phone numbers**, it is the tester's or deployer's responsibility to verify this before running any alert command — a single test run can send a large volume of messages to real people. Check with:
   > ```sql
   > SELECT COUNT(*) FROM security_users WHERE email NOT LIKE '%@%.comz' AND email LIKE '%@%';
   > SELECT COUNT(*) FROM security_users WHERE mobile_number IS NOT NULL AND mobile_number NOT LIKE '%zz';
   > ```
   > If either returns non-zero, anonymise those rows first or disconnect the mail/SMS provider.

5. **Set cron to working hours** before enabling the scheduler. The alert queue is cron-driven — a misconfigured schedule will deliver messages at 3 am on a Monday or on a Saturday. Restrict to working hours and weekdays only:

   ```cron
   ```cron
   # Standard Laravel scheduler entry (every minute, all day)
   * * * * *  cd /var/www/html/emis/core/api && php artisan schedule:run >> /dev/null 2>&1
   ```

   Restrict to working hours in `Kernel.php` with `->weekdays()->between('08:00', '17:00')`. Adjust the window to the target country's working hours and weekend definition.

   **To throttle sending speed**, set `ALERTS_PROCESS_LIMIT` in `.env` — no code change needed:
   ```env
   # Default 20 — safe for free-tier mail/SMS providers (e.g. 20 msg/min limit)
   ALERTS_PROCESS_LIMIT=20
   ```
   Then run `php artisan config:cache`. Setting it to `0` pauses processing entirely (queue accumulates, resumes when raised).

6. **Run smoke tests** for alert commands:
   ```bash
   # Test queue stats retrieval
   php artisan tinker
   >>> $table = \Cake\ORM\TableRegistry::getTableLocator()->get('Alert.AlertQueue');
   >>> $stats = $table->getQueueStats();
   >>> dump($stats);
   >>> exit

   # Test a direct alert (example: RetirementWarning)
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core/api && php artisan alerts:retirement-warning \
      --user_id=1 --rule_id=1 --process_id=0"
   ```

6. **Verify queue is populated**:
   ```sql
   SELECT COUNT(*) as queued_alerts FROM alerts_queue WHERE is_read = 0;
   ```

---

## System Administrator Guide

### Queue Stats and Monitoring

Check alert queue health:
```php
$alertQueueTable = TableRegistry::getTableLocator()->get('Alert.AlertQueue');
$stats = $alertQueueTable->getQueueStats();
// Returns: ['total' => 150, 'queued' => 45, 'sent' => 100, 'failed' => 5]
```

### Log Locations

- **Laravel alert command output:** `api/storage/logs/laravel.log`
- **CakePHP debug log:** `logs/hin-debug.log`
- **Queue errors:** Logged via `alert_logs` table with `status = FAILED`

### Rollback

If AlertQueue consolidation causes issues:

1. Restore the backup table (if one exists):
   ```sql
   DROP TABLE IF EXISTS alerts_queue;
   RENAME TABLE z_9509_alerts_queue TO alerts_queue;
   ```

2. Revert code to prior commit:
   ```bash
   git reset --hard HEAD~1
   php artisan config:cache
   ```

### Troubleshooting

**Problem:** `Table 'Alert.AlertQueue' not found`
- **Cause:** Plugin discovery failed; AlertQueue not in plugin namespace
- **Fix:** Verify `plugins/Alert/src/Model/Table/AlertQueueTable.php` exists and class name is `AlertQueueTable` (not with alias suffix)
- **Check:** `php artisan tinker` → `\Cake\ORM\TableRegistry::getTableLocator()->get('Alert.AlertQueue')->getTable();`

**Problem:** Queue methods undefined (`queueAlert()`, `getQueueStats()`)
- **Cause:** Using old `src/Model/Table/AlertQueueTable.php` or class not loaded
- **Fix:** Confirm `plugins/Alert/src/Model/Table/AlertQueueTable.php` has all methods; clear Cake registry and re-fetch

**Problem:** Alert commands fail with `AlertQueue reference error`
- **Cause:** Stale cached table references in Laravel bootstrap
- **Fix:** Run `php artisan config:cache && php artisan cache:clear`

### Alert Queue Structure

```sql
DESCRIBE alerts_queue;
```

Common columns:
- `id` — primary key
- `alert_rule_id` — foreign key to alert_rules
- `institution_id` — institution context (if applicable)
- `user_id` — recipient user
- `security_role_id` — role recipient
- `subject`, `body`, `message_type` — template output
- `is_read` — delivery status flag
- `created`, `modified` — timestamps
