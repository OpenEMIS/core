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

---

## Issue 8 – Academic > Competencies > Import CompetencyResults (CakePHP5 + PhpSpreadsheet migration)

### What is the Task?

Fix the `ImportCompetencyResults/add` import page which had multiple CakePHP3→CakePHP5 migration issues and a PHPExcel→PhpSpreadsheet 3.x migration issue, causing: no competency period dropdown options, template download errors, and wrong Excel template structure (columns off by 1).

### Situation Before

- **CakePHP 5 API Issues** (`ImportCompetencyResultBehavior.php`):
  - Used deprecated `$entity->errors()` instead of `$entity->getErrors()`
  - Used deprecated `$entity->invalid()` instead of `$entity->getInvalid()`
  - Used deprecated `$this->config()` instead of `$this->getConfig()`
  - Used deprecated `request->query[]` and `request->data[]` array access instead of method calls
  - Used deprecated `request->session()` instead of `$this->request->getSession()`
  - Used deprecated `$table->alias()` instead of `$table->getAlias()`

- **PHPExcel → PhpSpreadsheet 3.x Migration**:
  - Used `PHPExcel_IOFactory`, `new \PHPExcel()`, `PHPExcel_Writer_Excel2007`, `PHPExcel_Cell_DataValidation` — all removed
  - `getCellByColumnAndRow()` calls used 0-indexed columns (PHPExcel style) but PhpSpreadsheet 3.x is 1-indexed
  - `$commentColumn = (N*2)+2` was off by 1 when read back via `getCellByColumnAndRow()` because PhpSpreadsheet's `getExcelColumnAlpha()` returns 1-indexed column letters
  - `checkCorrectIdTemplate(2, ...)` / `checkCorrectTemplate(0, ...)` started at wrong column indices
  - `setCodesDataTemplate()` passed `$dropdownColumn` to `getCellByColumnAndRow()` instead of `$dropdownColumn+1`

- **URL Parameter Loss**:
  - `setupDownloadUrlIfAddAction()` in `ImportBehavior.php` re-encoded only `institution_id`, losing `class_id`, `academic_period_id`, `competency_template_id` from the template download URL
  - `isInstitutionIDSkipped()` in `InstitutionsController.php` did not skip institution check for `template` action, breaking template downloads

- **Query Parameter Reading**:
  - `getCompetencyCriteriasArray()` / `getStudentArray()` in `ImportCompetencyResultsTable.php` used `getQuery()` (URL query string) when params are in encoded `pass[1]` during the template GET action, returning empty results
  - `onUpdateFieldCompetencyPeriod()` read `academic_period` key from `request->query` when params are in `pass[1]`, and had a trailing space in the column name

### What Was Implemented

1. **All CakePHP 5 API replacements** (`ImportCompetencyResultBehavior.php`)
   - `$entity->errors()` → `$entity->getErrors()`
   - `$entity->invalid()` → `$entity->getInvalid()`
   - `$this->config()` → `$this->getConfig()`
   - `request->query['key']` → `$this->request->getQuery('key')`
   - `request->data['key']` → `$this->request->getData('key')`
   - `request->session()` → `$this->request->getSession()`
   - `$table->alias()` → `$table->getAlias()`

2. **All PHPExcel → PhpSpreadsheet 3.x class replacements** (`ImportCompetencyResultBehavior.php`)
   - `PHPExcel_IOFactory` → `\PhpOffice\PhpSpreadsheet\IOFactory`
   - `new \PHPExcel()` → `new \PhpOffice\PhpSpreadsheet\Spreadsheet()`
   - `PHPExcel_Writer_Excel2007` → `\PhpOffice\PhpSpreadsheet\Writer\Xlsx`
   - `PHPExcel_Cell_DataValidation` → `\PhpOffice\PhpSpreadsheet\Worksheet\DataValidation`

