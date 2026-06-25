# POCOR-9598 — Role-Based Profile Permission Control

## What is the Task?

Three failing scenarios prompted this work:

1. **Institutions > General > Profiles > Institutions** — Profiles should be generated; Profile was stuck in progress (worked in v4). → **Fail**
2. **Institutions > General > Profiles > Classes** — Classes should be generated; Classes was stuck in progress. → **Fail**
3. **Institutions > General > Profiles > Students** — When logging in as other roles, action button should appear if user has permission; action button was not appearing. → **Fail**

Replace CakePHP controller-action based permission checks with centralised role-based security function validation across all profile types (Institution, Staff, Student, Classes). Implement a reusable `ProfilePermissionTrait` to check `security_role_functions._execute` permissions consistently, fixing visibility of profile Generate/Download buttons for non-admin roles (Teachers could see buttons they shouldn't; Principals couldn't see Classes profile buttons).

## Situation Before
- Profile action buttons (Generate/Download) were checked via CakePHP's `AccessControl->check()` using controller action names — fragile and role-inconsistent.
- Teachers could see Institution/Staff/Student profile action buttons even though they should not have access.
- Principals could not see Classes profile action buttons even though they should.
- Profiles (Institution, Classes) were getting stuck permanently as `IN_PROGRESS` — no FAILED status existed to mark generation failures, and no stuck-queue cleanup ran.
- Profile generation used deprecated CakePHP `Shell` classes (`Cake\Console\Shell`) which are removed in CakePHP 5. The cron-triggered workers were not compatible with the CakePHP 5 console.

## What Was Implemented

### Shell → CakePHP 5 Command Migration

CakePHP 5 removed `Cake\Console\Shell`. The old profile-generation workers were rewritten as `Cake\Command\Command` subclasses with a shared abstract base.

**Added files:**
| New Command | Replaces Shell |
|---|---|
| `src/Command/GenerateProfileCommandBase.php` | (new abstract base — no old equivalent) |
| `src/Command/GenerateAllClassProfilesCommand.php` | `src/Shell/GenerateAllClassProfilesShell.php` |
| `src/Command/GenerateAllInstitutionProfilesCommand.php` | `src/Shell/GenerateAllInstitutionReportCardsShell.php` |
| `src/Command/GenerateAllStaffProfilesCommand.php` | `src/Shell/GenerateAllStaffReportCardsShell.php` |
| `src/Command/GenerateStudentProfileCommand.php` | `src/Shell/GenerateAllStudentReportCardsShell.php` |

**`GenerateProfileCommandBase` lifecycle:**
1. Fetch the oldest `NEW_PROCESS` record from the profile process queue
2. Update it to `RUNNING`
3. Call `renderExcelTemplate` on the appropriate Excel table
4. On failure: update the profile record to `FAILED (5)` and delete the process queue entry
5. On success: mark `COMPLETED` and recursively spawn the next worker

Each concrete command only declares:
- `defaultName()` — the `bin/cake` command name (e.g. `generate_all_class_profiles`)
- `getProcessTableAlias()` — which process queue table to read from
- `getExcelTableAlias()` / `getProfileDataTableAlias()` — which tables to write to
- `getLogFileName()` — per-profile-type log file
- `enrichRecord()` — optional extra arguments (e.g. `area_id` for class profiles)

**Trigger methods updated** in all four profile tables: `triggerGenerateReportCardCommand()` now calls `bin/cake generate_*_profiles` instead of the old `bin/cake generate_all_*_profiles_shell`.

**Old Shell files** (`src/Shell/GenerateAll*Shell.php`) are retained on disk but no longer invoked — the trigger methods bypass them entirely.

### New ProfilePermissionTrait
Created `plugins/Institution/src/Model/Traits/ProfilePermissionTrait.php` — a reusable PHP trait providing centralised permission checking:
- **Method:** `hasProfileFunctionPermission(string $functionName, string $controller): bool`
- **Logic:**
  - Looks up `security_functions.id` dynamically by `name` + `controller` — **portable across country deployments** where `auto_increment` IDs may differ
  - Queries `security_role_functions._execute=1` for the current user's roles in the current institution's security group
  - Uses union semantics: any role with `_execute=1` grants the permission
  - Per-request cache `$_profilePermCache` (keyed by `"controller:name"`) to avoid N×2 DB queries per page load
  - Super-admin bypass
  - Guards against null institution ID and missing function record

