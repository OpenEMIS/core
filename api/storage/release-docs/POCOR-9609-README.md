# POCOR-9609 — No Scheduled Classes: State Not Persisting on Navigation

## 1. What is the Task?

**Attendance > Students > Edit > Mark Attendance > Click "No Scheduled Classes"**

When a user marks a day's attendance as "No Scheduled Classes" (all students → "No Lessons"), navigating to a different date and returning to the same date reset the view back to regular attendance instead of preserving the "No Lessons" state.

## 2. Situation Before

- User clicks "No Scheduled Classes" button → all students display "No Lessons" ✓
- User changes Day filter to another date → then changes back to the original date
- **Bug:** Page renders regular attendance (Present/Absent) instead of "No Lessons"
- Root cause: `getViewAttendanceElement()` checked `data.is_NoClassScheduled == 1` as the fallback condition — but PHP returns the field as `no_scheduled_class`, not `is_NoClassScheduled`. The JS field name was stale from an old commented-out implementation. The "No Lessons" display only worked on initial click because `noScheduledClicked=true` was passed from `onNoScheduledClick()` directly, but not when `changeDay()` reloads the grid (which calls `setColumnDef()` without argument).

## 3. What Was Implemented

### Root cause fix (JS)

**File:** `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.svc.js`

Changed two occurrences of the stale field name `data.is_NoClassScheduled` → `data.no_scheduled_class` (the actual field returned by PHP in the student query):

1. In `getViewAttendanceElement()` — the primary condition for rendering "No Lessons" in view mode when attendance is marked
2. In the NOTMARKED rendering branch — secondary condition for unmark-state display

Now when `changeDay()` reloads the grid, the `no_scheduled_class: 1` value already present in each student row from the PHP query is correctly detected, and "No Lessons" is shown without requiring a separate API call.

### UI improvement

**File:** `plugins/Institution/templates/Institutions/student_attendances.php`

Reduced the default width of the right filter panel from 20% → 15% (`min-size-p` and `size-p` attributes on the filter `bg-pane`).

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/Institution/webroot/js/angular/student_attendances/institution.student.attendances.svc.js` | Fix field name `is_NoClassScheduled` → `no_scheduled_class` (2 places) |
| `plugins/Institution/templates/Institutions/student_attendances.php` | Filter panel width: 20% → 15% |

### Database Migrations

None required.

## 4. Deployment Instructions

1. `git pull origin POCOR-9609`
2. No migrations required.
3. Clear CakePHP cache: `./bin/cake cache clear_all` (inside container at `/var/www/html/emis/core`)
4. Hard-refresh the browser (Ctrl+Shift+R) to clear cached AngularJS files.
5. Smoke test: Institution > Attendance > Students → select a day → click "No Scheduled Classes" → navigate to another day → return to original day → confirm "No Lessons" is shown for all students.

## 5. System Administrator Guide

No configuration changes. The fix is purely client-side JS and a template attribute change. Existing saved "No Scheduled Classes" records in `student_attendance_marked_records` will now display correctly on page load without requiring the user to re-click the button.
