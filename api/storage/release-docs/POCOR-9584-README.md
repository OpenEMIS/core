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

---

## Issue 3 – Students > Qualification > Import (404)

### What is the Task?

Fix a 404 error that occurred when accessing the Staff Qualifications import page from the Students module context.

### Situation Before

- Navigating to **Students > Qualification > Import** resulted in a 404 error
- The `ImportStaffQualifications` model was not registered in `StudentsController`
- The import table did not know how to handle Student context (it was built for Staff context only)

### What Was Implemented

1. **Registered `ImportStaffQualifications` in StudentsController** (`plugins/Student/src/Controller/StudentsController.php`)
   - Added `'ImportStaffQualifications'` to `$this->ControllerAction->models` array
   - Enables the controller to load the import component for Students context

2. **Added Student context alias** (`plugins/Staff/src/Model/Table/ImportStaffQualificationsTable.php`)
   - Added `'ImportStaffQualifications'` to `isStudentIDSkipped()` method's import alias list
   - Allows the import behavior to recognize Student context and skip row-level student_id checks

3. **Updated `beforeAction()` for Student context**
   - Modified `ImportStaffQualificationsTable::beforeAction()` to read `student_id` from `pass[1]` when in Student plugin
   - Falls back to `staff_id` for Staff context (backward compatible)
   - Ensures the import correctly associates qualifications with the student being worked with

4. **Updated toolbar buttons** (`onUpdateToolbarButtons()`)
   - Changed to handle both Student and Staff plugins instead of Staff only
   - Ensures the add/import UI correctly shows context-appropriate buttons

### Files Changed

- `plugins/Student/src/Controller/StudentsController.php` — added ImportStaffQualifications to models array
- `plugins/Staff/src/Model/Table/ImportStaffQualificationsTable.php` — added Student context support to beforeAction and toolbar methods

### Database Migrations

Not required — no schema changes.

---

## Issue 4 – Students > Bank Accounts > Add (404)

### What is the Task?

Fix errors when adding a bank account record from the Students module, caused by outdated CakePHP 3 syntax and missing student_id fallback.

### Situation Before

- **Students > Bank Accounts > Add** resulted in a TypeError or 404 error
- `BankAccountsTable::beforeSave()` read `staff_id` from query params, which was null in Student context
- CakePHP 3-style `$request->getQuery['key']` property access caused TypeError in PHP 8.3 (requires method call syntax)
- Query param setting used deprecated CakePHP 3 style instead of immutable builder pattern

### What Was Implemented

1. **Added student_id fallback** (`plugins/User/src/Model/Table/BankAccountsTable.php`)
   - Changed `beforeSave()` to try `staff_id` first, then fall back to `student_id`
   - `$paramsQuery['staff_id'] ?? $paramsQuery['student_id'] ?? null`
   - Allows the same import/add logic to work in both Staff and Student contexts

2. **Fixed query param access** (BankAccountsTable.php, lines ~90–100)
   - Replaced all `$request->getQuery['key']` → `$request->getQuery('key')` (method call)
   - Compliant with CakePHP 5 and PHP 8.3

3. **Fixed query param mutation**
   - Replaced mutable `$request->getQuery = [...]` with immutable `$request = $request->withQueryParams([...])`
   - Follows modern CakePHP patterns and prevents side effects

### Files Changed

- `plugins/User/src/Model/Table/BankAccountsTable.php` — fixed query access syntax and added student_id fallback

### Database Migrations

Not required — no schema changes.

---

## Issue 5 – Students > Textbook > View (404)

### What is the Task?

Fix a fatal error when viewing a Textbook record from the Students module, caused by incorrect parameter decoding.

### Situation Before

- **Students > Textbook > View** resulted in a fatal error when fetching student name for display
- `TextbooksTable::afterAction()` incorrectly decoded `pass[1]` (which in view action contains the encoded record ID, not query params)
- Yielded null userId → fatal error trying to access `$result->first_name` on null

### What Was Implemented

1. **Replaced incorrect `pass[1]` decode** (`plugins/Student/src/Model/Table/TextbooksTable.php`)
   - Changed from `$id = $this->ControllerAction->encodePass[1]` decode (wrong for view action)
   - To `$id = $this->ControllerAction->getQueryString()` which correctly returns context params (student_id, institution_id, etc.)
   - `getQueryString()` is the standardized pattern across import tables

2. **Added null guard**
   - Added check for `$result !== null` before accessing `$result->first_name`
   - Prevents fatal error if record not found

### Files Changed

- `plugins/Student/src/Model/Table/TextbooksTable.php` — replaced pass[1] decode with getQueryString() and added null guard

### Database Migrations

Not required — no schema changes.

---

