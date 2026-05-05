# POCOR-8135 — Absent Count on Staff Attendance Mini Dashboard

## What is the Task?
Add a "No. of Staff Absent" counter to the mini dashboard on the Staff Attendance page
(Institutions > Attendance > Staff), matching the existing "Present" and "On Leave" counters.

## Situation Before
The mini dashboard showed:
- Total Staff
- No. of Present
- No. of Staff on Leave

There was no count for absent staff. Users had no way to see at a glance how many staff had neither checked in nor submitted leave.

## What Was Implemented

A staff member is counted **absent** when:
- `time_in` is empty/null (no check-in recorded), AND
- Their `leave` array is empty (no leave record for that slot)

### Logic Added (AngularJS controller)
`plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.ctrl.js`

- Initialised `vm.allAbsentCount = 0` alongside other counters
- Reset to `0` at the start of `setAllStaffAttendances()` before each recalculation
- Incremented inside the `angular.forEach` loop: if no `time_in` and no leave record → absent
- Falls back to `'-'` (dash) when count is zero after the loop (matches existing pattern for Present/Leave)

### UI Card Added (CakePHP template)
`plugins/Institution/templates/Institutions/institution_staff_attendances.php`

Added a fourth `data-section` div after the "On Leave" card:
```html
<div class="data-section">
    <div class="data-field">
        <h4>No. of Staff Absent</h4>
        <h1 class="data-header">{{$ctrl.allAbsentCount}}</h1>
    </div>
</div>
```

### Files Changed Summary
| File | Change |
|------|--------|
| `plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.ctrl.js` | Added absent counter init, reset, increment, dash fallback |
| `plugins/Institution/templates/Institutions/institution_staff_attendances.php` | Added "No. of Staff Absent" dashboard card |

### Database Migrations
None required.

## Deployment Instructions
1. Pull branch `POCOR-8135`
2. No build step required — AngularJS 1.x files are served directly
3. Clear browser cache if the old JS is cached

## System Administrator Guide
No configuration changes. The "No. of Staff Absent" counter appears automatically on the Staff Attendance mini dashboard and updates whenever the week, day, or shift filter changes.

## Known Technical Note
The staff attendance save function (`saveStaffAttendance`) does not perform a post-save DB verification step, unlike student attendance which re-reads the saved record from the DB and confirms the values match before updating the UI. Under high concurrent load (many users marking attendance and navigating away quickly), some staff attendance DB writes may silently fail without the UI reflecting this. This is a pre-existing gap in the staff attendance feature, not introduced by this ticket.
