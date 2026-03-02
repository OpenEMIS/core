# POCOR-9584: Fix Staff Import / Staff Attachments Bugs

## What is the Task?

Fix a database constraint error that occurred when uploading attachments in the staff module. The system was attempting to insert NULL values into the `file_name` column which has a NOT NULL constraint and no default value, causing file uploads to fail with a PDOException.

## Situation Before

- Users attempting to upload files as staff attachments encountered a critical error: "SQLSTATE[HY000]: General error: 1364 Field 'file_name' doesn't have a default value"
- The error occurred in the `beforeMarshal()` event handler when processing form submissions
- No validation was enforcing that a file must be selected before submission, allowing invalid requests to reach the database layer

## What Was Implemented

Fixed the attachment file handling logic in `plugins/User/src/Model/Table/AttachmentsTable.php`:

1. **Corrected `beforeMarshal()` method** (lines 466-471)
   - Removed the logic that explicitly set `file_name` and `file_content` to NULL when missing
   - Changed to only unset fields that don't have values, allowing the validation layer to catch the error
   - This prevents database constraint violations

2. **Added file content validation** (line 69)
   - Added `requirePresence('file_content', 'create')` validation rule
   - Ensures files are required during attachment creation
   - Provides user-friendly error message: "Please select a file to upload"

### Files Changed Summary

- **Modified files**: 1
  - `plugins/User/src/Model/Table/AttachmentsTable.php` — Fixed beforeMarshal and added validation

- **Database Migrations**: Not required
  - No schema changes needed; this fixes a data handling bug

## Deployment Instructions (User Experience)

1. **Git Deployment**
   ```bash
   git pull origin POCOR-9584
   ```

2. **Testing**
   - Navigate to Staff Attachments in the UI
   - Attempt to upload a file — should succeed
   - Attempt to submit the form without selecting a file — should show validation error
   - Verify the error message "Please select a file to upload" appears instead of a database error

3. **Cache Clear**
   ```bash
   # No cache clear necessary for this bug fix
   ```

## System Administrator Guide

### Monitoring

No special monitoring required for this fix. The change corrects invalid file upload behavior.

### Troubleshooting

- If staff attachments still fail with the same error: Clear browser cache and retry
- If validation message doesn't appear: Ensure CakePHP validation layer is running normally
- Check logs at: `/var/www/html/emis/core/logs/hin-error.log` for any related errors

### Rollback Procedure

If needed, rollback to the previous commit:
```bash
git revert [commit-hash]
```

This will restore the original (buggy) code; users will again encounter the database error when uploading without a file.

---

## Staff Leave Import Bugs (additional fix in same branch)

### What is the Task?

Fix two errors that occurred when uploading an Excel file to import Staff Leave records from the Staff module (StaffController).

### Situation Before

- Error 1: `@ImportBehavior line 629: Institution.StaffLeave -> assignee_id => This field is required` — appeared on every import row
- Error 2: `Record not found in table "institutions" with primary key` — appeared from StaffController when navigating to StaffLeave pages without institution_id in the URL

### What Was Implemented

**Bug 1 – `assignee_id` validation failure (`ImportStaffLeaveTable.php`)**

Root cause: `onGetBreadcrumb` read `institution_id` only from the CakePHP session key `Institution.Institutions.id`. When accessed from StaffController the institution_id is carried in the encoded URL querystring, not necessarily already written to that session key. Because `$this->institutionId` was null, `onImportModelSpecificValidation` returned early without setting `$tempRow['assignee_id']`, triggering WorkflowBehavior's `notEmpty` rule.

Fix: Changed `onGetBreadcrumb` to first try `$this->ControllerAction->getQueryString('institution_id')` (same pattern used by `ImportStaffAttendancesTable`), falling back to the session for backward compatibility.

**Bug 2 – `return $_SESSION;` debugging artifact (`StaffController.php`)**

Root cause: `getInstitutionID()` contained an accidental `return $_SESSION;` at line 401 that returned the entire PHP session superglobal instead of reading institution_id from the CakePHP session. This made the session-fallback branch dead code, causing `$this->Institutions->get($institutionId)` in `beforeFilter` to receive an array and throw `RecordNotFoundException`.

Fix: Removed the `return $_SESSION;` line, restoring the original intended session fallback.

### Files Changed

- `plugins/Staff/src/Controller/StaffController.php` — removed `return $_SESSION;` from `getInstitutionID()`
- `plugins/Institution/src/Model/Table/ImportStaffLeaveTable.php` — updated `onGetBreadcrumb` to read institution_id from URL querystring first