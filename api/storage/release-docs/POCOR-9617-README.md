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
- **Modified:** 1 file
- **Removed:** 0 files

| File | Change |
|------|--------|
| `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php` | Refactored `findNoScheduledClass()` — removed `formatResults` wrapper, added direct insert/update logic and absence record deletion |

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

## 5. System Administrator Guide

- **Log locations:** CakePHP error log at `logs/hin-error.log`, debug log at `logs/hin-debug.log`.
- **Configuration:** None required.
- **Cron:** None.
- **Rollback:** Revert `plugins/Attendance/src/Model/Table/StudentAttendanceMarkedRecordsTable.php` to the previous version. No DB rollback needed (the write/delete actions are idempotent — re-marking attendance will recreate absence records).
- **Troubleshooting:** If "No Scheduled Lessons" still has no effect, check `hin-error.log` for exceptions from `StudentAttendanceMarkedRecordsTable::findNoScheduledClass`. Ensure the `institution_student_absence_details` table exists (`SHOW TABLES LIKE 'institution_student_absence_details'`).
