# POCOR-9652 Release Notes

## What is the Task?

The "No Scheduled Classes" feature in Student Attendance had three problems:

1. Marking individual student attendance on a "No Scheduled Classes" day never cleared the flag — the grid kept showing "No Lessons" even after attendance was saved.
2. The "No Scheduled Class" toolbar button had no visual state — it looked the same whether the day was flagged or not, so teachers could not tell what had been set.
3. Clicking the button a second time had no effect — the flag could not be undone.

The fix makes the flag reversible (toggle), adds visual feedback (amber button when active), disables the edit button when the day is blocked, and clears the flag automatically when any attendance is marked on that day.

## Situation Before

- A day flagged as "No Scheduled Classes" always showed "No Lessons" — no way to recover attendance view without direct DB intervention.
- Marking an individual student absent/present on such a day did not clear the `no_scheduled_class` flag.
- The toolbar button gave no visual indication of the current state.
- Clicking the button twice had no effect (no toggle/undo logic).
- The Edit (pencil) button remained visible even when the day was blocked.
- After toggling the button, the attendance period selector always jumped back to period 1, losing the user's selection.
- Subject-based attendance stored `subject_id=0` instead of the correct subject ID when creating the marked-records row.

## What Was Implemented

### Fix 1 — PHP: Toggle "No Scheduled Class" (SET and UNDO)

**File:** `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php`

`findNoScheduledClass()` now checks whether a `no_scheduled_class=1` row already exists for the given date/period/subject before writing:

- **If already set (UNDO):** deletes the matching row → the confirmation query returns 0 rows → Angular sees `total=0` → clears the orange button and re-renders the grid.
- **If not set (SET):** updates or inserts the row with `no_scheduled_class=1` as before.

Also added `use Cake\Log\Log;` import (was missing, causing a fatal `Class not found` on every call).

**XOR rule for period / subject storage:**
- Period-based attendance: stores `period=X, subject_id=0`
- Subject-based attendance: stores `period=0, subject_id=X`

This ensures the toggle check and delete are correctly scoped regardless of attendance mode.

### Fix 2 — PHP: Auto-clear flag when attendance is marked

**File:** `plugins/Institution/src/Model/Table/StudentAbsencesPeriodDetailsTable.php`

Added private `clearNoScheduledClass(Entity $entity)` that runs `UPDATE student_attendance_marked_records SET no_scheduled_class=0` for the matching institution/class/date whenever any attendance record is saved. Called from:

- `afterSave()` — when a student is marked absent or late
- `beforeSave()` — when a student is marked present (delete path, `absence_type_id == 0`)

### Fix 3 — Angular: Amber button, disabled Edit, toggle state

**File:** `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.ctrl.js`

- Added `vm.isNoScheduledDay()` helper: returns `true` when `classStudentList[0].no_scheduled_class == 1`.
- `updateAttendancePeriodList()`: preserves the currently selected period if it is still in the returned list; only falls back to `[0]` when the selection no longer exists (e.g. different day with fewer periods). Removed the duplicate assignment that was there.
- `updateSubjectList()`: same preservation logic for the selected subject.

**File:** `plugins/Institution/templates/Institutions/student_attendances.php`

- "No Scheduled Class" button: applies `btn-warning` (amber) class via `ng-class` when `isNoScheduledDay()` is true; tooltip text updates accordingly.
- Edit (pencil) button: added `&& !$ctrl.isNoScheduledDay()` to its `ng-show` — the edit button is hidden when the day is blocked, preventing attendance edits on a "No Scheduled Classes" day.

**File:** `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.svc.js`

`getNoScheduledClassMarked()` sends the toggle request to `findNoScheduledClass` and resolves with `isMarked = (total > 0)`. After UNDO, `total=0` → `isMarked=false` → button goes back to default grey.

### Files Changed Summary

```
Added:    0 files
Modified: 5 files
Removed:  0 files
```

