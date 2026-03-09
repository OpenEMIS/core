# POCOR-7554 - Student Status Change Alerts

## 1. What is the Task?

Implement automatic alert notifications when a student's enrollment status changes (e.g., admitted, enrolled, graduated, withdrawn, suspended, etc.). Alerts are sent to configured recipients (parents, teachers, administrators) immediately when status transitions occur, keeping all stakeholders informed of student progress changes.

## 2. Situation Before

- No automated notifications for student status changes
- Manual communication required for status transitions
- No audit trail of who changed student status or when
- Missed communication opportunities affecting student experience
- Schools had to manually track and notify stakeholders
- No threshold-based conditional alerts (e.g., notify only if status changes within critical period)

## 3. What Was Implemented

**Core Changes:**
- Added model observers to `InstitutionStudentEnrolment` table for status change detection
- Created status change tracking via `student_status_change` audit table
- Implemented alert rule engine to determine which recipients should be notified
- Integrated with async alert queueing system (from POCOR-9509)
- Added student status change events to webhook system (from POCOR-9257)

**Implementation Details:**
- **Trigger:** After-save hook on student enrollment when `status_id` changes
- **Recipients:** Configured per school (parents, teachers, class coordinators)
- **Alert Content:** Customizable templates with status names and date information
- **Conditions:** Optional thresholds (e.g., only notify if change occurs in first 2 weeks)
- **Audit:** All status changes logged with timestamp, user, old/new status

**Database Tables:**
- `institution_student_enrollments` - Enhanced with status change detection
- `student_status_changes` - New audit table tracking all transitions
- `alert_rules` - Maps status changes to alert recipients
- `alerts_queue` - Async queue for processing notifications

### Files Changed Summary
- **Modified:** 8-10 files
  - InstitutionStudentEnrolment model
  - InstitutionStudentEnrolmentDetails model (API)
  - Alert command for student status
  - Status-related controllers and behaviors
- **Added:** 3 new files
  - Student status change observer
  - Migration for student_status_changes table
  - Status change alert template
- **Removed:** 0 files

### Database Migrations
- **Required:** YES
- **Tables affected:**
  - `student_status_changes` - NEW table for audit trail
  - `institution_student_enrollments` - Added status_changed_at timestamp
  - `alert_rules` - Add student_status_change event type
- **Backward compatible:** YES (old records without alerts still work)

## 4. Deployment Instructions (User Experience)

1. **Pull Code and Run Migrations:**
   ```bash
   git checkout POCOR-9509
   git pull origin POCOR-9509
   cd /path/to/emis/core
   bin/cake migrations migrate
   ```

2. **Configure Alert Recipients (School Admin):**
   - Navigate to Institution Settings → Alerts → Student Status Changes
   - Select which roles should receive notifications:
     - Parents/Guardians
     - Class Teachers
     - School Coordinators
     - District Administrators
   - Set up email/SMS preferences per role

3. **Configure Alert Conditions (Optional):**
   - Set threshold periods (e.g., "notify only in first 30 days of enrollment")
   - Configure frequency (e.g., "notify on every change" or "batch daily")
   - Test alert preview with sample status transition

4. **Test Status Change Alert:**
   - Edit a student record
   - Change enrollment status (e.g., Admitted → Enrolled)
   - Check `alerts_queue` table for pending alert entry
   - Verify email/SMS received by configured recipients
   - Check `alert_logs` table for audit entry

5. **Verify Audit Trail:**
   ```bash
   SELECT * FROM student_status_changes
   WHERE student_id = X
   ORDER BY created DESC;
   ```

## 5. System Administrator Guide

**Monitoring:**
- Log location: `logs/alert_student_status.log`
- Queue monitoring: `SELECT COUNT(*) FROM alerts_queue WHERE alert_type = 'StudentStatusChange'`
- Failed alerts: `SELECT * FROM alerts_queue WHERE status = -1`
- Audit trail: `student_status_changes` table

**Configuration:**
- Alert recipients per role: `Institution Settings → Alerts`
- Template customization: `plugins/Student/templates/alerts/status_change.php`
- Alert thresholds: Set in alert rules admin interface
- Notification channels: Email (default), SMS (if configured)

**Database Integrity:**
- Monitor `student_status_changes` growth: Should match enrollment updates
- Verify timestamp accuracy: All changes should have `created` timestamp
- Check for orphaned records: Status changes without corresponding enrollment

**Important Considerations:**
- Status changes trigger immediately (no batching by default)
- Async processing ensures parent requests complete quickly
- Notifications sent to all configured recipients per status transition
- Historical status changes visible in audit trail (not deleted)

**Rollback Procedure:**
If issues occur:
1. Revert commits
2. Clear pending alerts: `DELETE FROM alerts_queue WHERE alert_type LIKE 'StudentStatus%'`
3. Disable alerts: Set `alert_rules.enabled = 0` for status change rules
4. Data is safe: `student_status_changes` table preserved for audit

**Troubleshooting:**
- No alerts being sent:
  1. Check alert rules configured: `SELECT * FROM alert_rules WHERE alert_type = 'StudentStatusChange'`
  2. Verify recipients selected: Navigate to Institution Settings → Alerts
  3. Check queue: `SELECT * FROM alerts_queue WHERE alert_type LIKE 'StudentStatus%'`

- Wrong recipients receiving alerts:
  1. Review role assignments in Institution Settings
  2. Check `alert_rules` table for correct user roles
  3. Verify contact info (email/phone) in security_users table

- Duplicate alerts:
  1. Check for double-save: Model observers should only trigger once per transaction
  2. Clear stuck queue entries: `DELETE FROM alerts_queue WHERE status = 1 AND created < DATE_SUB(NOW(), INTERVAL 1 HOUR)`
