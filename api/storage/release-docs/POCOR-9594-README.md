# POCOR-9594 — Multi-bug QA Fixes

## What is the Task?
Fix multiple QA-reported regressions across the Institutions module: Wash infrastructure edit forms, Assets import fatal error, and Meals Distribution rating field with no options.

---

## Issue 1 — Infrastructure Wash Sanitation Edit shows all values as 0

### Situation Before
When editing an existing Wash > Sanitation record, all quantity fields (Male Functional/Non-functional, Female, Mixed) displayed `0` instead of the saved values.

### What Was Implemented
- **Root cause:** `addEditBeforeAction` set `'attr' => ['value' => 0]` on all 6 quantity fields. Because these fields are not DB columns (values live in `infrastructure_wash_sanitation_quantities`), the entity did not populate them — so the hardcoded `0` always showed.
- **Fix:**
  1. Removed `'value' => 0` from `addEditBeforeAction` field definitions.
  2. Added `addBeforeAction` to restore `'value' => 0` defaults for **add** mode only.
  3. Added `editAfterAction` to read from the `InfrastructureWashSanitationQuantities` association (already loaded via `findEdit`) and map values back to virtual entity properties before the form renders.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/InfrastructureWashSanitationsTable.php`

**Database Migrations:** Not required.

### Deployment Instructions
1. `git pull` on the target server.
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`.
3. Verify: open an existing Sanitation record → Edit → confirm values are populated correctly.

### System Administrator Guide
No config changes. Rollback: revert the file via git.

---

## Issue 2 — Infrastructure Wash Hygiene Edit shows all values as 0

### Situation Before
Same issue as Issue 1 but for Hygiene records.

### What Was Implemented
Identical fix applied to `InfrastructureWashHygienesTable.php`.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/InfrastructureWashHygienesTable.php`

**Database Migrations:** Not required.

### Deployment Instructions
Same as Issue 1.

### System Administrator Guide
No config changes. Rollback: revert the file via git.

---

## Issue 3 — Infrastructure Assets Import error page

### Situation Before
Navigating to Institutions > Infrastructure > Assets > Import resulted in an error page due to a PHP fatal error.

### What Was Implemented
- **Root cause:** `ImportInstitutionAssetsTable.php` had `use PHPExcel_Worksheet;` at the top. PHPExcel was replaced with `phpoffice/phpspreadsheet` — the `PHPExcel_Worksheet` class no longer exists, causing a fatal class-not-found error on every load of this file.
- **Fix:** Removed the dead `use` statement (class is not actually referenced anywhere in the file body).

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/ImportInstitutionAssetsTable.php`

**Database Migrations:** Not required.

### Deployment Instructions
1. `git pull`.
2. Assets Import page should now load without error.

### System Administrator Guide
No config changes. Rollback: revert the file via git.

---

## Issue 4 — Meals > Distribution: Rating field has no options

### Situation Before
The Rating select field on the Meals > Distribution add/edit form appeared empty — no options were available despite `Administration > Field Options > Meals Ratings` having values configured.

### What Was Implemented
- **Root cause:** `InstitutionDistributionsTable.php` had the `belongsTo('MealRatings', ...)` association commented out (comment said "Commented for POCOR-7484"). Without the association, CakePHP cannot auto-populate the select options for `meal_rating_id`. The field was still shown as a `select` in `addEditAfterAction`.
- **Fix:** Restored the `belongsTo('MealRatings', ['className' => 'Meal.MealRatings', 'foreignKey' => 'meal_rating_id'])` line. The `MealRatingsTable` and `meal_ratings` DB table both exist and are functioning.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/InstitutionDistributionsTable.php`

**Database Migrations:** Not required. Data already exists in `meal_ratings`.

### Deployment Instructions
1. `git pull`.
2. Clear CakePHP cache.
3. Verify: navigate to Institutions > Meals > Distribution > Add, confirm Rating dropdown is populated.

### System Administrator Guide
Ensure `Administration > Field Options > Meals Ratings` has at least one active record. Rollback: re-comment the `belongsTo` line.

---

## Issue 5 — Meals > Students > Import: Template download & class-list fatal errors (Bug 9 + Bug 13)

### Situation Before
- Navigating to Institutions > Students > Meals > Import (Angular SPA → CakePHP route) would fail with `BadMethodCallException: Method "session()" does not exist` in `ImportStudentMealsTable::beforeAction`, blocking the template download and the class select dropdown.
- `onUpdateFieldClass` contained a CakePHP 3 session fallback (`$this->request->session()->read(...)`) that caused the same fatal on the class dropdown.
- A dead `use Cake\Network\Request` (CakePHP 3 namespace) was used as a type hint in `onGetBreadcrumb`.
- A dead `use PHPExcel_Worksheet` imported a class that no longer exists after the PhpSpreadsheet upgrade.

### What Was Implemented
- Replaced `$this->request->session()` (removed in CakePHP 5) with `$this->getInstitutionID()` in `beforeAction`.
- Replaced `Cake\Network\Request` import and type hint with `Cake\Http\ServerRequest`.
- Removed dead `use PHPExcel_Worksheet` import.
- Replaced complex decode+session fallback in `onUpdateFieldClass` with `$this->getInstitutionID()`.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/ImportStudentMealsTable.php`