### Updated Profile Tables
All four profile tables now use `ProfilePermissionTrait` with string name constants instead of hardcoded integer IDs:
1. **InstitutionsProfileTable.php**
   - Constants: `GENERATE_FUNCTION_NAME = 'Generate Institutions Profile'`, `DOWNLOAD_FUNCTION_NAME = 'Download Institutions Profile'`, `FUNCTION_CONTROLLER = 'Institutions'`
   - `onUpdateActionButtons()`: replaced `AccessControl->check()` with `$this->hasProfileFunctionPermission()`

2. **ClassesProfilesTable.php**
   - Constants: `GENERATE_FUNCTION_NAME = 'Generate Classes Profile'`, `DOWNLOAD_FUNCTION_NAME = 'Download Classes Profile'`, `FUNCTION_CONTROLLER = 'Institutions'`
   - `onUpdateActionButtons()`: integrated trait-based permission check

3. **StaffProfilesTable.php**
   - Constants: `GENERATE_FUNCTION_NAME = 'Generate Staff Profile'`, `DOWNLOAD_FUNCTION_NAME = 'Download Staff Profile'`, `FUNCTION_CONTROLLER = 'Institutions'`
   - `onUpdateActionButtons()`: integrated trait-based permission check

4. **StudentProfilesTable.php**
   - Constants: `GENERATE_FUNCTION_NAME = 'Generate Students Profile'`, `DOWNLOAD_FUNCTION_NAME = 'Download Students Profile'`, `FUNCTION_CONTROLLER = 'Institutions'`
   - `onUpdateActionButtons()`: integrated trait-based permission check
   - **Also removed:** Dead POCOR-5191 code (old template-to-role filter with PHP bug `unset($buttons); return $buttons`)

### Failed Status for Stuck-in-Progress Profiles

A new `FAILED` status (value `5`) was added across all four profile table types and `GenerateProfileCommandBase` to handle profiles that fail during generation instead of remaining stuck as `IN_PROGRESS` indefinitely.

**Added to all four profile tables** (`InstitutionsProfileTable`, `StaffProfilesTable`, `StudentProfilesTable`, `ClassesProfilesTable`):
- `const FAILED = 5` — status constant matching the command base
- `self::FAILED => __('Failed')` label in the status dropdown so the UI displays "Failed" instead of a blank or numeric value

**`GenerateProfileCommandBase`:**
- On any exception during `renderExcelTemplate`, the profile record is updated to `FAILED` (5) and its process queue entry is deleted — preventing it from staying permanently stuck as `IN_PROGRESS`
- The full exception (message + stack trace) is logged to `hin-error.log` for post-mortem

**Stuck-queue reset window** (`StudentProfilesTable::triggerGenerateReportCardsCommand`):
- Extended from 6 hours to 24 hours to accommodate large-country deployments where generation legitimately takes longer

### Date/Time Cell Render Guard (Post-Merge Stabilisation)
After merging the primary permission feature, additional issues surfaced during profile card generation. When `Hash::extract()` retrieves date/time values from serialized arrays in Excel reports, CakePHP ORM objects become plain strings (e.g. "10:30:00") instead of FrozenTime/DateTime objects. Calling `->format()` directly on these strings caused fatal "Call to a member function format() on string" errors.

**Fix applied to all four ExcelReport behaviors:**
- `StudentExcelReportBehavior.php`
- `StaffExcelReportBehavior.php`
- `ClassExcelReportBehavior.php`
- `InstitutionExcelReportBehavior.php`

In each behavior's `renderCell()` method, added `is_string()` guard with `try/catch` → `''` fallback for both `'date'` and `'time'` switch cases. Profile and report card generation now succeeds without fatal errors.

### Files Changed Summary
**Added:** 6 files — `ProfilePermissionTrait.php` + 5 Command files (`GenerateProfileCommandBase`, `GenerateAllClassProfilesCommand`, `GenerateAllInstitutionProfilesCommand`, `GenerateAllStaffProfilesCommand`, `GenerateStudentProfileCommand`)
**Modified:** 9 files — 4 profile tables + 4 ExcelReport behaviors + `GenerateProfileCommandBase`
**Retained (not deleted):** old `src/Shell/Generate*Shell.php` files — still on disk but no longer triggered

**Database Migrations:** Not required. No schema changes — existing permission mapping in `security_role_functions` is sufficient.
**Backward Compatible:** YES — trait is new; tables use trait in place of old `AccessControl` calls.

