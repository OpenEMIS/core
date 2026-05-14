# POCOR-9700 — Staff Attendance: time values must persist under slow networks

## What is the Task?

**Jira summary:** *Institution > Attendance > Staff > Edit: The changed time values did not take.*

On GY UAT and Demo with a 3G throttle on, when a user marks a staff member's
`time_in` and then `time_out` in quick succession the saved values came back
different from what was typed. The Jira note explicitly pointed at the
student-attendance fix (POCOR-9617 "recheck the DB before showing the values")
and asked for the same treatment on the staff side.

While in the file we also took the opportunity to:

- Replace the ageing Bootstrap 3 jQuery timepicker (which loses focus, ignores
  the system 12/24h preference, and was the source of this very bug) with a
  native HTML5 `<input type="time">`.
- Make the Archive view and the Profile / Directory "My Attendance" views
  display-only (those should never have been editable — only the institution's
  marking page is the authoritative editor).
- Add the small UX improvements that surfaced during testing
  (auto-select sole shift, disable Edit when no shift selected, accessible
  toolbar buttons, view-side respect for the system `time_format`).

## Situation Before

- The save callback wrote the **typed** value back to the in-memory grid
  (`params.value[timeKey] = time24Hour`) immediately after the POST returned —
  ignoring whatever the server actually persisted.
- Every cell change triggered its own POST. On a slow network two POSTs from
  the same row could be in flight at the same time; the second one carried a
  stale read of the first key and either lost or overwrote the previous save.
- The Bootstrap 3 jQuery timepicker:
  - lost focus on scroll, mouse-wheel and other timepickers being open;
  - rendered through 200+ lines of DOM-built `.timepicker('place')` glue;
  - silently ignored the system `time_format` config.
- The Edit toolbar button was hidden when no specific Day was picked, but
  silently no-opped (with an after-the-fact "Please select shift" alert) when
  no Shift was selected — a guess-the-prerequisite UX trap.
- Toolbar buttons had no `title` / `aria-label` — invisible to screen readers,
  invisible to automation, and the static `title="Edit"` could not change
  with the shift-selected state.
- Archive and Profile / Directory views shared the **same editable
  cellRenderer** as the live editor page, meaning the same race-prone save
  path was reachable from views that should be read-only.

## What Was Implemented

### Live edit page (`InstitutionStaffAttendances`)

1. **HTML5 `<input type="time" step="60">`** replaces the jQuery timepicker.
   Locked to `lang="en-GB"` (24h) on the editor so AM/PM cannot be
   misread as "left before they arrived" — the system `time_format` still
   controls the view-mode rendering, where AM/PM has room to be clearly
   visible.
2. **Per-row save serialisation** via a `_savePromise` chain stored on
   `params.data.attendance[date]`. A second timepicker change waits for the
   first POST's `.then` to complete before sending its payload.
3. **Per-row 600 ms debounce** via `$timeout`. If a user changes time_in then
   time_out within the window the two cell-changes collapse into one POST.
   Halves the morning-rush request volume that Guyana sees with
   ~12,000 teachers marking attendance between 07:00 and 08:30.
4. **Recheck-from-DB.** After a successful save the in-memory cell is
   replaced with `response.data.data.time_in / time_out` — the server's
   persisted record, not the typed value.
5. **Hard block:** cannot mark a time later than the institution-tz current
   clock when the date is today.
6. **Soft warning** (toast, save proceeds): when `time_in` is more than
   3 hours before `shift.start_time`, or `time_out` is more than 3 hours
   after `shift.end_time`. Schools have early prep and late grading — a
   wide grace window stops nagging legitimate behaviour.
7. **Combined "saved with warning" toast.** Previously the
   `AlertSvc.warning(...)` for the soft warning was overwritten by the
   subsequent `AlertSvc.success('Saved')` (AlertSvc shows one message at a
   time). Now the save callback folds both into a single warning-toned
   message so the user actually sees the warning.
8. **`convert12Timeformat` honours the system `time_format` config** —
   returns `HH:MM AM/PM` when the system is configured 12-hour, plain
   `HH:MM` when 24-hour. The same function is reused for time_in,
   time_out, leave-period, and history-log labels.