**Database Migrations:** Not required.

**Note on Angular base URL:** The compiled Angular app in `webroot/js/angular/dist/main.js` calls `https://dmo-tst.openemis.org/core/api/v4/` — this is baked in at Docker build time via ARG. The Laravel API endpoint `GET /api/v4/institutions/students/meals/import/template` exists and is correct. To use the template download locally, the Docker image must be rebuilt with `--build-arg` pointing to the local URL.

### Deployment Instructions
1. `git pull`.
2. The CakePHP route `ImportStudentMeals/add` should now load without fatal errors.
3. For Angular-initiated template downloads: rebuild Docker image with correct API base URL ARGs.

### System Administrator Guide
No config changes. Rollback: revert the file via git.

---

## Issue 6 — Angular SPA: credentials, base URL, and 401 handling

### Situation Before
- Angular SPA used a hardcoded API base URL baked at Docker build time; local installs returned 401/network errors.
- `ApiService.setSession()` seeded hardcoded admin/demo credentials as a dev fallback.
- 401 responses from Laravel JWT were not normalized, causing silent failures.

### What Was Implemented
- `ApiService` / `DataService` base URL now computed at runtime from `window.location.origin + '/core/api/v4/'`.
- Removed all `setSession()` calls from components; CakePHP templates inject real credentials via `sessionStorage` (`nbn`/`pbn`).
- `handleError()` normalized to surface the `message` field on any 401.

**Files Changed Summary:**
- Modified: `frontend/src/app/api.service.ts`, `frontend/src/app/shared/data.service.ts`
- Rebuilt: `webroot/js/angular/dist/main.js`

**Database Migrations:** Not required.

### Deployment Instructions
1. `git pull`. Compiled `main.js` is included — no separate build step needed.
2. Smoke test: navigate to any Angular-rendered page and confirm no 401 errors.

### System Administrator Guide
No config changes. Rollback: revert `main.js` via git.

---

## Issue 7 — Institutions > Students > Timetable: empty grid, wrong times, wrong timetable, all shifts missing (Bug 11)

### Situation Before
- Institution timetable edit view showed an empty grid; all timeslots had identical times.
- Student timetable page showed only one random shift even when multiple published timetables existed.
- Overview panel loaded data from a sibling timetable (same class/term, different shift).
- Subject/room dropdowns in the lesson picker showed duplicates on every "Add lesson" click.
- Room picker listed the same room 4–5 times (year-copies, no floor/building context).
- Export saved files with `.xls` extension despite being xlsx format.

### What Was Implemented

**AngularJS student timetable (legacy):**
- `studenttimetable.ctrl.js`: fixed `TimetableSvc` → `StudentTimetableSvc` (ReferenceError); inserted cumulative time-calculation step from shift `start_time` + sorted timeslots; fixed `ExportTimetable` to use per-instance table ID and `.xlsx` extension.
- `studenttimetable.svc.js`: extended `getTimetable` contain to `ScheduleIntervals.Shifts.ShiftOptions`.
- `_student_timetable.php`: lesson-cell matching fixed from `start_time == start_time` (both undefined) to `institution_schedule_timeslot_id == timeslot.id`; each block now carries its own `ng-controller` instance with unique table ID and inline Download button.

