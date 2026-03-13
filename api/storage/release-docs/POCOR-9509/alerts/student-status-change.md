# Student Status Change — `StudentStatus`

> **Feature key:** `StudentStatus` · **Process:** `AlertStudentStatus`
> **Trigger:** Event-based · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student's enrolment status changes — for example from **Active** to **Transferred**, **Withdrawn**, **Graduated**, or **Promoted**. It ensures that the change is immediately visible to the relevant parties rather than being discovered during a periodic data review.

---

## Purpose

Student status changes carry downstream consequences:
- A **transferred** student must be removed from class lists and added at the new institution
- A **withdrawal** may require follow-up to understand the reason and prevent dropout
- A **promotion** or **graduation** triggers class reassignment and administrative processing

Without this alert, status changes sit in the database unnoticed until someone runs a report. The alert surfaces the change at the moment it is recorded, giving staff time to act appropriately.

---

## When and How It Fires

This is an **event-based** alert. It fires immediately when a student's `student_status_id` field is updated. The CakePHP student status table calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentStatus', ...)` in its `afterSave` callback, which dispatches the `alerts:student-status-change` artisan command.

The threshold can specify which status transitions to watch, enabling very targeted alerting.

---

## Frequency

**`Once` per event.** Each status change is a discrete, non-repeating event. The alert captures the transition — including the old and new values — as a point-in-time notification. Repeating it would lose that contextual value.

---

## Recipients

Security roles, scoped to the relevant institution. Appropriate recipients depend on the type of change:
- **Transfers / Withdrawals** — school administrators and ministry coordinators who need to update records
- **Promotions / Graduations** — class teachers and registry officers for class reassignment
- **Guardian roles** — for changes that require parent notification (depending on deployment policy)

Using separate rules per transition type allows each status change to reach the right audience.

---

## Threshold Configuration

The threshold defines which status transitions trigger the alert. The format specifies old and new status IDs:

```json
{"old_status_id": 1, "new_status_id": 7}
```

| Field | Description |
|-------|-------------|
| `old_status_id` | The status the student had **before** the change (from `student_statuses` table) |
| `new_status_id` | The status the student has **after** the change |

### Finding status IDs

```sql
SELECT id, name FROM student_statuses ORDER BY name;
```

Common status values (may vary by deployment):
| ID | Status |
|----|--------|
| 1 | Enrolled / Active |
| 2 | Promoted |
| 3 | Graduated |
| 4 | Transferred |
| 5 | Withdrawn |
| 6 | Repeated |

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"old_status_id": 1, "new_status_id": 5}` | Alert when an active student is withdrawn |
| `{"old_status_id": 1, "new_status_id": 4}` | Alert when an active student is transferred |
| `{"old_status_id": 1, "new_status_id": 3}` | Alert when a student graduates |
| _(omit both fields)_ | Alert on any status change |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${student.name}` | Student's full name |
| `${student.openemis_no}` | OpenEMIS ID |
| `${student.first_name}` | First name |
| `${student.last_name}` | Last name |
| `${student.email}` | Email address |
| `${student.date_of_birth}` | Date of birth |
| `${student.gender}` | Gender |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |

---

## Example Alert Rules

### Rule 1 — Withdrawal alert

| Field | Value |
|-------|-------|
| **Name** | Student Withdrawal — Administrator Alert |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"old_status_id": 1, "new_status_id": 5}` |
| **Security Roles** | Institution Administrator, District Coordinator |

**Subject:**
```
Student Withdrawal: ${student.name} has withdrawn from ${institution.name}
```

**Message body:**
```
Dear Colleague,

This is to notify you that the following student has been marked as Withdrawn
in OpenEMIS and requires administrative follow-up.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Institution: ${institution.name}

Required actions:
1. Remove the student from active class lists
2. Ensure all outstanding records are completed and archived
3. Follow up with the student's guardian to understand the reason for withdrawal
4. Document the outcome in the student's case record if applicable

Please log in to OpenEMIS to complete the necessary administrative steps.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Transfer alert

| Field | Value |
|-------|-------|
| **Name** | Student Transfer — Coordinator Notification |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"old_status_id": 1, "new_status_id": 4}` |
| **Security Roles** | District Coordinator, Registry Officer |

**Subject:**
```
Transfer Notification: ${student.name} transferred from ${institution.name}
```

**Message body:**
```
Dear Coordinator,

Student ${student.name} (${student.openemis_no}) has been transferred from
${institution.name}.

Please ensure the receiving institution has been notified and that the student's
academic records have been updated accordingly in OpenEMIS.

This is an automated notification from OpenEMIS.
```

### Rule 3 — Graduation alert

| Field | Value |
|-------|-------|
| **Name** | Student Graduation — Registry |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"old_status_id": 1, "new_status_id": 3}` |
| **Security Roles** | Registry Officer |

**Subject:**
```
Graduation Recorded: ${student.name} — ${institution.name}
```

**Message body:**
```
Student ${student.name} (${student.openemis_no}) has been marked as Graduated
at ${institution.name}.

Please process the graduation documentation and update the registry accordingly.
```

---

## Multiple Rules for One Alert

`StudentStatus` is one of the most versatile alert types because different transitions require different responses from different people. Using separate rules:

- **Withdrawal rule** → sends to institution admins with a detailed action checklist
- **Transfer rule** → sends to district coordinators for cross-institution coordination
- **Graduation rule** → sends to the registry for certification processing
- **Promotion rule** → sends to class teachers for class list updates

Each rule has its own threshold (the specific transition), its own message, and its own recipient roles. All rules for `StudentStatus` are evaluated at the same time — a graduation triggers the graduation rule only, not the withdrawal rule.

---

## Technical Notes

- Artisan command: `alerts:student-status-change`
- Dispatched from: student status update `afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-status-change \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
