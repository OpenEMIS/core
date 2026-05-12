# Staff Leave End — `StaffLeave`

> **Feature key:** `StaffLeave` · **Process:** `AlertStaffLeave`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`

---

## What It Is

A reminder alert sent a configured number of days before a staff member's approved leave period is due to end. It helps institutions prepare for the staff member's return — arranging handovers, notifying substitutes, and updating schedules.

---

## Purpose

When a staff member is on leave, a substitute or adjusted schedule is typically in place. A sudden return without advance notice disrupts both the returning staff member and the institution. This alert ensures that:
- Substitutes are informed their engagement is ending
- The institution can prepare for the returning staff member (room, schedule, materials)
- The returning staff member's manager is ready to receive them
- Any handover tasks can be arranged in advance

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:staff-leave` at the configured frequency. The command queries `institution_staff_leave` for records with an **approved** workflow status where `date_to` equals today + `threshold.value` days.

Only approved leave records are included — pending or rejected leave applications do not trigger this alert.

The threshold includes a `staff_leave_type` field, so separate rules can be configured for different leave categories (maternity, medical, study, etc.).

---

## Frequency

**`Daily`** is the standard frequency. The target date is precise — the alert should fire on a specific day relative to the return date. Daily scanning ensures it fires on the correct day regardless of when the leave was originally approved or recorded.

---

## Recipients

Security roles scoped to **the staff member's institution**. School administrators and HR officers hold the operational responsibility for scheduling around leave absences.

The leave type field in the threshold enables different rules for different leave categories — for example, maternity leave notifications may involve a different role or carry different instructions than sick leave.

---

## Threshold Configuration

The threshold specifies how many days before leave ends to alert, and optionally filters by leave type.

```json
{"value": 3, "staff_leave_type": 2}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Days before leave end date |
| `staff_leave_type` | Integer | No | ID from `staff_leave_types` table — omit to alert for all leave types |

### Finding leave type IDs

```sql
SELECT id, name FROM staff_leave_types ORDER BY name;
```

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 3}` | Alert 3 days before any approved leave ends |
| `{"value": 5, "staff_leave_type": 1}` | Alert 5 days before maternity leave ends |
| `{"value": 1, "staff_leave_type": 3}` | Alert 1 day before sick leave ends |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${user.openemis_no}` | Staff OpenEMIS ID |
| `${user.first_name}` | First name |
| `${user.last_name}` | Last name |
| `${user.email}` | Email address |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${threshold.value}` | Configured threshold (days) |

---

## Example Alert Rules

### Rule 1 — General leave return notice

| Field | Value |
|-------|-------|
| **Name** | Staff Leave Return — 3 Day Notice |
| **Feature** | StaffLeave |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 3}` |
| **Security Roles** | Institution Administrator, HR Officer |

**Subject:**
```
Staff Return Notice: ${user.first_name} ${user.last_name} returns in ${threshold.value} days
```

**Message body:**
```
Dear Administrator,

This is a reminder that the following staff member's leave period is due to
end in ${threshold.value} days.

Staff Member: ${user.first_name} ${user.last_name}
OpenEMIS ID: ${user.openemis_no}
Institution: ${institution.name}

Actions to complete before the staff member returns:
- Notify the current substitute that their engagement is ending
- Prepare the staff member's timetable and room assignments
- Brief the staff member on any changes that occurred during their absence
- Update the leave record in OpenEMIS once the return is confirmed

This is an automated notification from OpenEMIS.
```

### Rule 2 — Extended leave (maternity/paternity) return notice

| Field | Value |
|-------|-------|
| **Name** | Maternity Leave Return — 14 Day Notice |
| **Feature** | StaffLeave |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 14, "staff_leave_type": 1}` |
| **Security Roles** | HR Officer, Institution Principal |

**Subject:**
```
Maternity Leave Ending: ${user.first_name} ${user.last_name} — return in ${threshold.value} days
```

**Message body:**
```
Dear HR Officer,

${user.first_name} ${user.last_name} (${user.openemis_no}) is due to return
from maternity leave in ${threshold.value} days at ${institution.name}.

For extended leave returns, please ensure the following have been arranged:
- Formal return-to-work meeting scheduled
- Phased return arrangement confirmed if applicable
- Teaching schedule updated and communicated
- Any statutory rights or benefits reviewed

Please update the leave record in OpenEMIS once the return date is confirmed
or if the leave period is being extended.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Using multiple rules for `StaffLeave` allows leave type–specific handling:

- **General rule** (all leave types, 3 days) → catches all leave returns with a standard notice
- **Maternity/paternity rule** (specific leave type, 14 days) → longer advance notice with return-to-work guidance
- **Medical/sick leave rule** (specific leave type, 1 day) → last-day reminder for short-term absences
- **Study leave rule** (specific leave type, 7 days) → notice with schedule restoration instructions

Each rule targets a specific leave type by setting the `staff_leave_type` field in its threshold. Rules without a `staff_leave_type` catch all leave types not handled by a more specific rule.

---

## Technical Notes

- Artisan command: `alerts:staff-leave`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:staff-leave \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
