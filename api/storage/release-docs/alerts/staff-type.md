# Staff Type — `StaffType`

> **Feature key:** `StaffType` · **Process:** `AlertStaffType`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`

---

## What It Is

An alert triggered when a staff member on a specific contract type (e.g., temporary, probationary, fixed-term) approaches a review or end-of-type deadline. It ensures that HR and administrators monitor time-limited contracts and take action before deadlines pass unnoticed.

---

## Purpose

Probationary and temporary contracts serve specific HR purposes — they define a review point at which a decision must be made: convert to permanent, extend, or terminate. Without active monitoring, these review points pass silently. The Staff Type alert converts passive contract tracking into proactive notifications, ensuring every time-limited contract receives the attention it requires.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:staff-type` at the configured frequency. The command scans `institution_staff` for records matching the configured `staff_type_id` and approaching the relevant date within `threshold.value` days.

---

## Frequency

**`Daily`** is the standard frequency. Contract type review deadlines are date-based conditions — the system must check continuously to catch each record as it enters the alert window. Daily scanning prevents any staff member from slipping through the monitoring window.

---

## Recipients

Security roles scoped to **the staff member's institution** — the HR officers and administrators directly responsible for managing that type of contract at that institution.

---

## Threshold Configuration

The threshold specifies the time window and which staff type to monitor.

```json
{"value": 30, "staff_type_id": 2}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Days before the relevant date |
| `staff_type_id` | Integer | Yes | ID from `staff_types` table |

### Finding staff type IDs

```sql
SELECT id, name FROM staff_types ORDER BY name;
```

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 30, "staff_type_id": 2}` | Alert 30 days before a probationary contract review |
| `{"value": 14, "staff_type_id": 3}` | Alert 14 days before a temporary contract end |
| `{"value": 60, "staff_type_id": 4}` | Alert 60 days before a fixed-term contract end |

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

### Rule 1 — Probationary contract review

| Field | Value |
|-------|-------|
| **Name** | Probation Review Due — 30 Days |
| **Feature** | StaffType |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 30, "staff_type_id": 2}` |
| **Security Roles** | HR Officer, Institution Principal |

**Subject:**
```
Probation Review Due: ${user.first_name} ${user.last_name} — ${threshold.value} days
```

**Message body:**
```
Dear HR Officer,

A probationary period review is due within ${threshold.value} days for the
following staff member at ${institution.name}.

Staff Member: ${user.first_name} ${user.last_name}
OpenEMIS ID: ${user.openemis_no}
Institution: ${institution.name}

Actions required:
1. Schedule a formal probation review meeting
2. Assess performance against the criteria set at the start of the probationary period
3. Document the outcome: confirm permanent appointment, extend probation, or terminate
4. Update the staff type and employment record in OpenEMIS accordingly

Please complete the review before the probationary end date. Failure to act
may result in automatic contract status changes under local labour regulations.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Temporary contract ending

| Field | Value |
|-------|-------|
| **Name** | Temporary Contract End — 14 Days |
| **Feature** | StaffType |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 14, "staff_type_id": 3}` |
| **Security Roles** | HR Officer, Institution Administrator |

**Subject:**
```
Temporary Contract Ending: ${user.first_name} ${user.last_name} in ${threshold.value} days
```

**Message body:**
```
Dear Administrator,

${user.first_name} ${user.last_name} (${user.openemis_no}) is on a temporary
contract at ${institution.name} that ends in ${threshold.value} days.

Please ensure one of the following actions is taken:
- Issue a contract extension if additional coverage is still required
- Begin recruiting a permanent replacement if applicable
- Arrange a final handover meeting with the staff member
- Update OpenEMIS with the correct end date and status

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Create separate rules for each staff type that requires monitoring:

- **Probationary rule** (`staff_type_id` for probation, 30 days) → HR review workflow
- **Temporary rule** (`staff_type_id` for temporary, 14 days) → contract extension or recruitment
- **Fixed-term rule** (`staff_type_id` for fixed-term, 60 days) → longer lead time for planned transitions

Each rule targets a different staff type, carries type-appropriate instructions, and can reach different roles depending on who is responsible for each type of contract at your institution.

---

## Technical Notes

- Artisan command: `alerts:staff-type`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:staff-type \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