3. **Fixed `getCellByColumnAndRow()` column indexing** (PhpSpreadsheet is 1-indexed vs PHPExcel's 0-indexed):
   - Student column: `0` → `1`
   - Grade loop start: `2` → `3`, end: `totalColumns` → `totalColumns+1`
   - `$commentColumn = (N*2)+2` → `(N*2)+3` (to match PhpSpreadsheet's `getExcelColumnAlpha()` output)
   - `checkCorrectIdTemplate(2, ...)` → `checkCorrectIdTemplate(3, ..., totalColumns+1, ...)`
   - `checkCorrectTemplate(0, ...)` → `checkCorrectTemplate(1, ..., 2, ...)`
   - `setCodesDataTemplate()`: `getCellByColumnAndRow($dropdownColumn)` → `getCellByColumnAndRow($dropdownColumn+1)`

4. **Fixed template URL to carry full context** (`setupDownloadUrlIfAddAction()` in `ImportBehavior.php`):
   - Changed to preserve full `pass[1]` from the request, carrying all encoded context params (`class_id`, `academic_period_id`, `competency_template_id`)
   - No longer loses context when re-building the download URL

5. **Fixed institution skip check** (`InstitutionsController.php`)
   - Added `'template'` to `$furtherActions` in `isInstitutionIDSkipped()` to skip institution validation for template downloads

6. **Fixed query parameter reading** (`ImportCompetencyResultsTable.php`):
   - `getCompetencyCriteriasArray()` and `getStudentArray()`: Changed to decode `pass[1]` via `getQueryString()` as fallback when URL query params are absent (template GET action)
   - `onUpdateFieldCompetencyPeriod()`: Use `$this->request` instead of `request`, read `academic_period` key correctly, removed trailing space from column name

### Files Changed Summary

- **Modified files**: 4
  - `plugins/Institution/src/Model/Behavior/ImportCompetencyResultBehavior.php` — Fixed all CakePHP 5 API calls and PhpSpreadsheet 3.x class usage
  - `plugins/Import/src/Model/Behavior/ImportBehavior.php` — Fixed setupDownloadUrlIfAddAction() to carry full pass[1]
  - `plugins/Institution/src/Controller/InstitutionsController.php` — Added 'template' action to isInstitutionIDSkipped()
  - `plugins/Institution/src/Model/Table/ImportCompetencyResultsTable.php` — Fixed getCompetencyCriteriasArray(), getStudentArray(), onUpdateFieldCompetencyPeriod()

- **Database Migrations**: Not required
  - No schema changes needed

### Deployment Instructions (User Experience)

1. **Git Deployment**
   ```bash
   git pull origin POCOR-9584
   ```

2. **Testing**
   - Navigate to **Academic > Competencies > Import**
   - Verify competency period dropdown shows options when class is selected
   - Click **Download Template** — file should download with correct structure
   - Verify template columns are correctly aligned (student names start at column B, grades follow sequentially)
   - Upload the completed template — import should process correctly without column offset errors

3. **Cache Clear**
   ```bash
   # No cache clear necessary for CakePHP import/template handling
   ```

### System Administrator Guide

### Monitoring

Check `logs/hin-error.log` filtered by `@ImportCompetencyResult` if:
- Template download fails
- Import page shows no dropdown options
- Column mismatch errors appear during import

### Troubleshooting

- If dropdown still empty: Verify `pass[1]` contains `class_id` and `academic_period_id` in the URL
- If template download 404: Ensure `pass[1]` is preserved with all context params
- If column alignment errors: Check `getCellByColumnAndRow()` calls are using 1-indexed columns (3, 4, 5... not 0, 1, 2...)
- Check logs at: `/var/www/html/emis/core/logs/hin-error.log` for specific API errors

### Rollback Procedure

If needed, rollback to the previous commit:
```bash
git revert [commit-hash]
```

---

## Issue 9 – Import consistency: Competency / Outcome / Assessment Item results use clean DB column names

### What is the Task?

Make all three result-import pages consistent with each other:
- `ImportCompetencyResults/add`
- `ImportOutcomeResults/add`
- `ImportAssessmentItemResults/add`

All three now show `academic_period_id` and `institution_class_id` as visible selects from the start, use DB column names as form-field/query-param keys, and follow the same sequential dependency pattern (each field reveals the next).

### Situation Before

- **Field-naming zoo**: Each file used different aliases (`period`, `class`, `class_name`, `education_subject`, `academic_period`, `competency_template`, `outcome_template`, etc.) instead of the actual DB column names.
- **`withQueryParams` replaced instead of merged**: The second loop in `addAfterAction` called `$this->request->withQueryParams($requestDataArray)` which discarded existing URL params, so the template download URL was incomplete.
- **IS operator bug**: `institution_class_id IS` / `academic_period_id IS` used for non-null values — broken in CakePHP5.
- **Dead `addEditOnChange` handlers**: Used CakePHP3 mutable request mutation which does nothing in CakePHP5.
- **Deprecated `->query[]` array access** in both behaviors (CakePHP3 style).
- **Old `paramsDecode(pass[1])`** in `ImportOutcomeResultBehavior::setImportDataTemplate` — replaced by `getQueryParams()`.
- **Active `[TEMP-LOG]` lines** in all five files.

### What Was Implemented

1. **`ImportCompetencyResultsTable.php`** — full consistency rewrite:
   - Renamed all field keys to DB column names (`academic_period` → `academic_period_id`, `class` → `institution_class_id`, etc.)
   - Added missing `academic_period_id → [institution_class_id]` dependency
   - Both `academic_period_id` and `institution_class_id` visible from the start
   - Fixed `withQueryParams` to merge rather than replace
   - Added five clean `addEditOnChange*Id` handlers; renamed all `onUpdateField*` methods
   - Fixed IS operator; commented out dead backup methods

2. **`ImportOutcomeResultsTable.php`** — same consistency rewrite:
   - Same field renames, merge fix, IS operator fix, handler renames, new `addEditOnChange*Id` handlers
   - Removed `use PHPExcel_Worksheet;`

3. **`ImportCompetencyResultBehavior.php`**:
   - `getQuery('competency_item')` → `getQuery('competency_item_id')` in two places
   - All TEMP-LOG lines commented out

4. **`ImportOutcomeResultBehavior.php`**:
   - `addBeforeSave` closure: all field reads renamed to `_id` keys
   - `setImportDataTemplate`: switched from `paramsDecode(pass[1])` to `getQueryParams()`, all field keys renamed
   - Deprecated `->query['field']` → `getQuery('field_id')` in backup method
   - All TEMP-LOG lines commented out

5. **`ImportResultBehavior.php`**: One remaining active TEMP-LOG line commented out

### Files Changed Summary

- **Modified files**: 5
  - `plugins/Import/src/Model/Behavior/ImportCompetencyResultBehavior.php`
  - `plugins/Import/src/Model/Behavior/ImportOutcomeResultBehavior.php`
  - `plugins/Import/src/Model/Behavior/ImportResultBehavior.php`
  - `plugins/Institution/src/Model/Table/ImportCompetencyResultsTable.php`
  - `plugins/Institution/src/Model/Table/ImportOutcomeResultsTable.php`

- **Database Migrations**: Not required

### Deployment Instructions (User Experience)

1. **Git Deployment**
   ```bash
   git pull origin POCOR-9584
   ```

2. **Testing**
   - Navigate to **Academic > Competencies > Import** — `academic_period_id` and `institution_class_id` visible from start; selecting class reveals template dropdown
   - Navigate to **Academic > Outcomes > Import** — same sequential field reveal
   - Click **Download Template** on each page — should download with correct column structure
   - Upload a filled template — import should save records

3. **Cache Clear**
   ```bash
   # No cache clear necessary for CakePHP import/template handling
   ```

### System Administrator Guide

Check `logs/hin-error.log` filtered by `@Import` if any import page shows a black screen or empty dropdown.

### Rollback Procedure

```bash
git revert [commit-hash]
```

---

## Issue 10 – ImportCompetencyResults CakePHP5 compatibility & back-button fix

### What is the Task?

Complete the CakePHP 5 migration of the `ImportCompetencyResults` behavior and fix the back button URL navigation after import results, ensuring all query parameters are correctly preserved and results page redirects carry proper context.

### Situation Before

- **CakePHP 5 API Issues** (`ImportCompetencyResultBehavior.php`):
  - `$event->result` accessed as property instead of via `getResult()` method (protected in CakePHP 5)
  - `$activeModel->newEntity()` called without required array argument in CakePHP 5
  - `$clonedEntity->virtualProperties([])` used — method removed in CakePHP 5, replaced by `setVirtual([])`
  - Overall comment field save used old field key aliases instead of DB column names with `_id` suffix
  - Results redirect URL missing `pass[1]` (bare `url('results')`) so context params were lost

- **Query Parameter Loss in Dynamic-Column Imports** (`ImportCompetencyResultsTable.php`):
  - `getStudentArray()` and `getCompetencyCriteriasArray()` read only from `getQuery()` (URL query string)
  - After `addOnInitialize` clears query params (same issue as CompetencyPeriod), these methods returned empty results during template build
  - `onUpdateToolbarButtons` back button URL always lost context params for both add and results pages

### What Was Implemented

1. **Fixed CakePHP 5 event API** (`ImportCompetencyResultBehavior.php`):
   - `$event->result` → `$event->getResult()` (method call for protected property)
   - Line 186–190: Overall comment field save changed old key aliases (`competency_template`, `competency_period`, `competency_item`, `academic_period`) to DB column names (`competency_template_id`, `competency_period_id`, `competency_item_id`, `academic_period_id`)

2. **Fixed CakePHP 5 entity creation** (`ImportCompetencyResultBehavior.php`):
   - `$activeModel->newEntity()` → `$activeModel->newEntity([])` (requires array argument in CakePHP 5)
   - `$clonedEntity->virtualProperties([])` → `$clonedEntity->setVirtual([])` (method call syntax for CakePHP 5)

3. **Fixed query parameter reading with POST fallback** (`ImportCompetencyResultBehavior.php` and `ImportCompetencyResultsTable.php`):
   - Added `getData()[$alias]` fallback in `getStudentArray()` and `getCompetencyCriteriasArray()` so methods read from POST data if query params are cleared
   - Matches the same pattern used by `ImportResultBehavior::onUpdateToolbarButtons` for dynamic-column imports
   - `competency_item_id` read: added POST data fallback via `getData('competency_item_id')`

4. **Fixed results redirect URL** (`ImportBehavior.php` processImport):
   - Bare `url('results')` → now carries `pass[1]` with encoded context params
   - Added `unset($url['?'])` to strip stale `?` query params from the redirect URL (important for clean URLs after `addOnInitialize` clears them)

5. **Fixed back button URL for ImportCompetencyResults** (`ImportResultBehavior.php` onUpdateToolbarButtons):
   - Changed back button for `ImportCompetencyResults` to use `backUrl` config (StudentCompetencies context) instead of default import back pattern
   - Now carries `pass[1]` (encoded institution_id) on both add and results pages
   - Strips stale `?` and `period` query params to preserve clean URL state
   - Preserves `index` at `pass[0]` instead of unsetting it (correct CakePHP navigation)

### Files Changed Summary

- **Modified files**: 4
  - `plugins/Import/src/Model/Behavior/ImportCompetencyResultBehavior.php` — Fixed CakePHP 5 event/entity APIs, added POST fallback for query params, updated field key aliases to _id suffix
  - `plugins/Institution/src/Model/Table/ImportCompetencyResultsTable.php` — Added POST fallback in getStudentArray() and getCompetencyCriteriasArray()
  - `plugins/Import/src/Model/Behavior/ImportResultBehavior.php` — Fixed back button URL to carry pass[1], strip stale params, use backUrl config for CompetencyResults
  - `plugins/Import/src/Model/Behavior/ImportBehavior.php` — Added unset($url['?']) to results redirect URL

- **Database Migrations**: Not required
  - No schema changes

### Deployment Instructions (User Experience)

1. **Git Deployment**
   ```bash
   git pull origin POCOR-9584
   ```

2. **Testing**
   - Navigate to **Academic > Competencies > Import**
   - Select class and competency period — verify dropdowns work without errors
   - Download template — should have correct column structure with student names and criteria columns
   - Upload completed template — import should process without API errors
   - Click back button on results page — should return to full StudentCompetencies context (not dead import index page)
   - Verify back button URL includes all context parameters (institution_id, class_id, academic_period_id, etc.)

3. **Cache Clear**
   ```bash
   # No cache clear necessary for CakePHP import behavior changes
   ```

### System Administrator Guide

### Monitoring

Check `logs/hin-error.log` filtered by `@ImportCompetencyResult` if:
- Template download fails with API error
- Import page shows blank dropdowns
- Results redirect shows 404 or wrong page
- Back button points to dead import index instead of StudentCompetencies context

### Troubleshooting

- If dropdowns still blank: Verify `addOnInitialize` is clearing params correctly and `addAfterAction` is restoring them via `withQueryParams`
- If results redirect 404: Check `pass[1]` contains encoded context params and `url['?']` is unset
- If back button wrong: Verify `onUpdateToolbarButtons` reads `backUrl` config for `ImportCompetencyResults` and uses `getQueryString()` to build encoded `pass[1]`
- Check logs at: `/var/www/html/emis/core/logs/hin-error.log` for specific CakePHP 5 API errors

### Rollback Procedure

If needed, rollback to the previous commit:
```bash
git revert [commit-hash]
```

---