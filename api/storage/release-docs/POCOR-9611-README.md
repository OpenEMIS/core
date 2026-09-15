# POCOR-9611 — Students Weekly Attendance Report

## 1. What is the Task?

Add a new **Students Weekly Attendance** report under Institution > Statistics > Standard.
The report generates one Excel row per student showing colour-coded attendance status for each
school day × attendance slot (period or subject) within the selected week.

---

## 2. Situation Before

No weekly attendance Excel report existed under Institution > Statistics > Standard.
Staff had no way to export a colour-coded weekly attendance grid per class.

---

## 3. What Was Implemented

### Report features
- Required inputs: Academic Period, Education Grade (required), Institution Class (required), Week (required — dropdown of weeks within the academic period).
- Output: one Excel row per student; one column per school day (Mon–Fri) × attendance slot.
- Cell status values: PRESENT, LATE, EXCUSED, UNEXCUSED, NOTMARKED, NO CLASS.
- Totals columns: Total present (LATE counted as present), Total late, Total absent.
- Attendance By column shows the mark type name matching the class's actual mode (period or subject), not the most recently enabled type globally.

### Attendance mode detection
Slot type (period vs subject) is detected from actual `student_attendance_marked_records` for the
specific class. Falls back to all currently active `student_mark_type_statuses` rows (not just the
most recently enabled one) when no records exist yet.

### MySQL join-limit fix
Original approach used two LEFT JOINs per slot per day. A class with 8 subjects produces 83 joined
tables, exceeding MySQL's 61-table hard limit. Replaced with three bulk queries (students, mark
records for the week, absence details for the week) and PHP index-based assembly — no join limit.

### Grade → Class cascade
Institution Class dropdown filters by selected Education Grade using an INNER JOIN on
`institution_class_grades` in `onUpdateFieldInstitutionClassId`.

### Week dropdown
Generated from the academic period's `start_date` / `end_date` and the `first_day_of_week`
config item, mirroring the logic in the Laravel `AttendanceRepository`.

---

## 4. Files Changed

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/InstitutionStudentWeeklyAttendanceTable.php` | New file — report engine |
| `plugins/Institution/src/Model/Table/InstitutionStandardsTable.php` | Grade/Class/Week field handlers; Weekly Attendance added to required-field constants |
| `plugins/Institution/src/Controller/InstitutionsController.php` | Feature registered in `getInstitutionStatisticStandardReportFeature()` |
| `plugins/Report/src/Model/Behavior/ReportListBehavior.php` | Fix JSON_EXTRACT call that prevented the Standard Reports page from loading |

---

## 5. Database Migrations

None. No schema changes.

---

## 6. Deployment Instructions

1. Pull branch `POCOR-9611` to the application server.
2. Clear CakePHP cache: `php bin/cake.php cache clear_all`
3. No migrations to run.

---

## 7. System Administrator Guide

Navigate to **Institution > Statistics > Standard**.
Select feature **Students Weekly Attendance**.
Choose Academic Period, Education Grade, Institution Class, and Week, then click Generate.
The report downloads as an `.xlsx` file with colour-coded attendance cells.

**Status values:**

| Status | Meaning |
|--------|---------|
| PRESENT | Student was present |
| LATE | Student was late — also counted in Total present |
| EXCUSED | Excused absence |
| UNEXCUSED | Unexcused absence |
| NOTMARKED | Attendance not recorded for this slot |
| NO CLASS | No class scheduled — excluded from all totals |