**Angular 11 institution timetable:**
- `TimetableOverviewService.php`: added timeslot `id` to `time_slots`; `ORDER BY order ASC`; guarded zero-interval crash.
- `TimetableOverviewRepository.php`: added `timetable_id` filter to prevent sibling timetable being returned.
- `student-timetable.component.ts`: `findIndex` by `timeslot_id`; both `timeSlotById()` and `overViewData()` pass `timetable_id`; rooms and subjects cached for component lifetime.

**Room picker:**
- New `GET /institutions/{id}/rooms/for-timetable`: INNER JOINs floors + buildings; `GROUP BY (name, floor, building) + MAX(id)` deduplicates year-copies; returns `display_name = "Room (Building — Floor)"` sorted by building → floor → room.

**Student timetable page:**
- `StudentsController`: `->toArray()` + contain term + shift; passes all published timetables.
- `student_schedule_timetable.php`: groups by term (`<h2>`) → shift (`<h3>`); independent `ng-controller` per block.

**Files Changed Summary (13 files):**
- `plugins/Profile/webroot/js/angular/studenttimetable.ctrl.js`
- `plugins/Profile/webroot/js/angular/studenttimetable.svc.js`
- `plugins/Student/templates/Element/Timetables/_student_timetable.php`
- `plugins/Student/templates/Students/student_schedule_timetable.php`
- `plugins/Student/src/Controller/StudentsController.php`
- `api/app/Services/TimetableOverviewService.php`
- `api/app/Repositories/TimetableOverviewRepository.php`
- `api/app/Http/Controllers/InstitutionController.php`
- `api/routes/api.php`
- `frontend/src/app/student-timetable/student-timetable.component.ts`
- `webroot/js/angular/dist/main.js`

**Database Migrations:** Not required.

### Deployment Instructions
1. `git pull`. Compiled `main.js` is included.
2. `php artisan route:clear && php artisan cache:clear` (new route registered).
3. Smoke test: open a student with multiple published timetables — all shifts appear grouped by term/shift with correct times and lessons.
4. Verify room picker shows `"Room 1 (Block B — B1)"` with no duplicates.

### System Administrator Guide
The new `rooms/for-timetable` endpoint is protected by `auth.jwt` middleware. No config changes required. Rollback: revert affected files via git.

---

## Issue 8 — Institutions > Students > Risk > View: no data shown, risks not recalculated on attendance save

### Situation Before
- The Student Risk view page showed no data in any column for students who exceeded an absence threshold.
- Saving a new attendance record did **not** trigger risk recalculation — no `institution_student_risks` record was created/updated automatically.
- The "Generate" button also failed silently to populate risks for absence-based criteria.

### What Was Implemented

**Root cause:** Attendance was migrated from `institution_student_absences` to `institution_student_absence_details` (via `StudentAbsencesPeriodDetailsTable`), but the entire risk calculation pipeline still pointed at the old table:
1. `RisksBehavior` was not attached to `StudentAbsencesPeriodDetailsTable` → the save trigger never fired.
2. `InstitutionStudentRisksTable` only listened to `Model.InstitutionStudentAbsences.afterSave/afterDelete` → new attendance events were invisible.
3. `institutionStudentRiskCalculateRiskValue` counted rows from `institution_student_absences` → always returned 0 for new data.
4. `UpdateRisksShell` queried `institution_student_absences` for the Generate action → found no current data, did nothing.

**Fix (4 files):**

1. **`StudentAbsencesPeriodDetailsTable.php`** — Added `Risk.Risks` behavior (with plugin-exclusion guard) so saving any attendance record fires `afterSave`/`afterDelete` on the risk trigger chain.

2. **`InstitutionStudentRisksTable.php`** — Added event listeners for `Model.StudentAbsencesPeriodDetails.afterSave` and `afterDelete`; added a model mapping block that converts `Institution.StudentAbsencesPeriodDetails` → `Institution.InstitutionStudentAbsences` so the existing `getCriteriaByModel` / `criteriaTypes` config lookup continues to work without changing the criteria registry.

3. **`InstitutionStudentAbsencesTable.php`** — Rewrote `institutionStudentRiskCalculateRiskValue` to query `institution_student_absence_details` with `DISTINCT date` (period-by-period attendance can produce multiple rows per day; only unique absence days should count toward the threshold).