## Issue 6 – Academic > Competencies > Import (404)

### What is the Task?

Fix a corrupted back button URL that occurred when importing Academic Competencies in non-institution contexts (e.g., from Student or Staff modules).

### Situation Before

- **Academic > Competencies > Import** from Student/Staff context resulted in a 404 or incorrect back button URL
- `ImportBehavior::setupBackButtonUrl()` always appended `[1]` with encoded `institution_id=null` when `backUrl` config was set
- Similarly, `setupDownloadUrlIfAddAction()` lost the original `pass[1]` context params
- These corruptions only occurred in non-institution contexts (where `institutionId` is null)

### What Was Implemented

1. **Fixed `setupBackButtonUrl()`** (`plugins/Import/src/Model/Behavior/ImportBehavior.php`)
   - Only append encoded `institution_id` to `[1]` when `institutionId` is truthy
   - When `institutionId` is null, clear stale `pass[0]` and `pass[1]` entirely
   - Prevents appending `institution_id=null` which corrupts the back URL

2. **Fixed `setupDownloadUrlIfAddAction()`**
   - Only append encoded `institution_id` to `[1]` when `institutionId` is truthy
   - When `institutionId` is null, preserve the existing `pass[1]` context params (e.g., `staff_id`, `student_id`)
   - Ensures download URLs correctly carry Student/Staff context

### Files Changed

- `plugins/Import/src/Model/Behavior/ImportBehavior.php` — added institution_id truthiness checks in setupBackButtonUrl() and setupDownloadUrlIfAddAction()

### Database Migrations

Not required — no schema changes.

---

---

## Issue 7 – Staff > Qualifications > Import (Back button to wrong page)

### What is the Task?

Fix back button navigation from Staff Qualifications import results page. The back button was incorrectly pointing to `ImportStaffQualifications/index` (a dead screen) instead of `Qualifications/index` with full context.

### Situation Before

- After uploading an Excel file to import Staff Qualifications, users saw a results page
- Clicking the back button took them to `ImportStaffQualifications/index` — a blank/dead screen
- Expected behavior: back button should return to the main Qualifications index with full navigation context (student_id, user_id, institution_student_id, etc.)

### What Was Implemented

1. **Fixed `ImportBehavior::processImport` redirect** (`plugins/Import/src/Model/Behavior/ImportBehavior.php`)
   - Root cause: The redirect URL only encoded `institution_id` in `pass[1]`, losing all other context params (student_id, user_id, institution_student_id)
   - Changed to carry the **full `pass[1]`** from the request instead of re-encoding only institution_id
   - Results page URL now includes all original context parameters

2. **Removed action guard from `ImportStaffQualificationsTable::onUpdateToolbarButtons`** (`plugins/Staff/src/Model/Table/ImportStaffQualificationsTable.php`)
   - Root cause: `$action !== 'results'` condition prevented back button correction on the results page
   - Removed the guard so both add and results pages point back to `Qualifications/index` with full encoded parameters
   - Back button now consistently navigates to the correct parent index page

### Files Changed

- `plugins/Import/src/Model/Behavior/ImportBehavior.php` — preserve full pass[1] in processImport redirect
- `plugins/Staff/src/Model/Table/ImportStaffQualificationsTable.php` — removed action guard from onUpdateToolbarButtons

### Database Migrations

Not required — no schema changes.

---

## Summary of Fixes in This Batch

| Issue | Module | Problem | Root Cause | Fix |
|-------|--------|---------|-----------|-----|
| 3 | Students > Qualification > Import | 404 | ImportStaffQualifications not registered in StudentsController | Registered model + added Student context support |
| 4 | Students > Bank Accounts > Add | TypeError/404 | CakePHP 3 query syntax + missing student_id fallback | Fixed query method calls + added fallback |
| 5 | Students > Textbook > View | Fatal error | Incorrect pass[1] decode | Replaced with getQueryString() + null guard |
| 6 | Academic > Competencies > Import | Corrupted URL | Always appending institution_id=null | Added institutionId truthiness checks |
| 7 | Staff > Qualifications > Import | Wrong back button URL | Results redirect only encoded institution_id | Preserve full pass[1] + remove action guard |

**Files Changed Summary:**
- **Modified files**: 6
  - `plugins/Student/src/Controller/StudentsController.php`
  - `plugins/Staff/src/Model/Table/ImportStaffQualificationsTable.php`
  - `plugins/User/src/Model/Table/BankAccountsTable.php`
  - `plugins/Student/src/Model/Table/TextbooksTable.php`
  - `plugins/Import/src/Model/Behavior/ImportBehavior.php`

- **Database Migrations**: Not required
  - No schema changes needed; all fixes address data handling and URL routing logic