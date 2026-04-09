# Staff Attendance — `StaffAttendance`

> **Feature key:** `StaffAttendance` · **Process:** `AlertStaffAbsence`
> **Status:** ⚠️ NOT IMPLEMENTED — planned for a future release
> **Default frequency:** `Never` (locked)

---

## What It Is

A planned alert for notifying administrators when a staff member has been absent from work for a number of days meeting or exceeding a configured threshold. It is the staff-side equivalent of the [Student Absence](student-absence.md) alert.

---

## Current Status

**This alert has not been implemented.** The `alerts` table contains a row for `StaffAttendance`, and the alert is visible in the OpenEMIS Alerts management screen, but:

- No processing command (`AlertStaffAttendanceCommand`) has been built
- The frequency is locked to `Never` by `AlertsTable::NON_IMPLEMENTED_ALERTS`
- The frequency cannot be changed in the UI until the command is implemented
- No alert rules for this feature will be executed even if created

---

## Planned Purpose

When implemented, this alert would:
- Monitor `institution_staff_attendances` for absences above a configured threshold
- Fire when a staff member's consecutive or cumulative absences reach the threshold
- Notify school administrators and HR officers so they can follow up
- Help institutions track patterns of staff absence that may indicate welfare concerns or performance issues

---

## Planned Threshold Format

Based on the pattern established by the Student Absence alert, the threshold would likely be a simple integer representing the number of absence days:

```
threshold: 3   →   alert when staff member reaches 3 absence days
```

Or a JSON object if additional fields (e.g., leave type exclusion, consecutive vs cumulative) are needed:

```json
{"value": 3, "consecutive": true}
```

The exact format will be defined when the command is implemented.

---

## When It Will Be Available

This alert requires:
1. A `AlertStaffAttendanceCommand.php` to be written in `api/app/Console/Commands/Alerts/`
2. Registration in `AlertLogsTable::triggerAlertCommand()` or `CheckAndQueueAlerts::queueAlertCommand()` depending on whether it becomes event-based or scheduled
3. Removal of `StaffAttendance` from `AlertsTable::NON_IMPLEMENTED_ALERTS`

If you require this functionality urgently, raise a ticket with your OpenEMIS support contact referencing the `StaffAttendance` / `AlertStaffAbsence` feature key.

---

## Workaround

Until this alert is available, staff absence tracking can be managed through:
- Manual reports: **Reports → Staff → Attendance**
- Scheduled report exports reviewed by HR periodically
- Workflow-based case creation when absences are recorded (using the Case Escalation alert as a follow-up mechanism)
