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
   The wire value is always 24h `HH:MM` (per W3C spec) regardless of
   how the browser displays it, so save / round-trip is locale-independent.
   The system `time_format` config still drives **view-mode** rendering.
   See *Browser locale gotcha* below — HTML5 picker **display** (12 vs 24h
   AM/PM) is decided by the user's OS region locale and cannot be
   overridden from HTML/CSS/JS (per MDN); the underlying value is always
   24h, so all the validation, save and round-trip code is locale-safe.
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

   The 12h vs 24h detection tests the format string against `/[hgaA]/`
   (PHP date chars). Lowercase `h` or `g` indicates 12-hour even
   without an `A` meridian token, so a misconfigured `h:i:s` is
   classified correctly rather than silently passing as 24h.

   A `normalizeTo24Hour()` helper canonicalises every time value that
   arrives from the server before it is rendered or compared — strings
   like `"03:00:00 PM"` are converted to `"15:00:00"` first, so the
   downstream `:`-split parser never mis-reads a PM time as AM.
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

### Edit-cell layout (CSS)

The native HTML5 picker exposed three layout issues that did not exist
with the old jQuery widget. All three are fixed in
`institution_staff_attendances.php`:

- **Two clock icons.** Chrome renders its own
  `::-webkit-calendar-picker-indicator` next to a `<input type="time">`.
  The existing blue glyphicon-time addon (kept for visual consistency
  with other OpenEMIS forms) made it a double clock. The native
  indicator is hidden via
  `#institution-staff-attendances-table .timPikr::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }`
  — the blue addon receives the click and focuses the input.
- **Empty gap between input and addon.** Bootstrap 3 makes
  `.input-group` `display: table; width: 100%`. Inside the wide
  ag-Grid cell the wrapper stretched to fill, leaving an empty band
  between the (fixed-width) input and the (content-sized) addon. Now
  `.input-group.time { display: flex; width: fit-content; gap: 4px; }`
  — block-level flex stacks Time In above Time Out, `fit-content`
  shrink-wraps to the children, and the 4 px gap gives a hair-line
  breathing room.
- **Glyph misaligned inside the stretched blue addon.** Once the
  wrapper became flex, the addon (a flex item) stretched to match the
  input's height but its inherited `vertical-align: middle` no longer
  applied (that only honours inline / table-cell). The glyph drifted
  to the top per its `line-height: 1`. The addon is now itself a
  centering flex container:
  `.input-group-addon { display: flex; align-items: center; justify-content: center; }`.

Adjacent-sibling margin
(`.input-group.time + .input-group.time { margin-top: 4px; }`) gives
Time In and Time Out the same 4 px vertical separation as the
horizontal input ↔ addon gap, so the two blue clocks no longer abut
and read as one tall block.

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
| `plugins/Institution/templates/Institutions/institution_staff_attendances.php` | Edit button: `aria-label`, `uib-tooltip`, `ng-disabled`; native picker indicator hidden; flex layout + gaps; addon-centering CSS |
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

- **`time_format` config** under *Administration > System Configuration*
  drives the **view-mode** time display. Recommended values:
  - `H:i:s` — 24-hour, canonical, the OOTB default. Recommended.
  - `h:i:s A` — 12-hour with `AM`/`PM` suffix. Round-trip-safe.
  - **Avoid `h:i:s` (no `A`).** This is 12-hour **without** a meridian
    token. The server then emits `"03:00:00"` for both 3 AM and 3 PM,
    and the meridian is unrecoverable on read-back. The 9700 release
    detects this misconfiguration and treats it as 12-hour, but it
    still cannot reconstruct the lost meridian. If you find this value
    in `config_items` flip it to one of the two above.
- **Browser locale gotcha (macOS / Windows).** HTML5 `<input type="time">`
  decides 12 vs 24h **display** from the operating-system region
  locale, not from any HTML attribute. Per MDN this is true for
  Chrome, Firefox and Edge. On macOS, the *System Settings → Date & Time
  → 24-hour time* toggle alone is **not enough** — it changes the
  menu-bar clock but not Chromium's form widget. To force 24h display
  in the picker, set *System Settings → General → Language & Region →
  Region* to a 24h-default region (UK, Germany, France, …) and quit
  Chrome fully before relaunching. **The underlying value is always
  24h regardless of display**, so save and round-trip are correct on
  any locale.
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