| File | Change |
|------|--------|
| `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php` | Toggle logic, XOR period/subject rule, `use Cake\Log\Log` import |
| `plugins/Institution/src/Model/Table/StudentAbsencesPeriodDetailsTable.php` | `clearNoScheduledClass()` called from `afterSave` and `beforeSave` |
| `plugins/Institution/templates/Institutions/student_attendances.php` | Amber button style; Edit button hidden when day is blocked |
| `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.ctrl.js` | `isNoScheduledDay()`, period/subject selection preservation |
| `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.svc.js` | Toggle request/response handling |

### Database Migrations

None. The `no_scheduled_class` column in `student_attendance_marked_records` already exists with `DEFAULT '0'`.

## Deployment Instructions

1. **Pull the branch:**
   ```bash
   git pull origin POCOR-9652
   ```

2. **Rebuild Angular frontend** (required — JS and template changes):
   ```bash
   docker exec poe-application sh -c "cd /var/www/html/emis/core/frontend && NODE_OPTIONS=--openssl-legacy-provider npx ng build --configuration production"
   ```
   Takes ~8 minutes. Output goes to `webroot/js/angular/dist/`.

3. **Clear CakePHP caches:**
   ```bash
   docker exec poe-application /bin/sh -c "cd /var/www/html/emis/core && php bin/cake.php cache clear_all"
   ```

4. **Smoke test:**
   - Open an institution's Student Attendance page.
   - Select a day with no entries. Click the "No Scheduled Class" button — it should turn **amber**.
   - Verify the **Edit** (pencil) button disappears while the button is amber.
   - Click the amber button again — it should return to **grey**, Edit reappears.
   - Click to set "No Scheduled Class" again (amber). Then switch to **Edit**, mark a student absent, wait for auto-save, click **Return** — the amber button should disappear, the grid shows attendance data (not "No Lessons").
   - Verify period/subject selection is **not** reset to the first option after toggling.

## System Administrator Guide

### Log Locations

- CakePHP error log: `/var/www/html/emis/core/logs/hin-error.log`
- CakePHP debug log: `/var/www/html/emis/core/logs/hin-debug.log`

### Rollback

```bash
git revert HEAD  # or reset to the commit before this branch was merged
```

Then rebuild Angular and clear caches. Any `no_scheduled_class=1` rows already written remain; reset individually if needed:

```sql
UPDATE student_attendance_marked_records
SET no_scheduled_class = 0
WHERE date = 'YYYY-MM-DD' AND institution_class_id = N;
```

### Troubleshooting

| Symptom | Check | Resolution |
|---------|-------|------------|
| "No Lessons" still appears after marking attendance | `SELECT no_scheduled_class FROM student_attendance_marked_records WHERE date=... AND institution_class_id=...` | If still 1, `clearNoScheduledClass()` did not fire. Check `hin-error.log` for exceptions in `StudentAbsencesPeriodDetailsTable`. |
| Button stays grey after clicking (no amber) | Check browser console for JS errors; check `hin-error.log` for PHP 500 on the `findNoScheduledClass` request | Verify `use Cake\Log\Log` is present in `StudentAttendanceMarkedRecordsTable.php` (was a known missing import). |
| Toggle undoes instantly on second click | Expected behaviour — second click is the UNDO path. | Not a bug. If period jumps to 1 after toggle, rebuild Angular (JS change to `updateAttendancePeriodList`). |
| Edit button still visible when day is blocked | Angular dist is stale. | Rebuild Angular. |
| Period / subject resets to first on every toggle | Angular dist is stale. | Rebuild Angular. |

### Key Business Logic

- `student_attendance_marked_records.no_scheduled_class = 1` is the flag that causes the grid to show "No Lessons" for a day.
- The flag is toggled by `findNoScheduledClass` — SET on first click, UNDO (delete row) on second click.
- The flag is auto-cleared whenever any attendance record (absent, late, or present) is saved for that day/class combination.
- The Edit button is intentionally suppressed while the day is flagged — teachers must first undo the "No Scheduled Class" designation before editing individual attendance.