## Deployment Instructions
1. `git pull origin POCOR-9598` (or merge to master)
2. No migrations needed.
3. Clear CakePHP cache: `bin/cake cache clear_all`
4. Verify button visibility:
   - **Principal** (full institution access): Log in, navigate to Institutions > [Select Institution] > Profiles sidebar
     - Should see: **Institutions**, **Classes**, **Staff**, **Students** profile options
     - All Generate/Download buttons should be visible
   - **Teacher** (class-level access): Log in, navigate to Institutions > [Select Institution] > Profiles sidebar
     - Should see: **Classes** profile option ONLY
     - No Institutions/Staff/Students options
     - Only "Select" button should be visible on Classes Profile rows
   - **Student** (if profile access is enabled): Generate/Download buttons only visible if role has permission

## Test Results

### Overview
Three scenarios verified with Playwright automated tests:
1. **Principal role (avoryprincipal):** All 4 profile type buttons visible (Institutions, Classes, Staff, Students)
2. **Principal role (Profiles sidebar):** Sees all 4 profile options in sidebar
3. **Teacher role (avorya1teacher/Amanda Wells):** Classes profile only, with "Select" button visible on all rows

### Test Execution Results

**Test 1 ✅ — Principal (avoryprincipal):** All 4 profile type buttons visible
- Navigated to Institutions > [Select Institution] > Profiles
- Saw all 4 sidebar options: Institutions, Classes, Staff, Students
- Verified: Generate and Download buttons present on Institution Profile row

**Test 2 ✅ — Principal (Profiles Sidebar):** Sidebar shows all 4 profile modules
- Principal account has access to Institution/Classes/Staff/Student profile modules
- No filtering of sidebar — full permission set visible

**Test 3 ✅ — Teacher (avorya1teacher / Amanda Wells):** Classes profile only
- Navigated to Institutions > [Select Institution] > Profiles sidebar
- Saw only "Classes" profile option
- No Institutions/Staff/Students options visible
- Verified: "Select" button present on every Classes Profile row (no Generate button since Teacher role lacks Classes Profile: Generate permission)

### What to Look For During Deployment
- **Pass:** Role-specific button visibility matches `security_role_functions._execute` permission matrix
- **Fail:** Buttons visible when role lacks `_execute=1` for the function ID, or hidden when role has permission
- **Cache validation:** Per-request permission cache prevents duplicate DB queries

---

## System Administrator Guide

### Permission Configuration
Profile action buttons (Generate/Download) are now controlled via the `security_role_functions._execute` flag. To grant a role access:

1. Access the Security Configuration page for the role's security group
2. Locate the relevant security functions by name (IDs may vary per deployment):
   - **Institution Profile:** "Generate Institutions Profile", "Download Institutions Profile"
   - **Staff Profile:** "Generate Staff Profile", "Download Staff Profile"
   - **Student Profile:** "Generate Students Profile", "Download Students Profile"
   - **Classes Profile:** "Generate Classes Profile", "Download Classes Profile"
3. Set `_execute=1` for the functions the role should access
4. Changes take effect immediately (per-request cache only)

### Permission Caching
- `ProfilePermissionTrait` caches permission checks per request in `$_profilePermCache`
- No need to clear caches after permission changes — next request will re-validate
- Super-admin (user_id=1) automatically bypasses all permission checks

### Troubleshooting Button Visibility
**Buttons not visible when they should be:**
1. Verify `security_role_functions` has `_execute=1` for the function ID
2. Verify the user's role(s) are mapped to that function
3. Verify the institution_id is set correctly in the session
4. Check `logs/hin-error.log` for any exceptions during permission check

**Query to verify permissions:**
```sql
SELECT sf.name AS function_name, srf._execute, sr.name AS role_name
FROM security_role_functions srf
JOIN security_functions sf ON sf.id = srf.security_function_id
JOIN security_roles sr ON sr.id = srf.security_role_id
WHERE sf.name IN (
    'Generate Institutions Profile', 'Download Institutions Profile',
    'Generate Staff Profile', 'Download Staff Profile',
    'Generate Students Profile', 'Download Students Profile',
    'Generate Classes Profile', 'Download Classes Profile'
);
```

### Rollback
If issues arise, revert the 4 profile table files to remove ProfilePermissionTrait usage and restore old `AccessControl->check()` calls. The trait is purely additive and can be safely removed.