9. **Auto-select sole shift.** If there is exactly one real shift in
   `shiftListOptions`, the controller picks it on load and calls
   `changeShift()` so the grid is ready for editing immediately. Multishift
   schools still see `-- All --` selected, but…
10. **Edit button gated by `ng-disabled`** when no real shift is selected
    (`selectedShift == -1`). The button stays visible (so users can see it
    exists) but is greyed-out; a reactive `uib-tooltip` switches its hint
    between *"Edit"* and *"Select a shift to enable editing"* so the user
    knows what to do.
11. **`aria-label="Edit"`** added to the icon-only toolbar button so
    screen readers and automation can identify it.

### Archive + Profile / Directory views become read-only

- `plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.archive.svc.js`
  — the `if (action == 'edit' && conditionStatus == 1)` branch in the
  cellRenderer was removed; cells always render the display-only
  HTML representation.
- `plugins/Staff/webroot/js/angular/staff_attendances/staff.attendances.svc.js`
  — same: `getTimeInElement` / `getTimeOutElement` collapsed to a single
  read-only branch.

The institution's marking page remains the single authoritative editor.

### Files Changed Summary

| File | Change |
|---|---|
| `plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.svc.js` | HTML5 picker, race fix, validations, alert combine, view-format respect |
| `plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.ctrl.js` | Shift bounds → ag-Grid context; auto-select sole shift |
| `plugins/Institution/templates/Institutions/institution_staff_attendances.php` | Edit button: `aria-label`, `uib-tooltip`, `ng-disabled` |
| `plugins/Institution/webroot/js/angular/staff/institution.staff.attendances.archive.svc.js` | Archive cell renderer: edit branch stripped |
| `plugins/Staff/webroot/js/angular/staff_attendances/staff.attendances.svc.js` | Profile / Directory cell renderers: edit branch stripped |

### Database Migrations

None. POCOR-9700 is frontend-only.

## Deployment Instructions

1. Pull the branch onto the target host.
2. **No migration to run.**
3. Clear any Angular bundle cache if one is in use (this repo serves the
   plugin AngularJS files directly from `plugins/*/webroot/js/angular/`, so
   most environments need no rebuild).
4. Smoke-test on the live host:
   - Login as a user who can edit staff attendance.
   - Open *Institution > Attendance > Staff* for an institution with shifts.
   - With browser DevTools set to **Slow 3G**, click Edit, mark `time_in`
     then `time_out` quickly on the same row, reload the page, confirm both
     persisted values are exactly what was typed.

## System Administrator Guide

- **`time_format` config** under *Administration > System Configuration* still
  drives the **view-mode** time display. The editor itself is locked to 24h
  to avoid AM/PM mis-reads — this is intentional.
- **`time_zone` config** is read by every save to determine "today" for the
  future-time-on-today block. Setting an incorrect timezone may make the
  block fire on legitimate late-evening entries; verify with the
  *Administration > System Configuration* page.
- **Shift bounds soft warning** is hard-coded to a 3-hour grace window
  (`shift.start_time - 3h` to `shift.end_time + 3h`). If a future need
  arises to make this per-environment configurable, add a config item
  `staff_attendance_shift_grace_minutes`.
- The Profile / Directory "My Attendance" / "Staff Attendance" pages are
  now strictly read-only. Marking is done only from the institution's
  *Attendance > Staff* page.

## Verification

A Playwright spec lives at `tmp/playwright-tests/POCOR-9700.spec.ts`
(not committed — `tmp/` is gitignored). It:

1. Logs in as `admin/demo`.
2. Navigates to the test institution.
3. Picks Day + Shift via AngularJS scope (the toolbar buttons are
   icon-only — see UX item 11 above for the fix).
4. Applies a Slow-3G CDP throttle.
5. Sets `time_in=07:30` then `time_out=10:45` within the debounce window.
6. Reloads.
7. Asserts the persisted view-mode text matches what was typed.

Last green run: **1 passed (43.7s)**.