4. **`UpdateRisksShell.php`** — Added model remapping in `autoUpdateRisks`: when the criteria model is `Institution.InstitutionStudentAbsences`, redirect to `Institution.StudentAbsencesPeriodDetails` so the Generate shell iterates real current data.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/StudentAbsencesPeriodDetailsTable.php`
- Modified: `plugins/Institution/src/Model/Table/InstitutionStudentRisksTable.php`
- Modified: `plugins/Institution/src/Model/Table/InstitutionStudentAbsencesTable.php`
- Modified: `src/Shell/UpdateRisksShell.php`

**Database Migrations:** Not required. No schema changes; data recalculated at runtime via the Generate action.

### Deployment Instructions
1. `git pull`.
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`.
3. Navigate to Administration > Risks, select the relevant Risk and academic period, click **Generate** to recalculate existing absence data.
4. Verify: students with absences ≥ threshold now appear in Institutions > Students > Risk with correct `total_risk` values.
5. Mark a new attendance record as absent — confirm that the student's risk record is updated immediately without needing to re-generate.

### System Administrator Guide
- The `Risk.Risks` behavior exclusion check (`School.excludedPlugins`) is respected — sites that disable the Risks plugin are unaffected.
- Rollback: revert the 4 changed files via git; existing `institution_student_risks` data is unaffected by the rollback (it will just stop updating on new attendance).
- `institution_student_absences` is retained in the DB for historical reference and is not dropped by this change.

---

## Issue 10 — Survey > Rubrics > View: HTTP 500 (unlogged fatal errors)

### Situation Before
- Navigating to Institutions > Survey > Rubrics > View returned HTTP 500 with no entries in `hin-error.log` or `hin-debug.log`.
- Three separate PHP 8.1 / CakePHP 5 incompatibilities in `InstitutionRubricsTable` and `InstitutionRubricAnswersTable` caused silent fatal errors.

### What Was Implemented

**Root causes (fixed in order of discovery):**

1. **`renderElement()` → `getView()->element()`** — In CakePHP 5, `$event->getSubject()` inside an `onGet*` callback is the `HtmlFieldHelper`, not the `View`. `HtmlFieldHelper::element()` has a different signature `(string, Entity, array)` vs `View::element(string, array)`. Fix: `$event->getSubject()->getView()->element(...)`.

2. **`InstitutionRubricAnswersTable::validationDefault()` missing `: Validator` return type** — PHP 8.1 throws a fatal `Declaration must be compatible` error when the class definition is loaded (triggered lazily by `TableRegistry::get()`). Parent `AppTable::validationDefault()` declares `: Validator`.

3. **`InstitutionRubricAnswersTable::initialize()` missing `: void` return type** — Same PHP 8.1 strict variance rule. Also fixed: removed `use Cake\Network\Request` (class removed in CakePHP 5), replaced `$this->table()` with `$this->setTable()`, added `: array` to `implementedEvents()`.

4. **`'rubric_criteria_option_id IS NOT' => 0` invalid in CakePHP 5 query builder** — Replaced with `'rubric_criteria_option_id !=' => 0` in both table files.

5. **`$this->InstitutionRubricAnswers->find()` crashes** — Calling `find()` on a `HasMany` association object (not a Table) crashes in CakePHP 5. Fix: use `TableRegistry::getTableLocator()->get('Institution.InstitutionRubricAnswers')->find()` directly.

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/InstitutionRubricsTable.php`
- Modified: `plugins/Institution/src/Model/Table/InstitutionRubricAnswersTable.php`

**Database Migrations:** Not required.

### Deployment Instructions (Playwright smoke test path)

To verify the fix on any environment (e.g. `https://dmo-dev.openemis.org/khindol/core`):

```
1. Navigate to https://[host]/core
2. Log in (admin / demo)
3. Click "Institutions" in the top navigation
4. Find and click "Avory Primary School"
5. In the left sidebar expand "Survey" → click "Rubrics"
6. On the Rubrics index page (status tab = New), click the "Select" dropdown on any row
7. Click "View"
8. Expected: View page renders with Status, Rubric Template, Rubric Sections table (sections with criteria counts and clickable links)
9. Expected: hin-error.log is empty; hin-debug.log shows no PHP fatals
```

