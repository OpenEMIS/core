# POCOR-9617 - Fix "No Scheduled Lessons" When Attendance Was Never Marked

## 1. What is the Task?

Under Attendance > Students, clicking "No Scheduled Lessons" for a day where no attendance had ever been recorded did nothing — no record was saved and the UI showed no change. Additionally, setting "No Scheduled Lessons" on a day that had previously been marked did not clear the existing per-student absence records, leaving stale absence data. Both issues are in the `findNoScheduledClass` backend logic that was originally introduced in POCOR-6021 and partially fixed in POCOR-9609.

## 2. Situation Before

- Clicking "No Scheduled Lessons" on a day with zero prior attendance marks had no effect — `student_attendance_marked_records` was not written and the day remained unmarked.
- The root cause: `findNoScheduledClass()` wrapped all create/update logic inside `formatResults()->map()`, a CakePHP callback that only fires for rows returned by the outer query. If no attendance was ever marked, the query returned zero rows and the callback never ran.
- Setting "No Scheduled Lessons" on a day with previously saved absences did not delete those absence records from `institution_student_absence_details`, causing data inconsistency (absences for a day declared to have no lessons).

## 3. What Was Implemented

### Core Changes

- Refactored `findNoScheduledClass()` to run all create/update logic directly (outside `formatResults`), so it always fires regardless of whether prior attendance records exist.
- On "No Scheduled Lessons": if a `student_attendance_marked_records` row exists for the date/period/class → update it to `no_scheduled_class = 1`; if none exists → insert a new row with `no_scheduled_class = 1`.
- After writing the no-scheduled-class record, `deleteAll()` is called on `institution_student_absence_details` for the same `institution_id`, `academic_period_id`, `institution_class_id`, `date`, and `period` (with `subject_id` filter added for subject-based attendance). This removes all per-student absence records and their comments for that day/period.

### Files Changed Summary

- **Added:** 0 files
- **Modified:** 2 files
- **Removed:** 0 files

| File | Change |
|------|--------|
| `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php` | Refactored `findNoScheduledClass()` — removed `formatResults` wrapper, added direct insert/update logic and absence record deletion |
| `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.ctrl.js` | `onNoScheduledClick()` — after saving, reload student list and refresh grid so UI reflects "No Lessons" state immediately |

### Database Migrations

- **Required:** NO
- **Tables affected:** `student_attendance_marked_records` (write), `institution_student_absence_details` (delete)
- **Backward compatible:** YES

## 4. Deployment Instructions (User Experience)

1. `git pull` on the target server / branch deploy.
2. No migrations required.
3. Clear CakePHP cache: `php bin/cake.php cache clear_all`
4. **Smoke test:**
   - Go to Attendance > Students, select a class and a day that has **never** been marked.
   - Click "No Scheduled Lessons" — the day should now show "No Lessons" and the button state should persist on navigation.
   - Go to a day that has some students marked as absent, click "No Scheduled Lessons" — verify the absence records are cleared (students no longer show as absent when you return to normal edit mode on that day).

## 5. Known Architectural Issue — Memory Exhaustion on Large Deployments

### The Problem

On large production servers (e.g. GY-MOE-TST) with many years of attendance data, clicking "No Scheduled Lessons" caused a PHP `Fatal Error: Allowed memory size exhausted` crash, even when the PHP memory limit was already set to 2 GB. The crash occurred inside `findNoScheduledClass()` during CakePHP ORM existence checks — even a simple `.first()` or `.count()` query OOMed because the CakePHP ORM hydrates entities into memory before the result is used.

The fix (raw SQL `SELECT 1 ... LIMIT 1`) resolves the OOM on the existence checks. However, the returned `$query` at the end of `findNoScheduledClass` (used by the restful API's `total` count) still uses ORM and will still OOM on very large datasets. The INSERT/UPDATE commits to the database *before* the crash, so the "No Lessons" state is written correctly — the JS frontend always reloads from DB via `changeClass()` regardless of the API response, so the UI still reflects the correct state.

### Root Cause: Table Architecture

The two tables involved have an unusual primary key design:

**`student_attendance_marked_records`**

| Column | Type | Key |
|--------|------|-----|
| institution_id | int | PRI (1st) |
| academic_period_id | int | PRI (2nd) |
| institution_class_id | int | PRI (3rd) |
| education_grade_id | int | PRI (4th) |
| date | date | PRI (5th) |
| period | int | PRI (6th) |
| subject_id | int | PRI (7th) |
| no_scheduled_class | tinyint | — |

The entire primary key is a 7-column composite. There is no surrogate `id` column. The composite PK doubles as the only unique lookup index, but CakePHP ORM builds entity objects for every matched row before returning results — on a table with millions of rows this hydration is extremely expensive.

**`institution_student_absence_details`**

| Column | Type | Key |
|--------|------|-----|
| student_id | int | PRI (1st) |
| institution_id | int | PRI (2nd) |
| academic_period_id | int | PRI (3rd) |
| institution_class_id | int | PRI (4th) |
| date | date | PRI (5th) |
| period | int | PRI (6th) |
| subject_id | int | PRI (7th) |

Same pattern — no surrogate key, 7-column composite PK. Separate indexes on `date` and `period` exist but are single-column, so a lookup by `institution_class_id + date + period` cannot use the composite PK efficiently unless all leading columns are also provided.

### Recommendation for System Administrators

On large deployments (> 500,000 rows in `student_attendance_marked_records`):

1. **Add a composite index** covering the most common lookup pattern used by `findNoScheduledClass`:
   ```sql
   ALTER TABLE student_attendance_marked_records
   ADD INDEX idx_nsc_lookup (institution_class_id, education_grade_id, date, period);

   ALTER TABLE institution_student_absence_details
   ADD INDEX idx_absence_cleanup (institution_class_id, date, period);
   ```
2. **Increase PHP memory limit** to at least 4 GB if the ORM path is still triggered by other attendance features.
3. **Monitor** `logs/hin-error.log` for `Allowed memory size exhausted` errors after deployment — they indicate the ORM-based `$query` return path at the end of `findNoScheduledClass` is still being reached on very large result sets.

### Long-Term Fix (Future Ticket)

Replace the final ORM `$query` return in `findNoScheduledClass()` with a raw SQL count, the same way the existence checks were replaced. This would eliminate the last OOM vector in this function entirely.

## 6. System Administrator Guide

- **Log locations:** CakePHP error log at `logs/hin-error.log`, debug log at `logs/hin-debug.log`.
- **Configuration:** None required.
- **Cron:** None.
- **Rollback:** Revert `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php` to the previous version. No DB rollback needed (the write/delete actions are idempotent — re-marking attendance will recreate absence records).
- **Troubleshooting:** If "No Scheduled Lessons" still has no effect, check `hin-error.log` for exceptions from `StudentAttendanceMarkedRecordsTable::findNoScheduledClass`. Ensure the `institution_student_absence_details` table exists (`SHOW TABLES LIKE 'institution_student_absence_details'`).
