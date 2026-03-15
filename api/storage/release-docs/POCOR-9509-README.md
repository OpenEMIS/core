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

4. **Run smoke tests** for alert commands:
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

5. **Verify queue is populated**:
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