**Playwright automation snippet (reusable):**
```js
// 1. Login
await page.goto('https://[host]/core', { ignoreHTTPSErrors: true });
await page.$('input[name="username"]').then(f => f.fill('admin'));
await page.$('input[name="password"]').then(f => f.fill('demo'));
await page.keyboard.press('Enter');
await page.waitForLoadState('networkidle');

// 2. Institutions list
await page.goto('https://[host]/core/Institutions/Institutions/index', { ignoreHTTPSErrors: true });
await page.waitForLoadState('networkidle');

// 3. Open Avory Primary School dashboard
const institutionLink = page.locator('a', { hasText: 'Avory Primary School' }).first();
const dashboardUrl = await institutionLink.getAttribute('href');
await page.goto('https://[host]' + dashboardUrl, { ignoreHTTPSErrors: true });
await page.waitForLoadState('networkidle');

// 4. Find Survey > Rubrics link and navigate
const rubricsLink = page.locator('a', { hasText: 'Rubrics' }).first();
await rubricsLink.click();
await page.waitForLoadState('networkidle');

// 5. Open Select dropdown → View on first row
await page.getByRole('button', { name: 'Select' }).first().click();
await page.getByRole('menuitem', { name: 'View' }).click();
await page.waitForLoadState('networkidle');

// Verify: page title should be 'OpenEMIS Core', URL contains 'Rubrics/view'
```

### System Administrator Guide
- No config changes. Rollback: revert the 2 changed PHP files via git.
- These fixes are PHP 8.1 / CakePHP 5 compatibility patches — they correct method signature declarations and API usage that changed between framework versions.
- The same `renderElement → getView()->element()` pattern may affect other pages that use custom element rendering inside `onGet*` callbacks — check any page returning 500 with no log entries for the same pattern.

---

## Issue 12 — Institutions > Cases > Add: Assignee field empty on initial load

### Situation Before
- Opening Institutions > Cases > Add showed "No options" in the Assignee dropdown.
- The dropdown only populated after selecting a Case Type (which triggered a form reload/POST), because the assignee resolution relied solely on `$entity->institution_id` (empty for new entities) and `session('Institution.Institutions.id')` (not set on initial GET in Institution context).

### What Was Implemented

**Root cause:** `WorkflowCaseBehavior::getFirstStepAssigneeOptions()` and `onUpdateFieldWorkflowAssigneeId()` did not read `institution_id` from the URL-encoded query string (`pass[1]`), which is the standard way institution context is passed in OpenEMIS URLs.

**Fix:** Extended the institution_id resolution chain in both methods to a 4-tier fallback:
1. `entity->institution_id` (populated on edit or after POST reload)
2. `getInstitutionID()` via `QueryStringBehavior` (reads from ControllerAction's decoded queryString)
3. `paramsDecode(pass[1])['institution_id']` — direct decode of the URL-encoded query string segment
4. `paramsDecode(params['institutionId'])['id']` — old-style per-parameter encoding (legacy routes)
5. `session('Institution.Institutions.id')` — last resort

**Files Changed Summary:**
- Modified: `plugins/Workflow/src/Model/Behavior/WorkflowCaseBehavior.php`

**Database Migrations:** Not required.

### Deployment Instructions (Playwright smoke test path)

To verify the fix on any environment (e.g. `https://dmo-dev.openemis.org/khindol/core`):

```
1. Navigate to https://[host]/core
2. Log in (admin / demo)
3. Click "Institutions" in the top navigation
4. Find and click "Avory Primary School"
5. Click "Cases" in the left sidebar
6. Click the Add button (+ icon)
7. Expected: the "*Assignee" dropdown immediately shows "-- Select Assignee --"
   (not "No options") and opens to show at least one user (e.g. "John Doe (Superrole)")
8. Expected: no type selection required to populate Assignee
```

**Playwright automation snippet (reusable):**
```js
// 1. Login
await page.goto('https://[host]/core', { ignoreHTTPSErrors: true });
await page.getByRole('textbox', { name: 'Username' }).fill('admin');
await page.getByRole('textbox', { name: 'Password' }).fill('demo');
await page.getByRole('button', { name: 'Login' }).click();
await page.waitForLoadState('networkidle');

// 2. Institutions list → Avory Primary School
await page.getByRole('link', { name: ' Institutions' }).click();
await page.waitForLoadState('networkidle');
await page.getByRole('link', { name: 'Avory Primary School' }).click();
await page.waitForLoadState('networkidle');

// 3. Cases → Add
await page.getByRole('link', { name: 'Cases' }).click();
await page.waitForLoadState('networkidle');
// click the add (+) icon button (first icon link in the toolbar)
await page.locator('a[href*="Cases/add"]').click();
await page.waitForLoadState('networkidle');

// 4. Verify Assignee shows options immediately
const assignee = page.locator('.chosen-single');
await expect(assignee).not.toHaveText('No options');
await assignee.click();
// Should show at least one real user option
await expect(page.locator('.chosen-results li').first()).not.toHaveText('No results match');
```

### System Administrator Guide
- No config changes. Rollback: revert `WorkflowCaseBehavior.php` via git.
- The same institution_id resolution chain is now used in both `getFirstStepAssigneeOptions` (add/edit) and `onUpdateFieldWorkflowAssigneeId` (approve), keeping them consistent.
- Other workflow-based models that are school-based and use the WorkflowCase behavior benefit from this fix automatically.

---

## Issue 13 — Institutions > Committees > Export completes successfully (smoke test)

### Situation Before
No code change required — export was verified as working.

### Playwright Smoke Test Path

```
1. Navigate to https://[host]/core
2. Log in (admin / demo)
3. Click "Institutions" → find and click "Avory Primary School"
4. Click "Committees" in the left sidebar
5. Click the Excel export icon (spreadsheet icon in the toolbar)
6. Expected: file downloads as InstitutionTestCommittees_YYYYMMDDTHHMMSS.xlsx
7. Expected: no error page, no 500, no flash error message
```

**Playwright automation snippet (reusable):**
```js
// 1. Login
await page.goto('https://[host]/core', { ignoreHTTPSErrors: true });
await page.getByRole('textbox', { name: 'Username' }).fill('admin');
await page.getByRole('textbox', { name: 'Password' }).fill('demo');
await page.getByRole('button', { name: 'Login' }).click();
await page.waitForLoadState('networkidle');

// 2. Institutions list → Avory Primary School
await page.getByRole('link', { name: ' Institutions' }).click();
await page.waitForLoadState('networkidle');
await page.getByRole('link', { name: 'Avory Primary School' }).click();
await page.waitForLoadState('networkidle');

// 3. Committees
await page.getByRole('link', { name: 'Committees' }).click();
await page.waitForLoadState('networkidle');

// 4. Click export and wait for download
const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.locator('a[href*="Committees/excel"]').click(),
]);
const filename = download.suggestedFilename();
// Verify filename matches expected pattern
expect(filename).toMatch(/^InstitutionTestCommittees_\d{8}T\d{6}\.xlsx$/);
```

---

## Issue 14 — Institutions > Committees: breadcrumb and heading showed "Institution Test Committees"

### Situation Before
- Navigating to Institutions > Committees displayed breadcrumb text "Institution Test Committees" and page heading "Avory Primary School - Institution Test Committees".
- The word "Test" appeared because CakePHP auto-humanizes the internal class name `InstitutionTestCommitteesTable`.

### What Was Implemented
- Added `getHeader()` override in `InstitutionTestCommitteesTable` returning `__('Institution Committees')` — fixes the H2 page heading.
- Added `beforeAction()` in `InstitutionTestCommitteesTable` calling `$this->Navigation->substituteCrumb(__('Institution Test Committees'), __('Institution Committees'))` — fixes the breadcrumb trail.
- No controller change needed (pattern consistent with `InstitutionBuildingsTable`).

**Files Changed Summary:**
- Modified: `plugins/Institution/src/Model/Table/InstitutionTestCommitteesTable.php`

**Database Migrations:** Not required.

### Deployment Instructions
1. `git pull` on the target server.
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`.
3. Verify: Institutions → any school → Committees → breadcrumb reads "Institution Committees" and heading reads "[School] - Institution Committees".

### System Administrator Guide
- No configuration required.
- Rollback: revert commit `a476d16c00`.

### Playwright Smoke Test

```js
// Navigate to Committees and verify breadcrumb and heading have no "Test"
await page.getByRole('link', { name: ' Institutions' }).click();
await page.waitForLoadState('networkidle');
await page.getByRole('link', { name: 'Avory Primary School' }).click();
await page.waitForLoadState('networkidle');
await page.getByRole('link', { name: 'Committees' }).click();
await page.waitForLoadState('networkidle');

// Breadcrumb last item
const breadcrumb = await page.locator('ol.breadcrumb li:last-child').textContent();
expect(breadcrumb.trim()).toBe('Institution Committees');

// Page heading
const heading = await page.locator('h2').first().textContent();
expect(heading).toContain('Institution Committees');
expect(heading).not.toContain('Test');
```
