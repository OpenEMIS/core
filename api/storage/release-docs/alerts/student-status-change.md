# Student Status Change — `StudentStatus`

> **Feature key:** `StudentStatus` · **Process:** `AlertStudentStatus`
> **Trigger:** Event-based (student status match) · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student's enrolment record (`institution_students`) is saved with a `student_status_id` that matches one of the statuses configured in the threshold. It notifies the student and/or their guardians that a status change — such as Transfer, Withdrawal, or Graduation — has been recorded.

---

## Purpose

Student status changes carry downstream consequences:
- A **transferred** student must be removed from class lists and added at the new institution
- A **withdrawal** may require follow-up to understand the reason and prevent dropout
- A **promotion** or **graduation** triggers class reassignment and administrative processing

Without this alert, status changes sit in the database unnoticed until someone runs a report. The alert surfaces the change at the moment it is recorded, giving the student and guardians timely information.

---

## When and How It Fires

This is an **event-based** alert. The CakePHP `StudentsTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentStatus', ...)` in its `afterSave` callback when it processes an `institution_students` record, which dispatches the `alerts:student-status-change` artisan command.

The command then checks the `institution_students.student_status_id` of the saved record against the `statuses` list in the threshold JSON. **If the current status ID is not in the configured list, the alert is suppressed.** The threshold is required — if no statuses are configured, no alerts will fire.

---

## Frequency

**`Once` per event.** Each status change is a discrete, non-repeating event. The alert fires when the record is saved with a matching status.

---

## Recipients

Recipients are resolved via **student-associated contact lookup** (`getStudentAssociatedContactList`). This method **only** resolves:

- **The student themselves** — when security role ID `8` (Student) is assigned to the rule
- **The student's guardians** — when security role ID `9` (Guardian) is assigned to the rule

Other security roles added to the rule are ignored. If neither role 8 nor 9 is in the rule's security roles, no recipients will be found and no alert will be sent.

---

## Threshold Configuration

The threshold defines which **student statuses** should trigger the alert. The format is a JSON object with an array of `student_statuses.id` values:

```json
{"statuses": [4]}
```

| Field | Description |
|-------|-------------|
| `statuses` | Array of `student_statuses.id` values that should trigger the alert |

### Finding status IDs

```sql
SELECT id, name FROM student_statuses ORDER BY id;
```

Status IDs from this deployment:

| ID | Status |
|----|--------|
| 1 | Enrolled |
| 3 | Transferred |
| 4 | Withdrawn |
| 6 | Graduated |
| 7 | Promoted |
| 8 | Repeated |

> **Important:** These IDs were seeded during installation and can vary significantly between deployments. Always query `SELECT id, name FROM student_statuses` to confirm the correct IDs for your system before configuring alert thresholds. Alert rules configured with wrong status IDs will silently not fire.

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"statuses": [4]}` | Alert when a student is marked Withdrawn |
| `{"statuses": [3]}` | Alert when a student is Transferred |
| `{"statuses": [6]}` | Alert when a student Graduates |
| `{"statuses": [3, 4]}` | Alert on either Transfer or Withdrawal |
| `{"statuses": [1, 3, 4, 6, 7, 8]}` | Alert on any status change |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${student_status}` | Student status name (e.g., "Withdrawn") |
| `${academic_period.name}` | Academic period name |
| `${start_date}` | Student study start date |
| `${end_date}` | Student study end date |
| `${student.name}` | Student's full name |
| `${student.openemis_no}` | OpenEMIS ID |
| `${student.first_name}` | First name |
| `${student.middle_name}` | Middle name |
| `${student.third_name}` | Third name |
| `${student.last_name}` | Last name |
| `${student.preferred_name}` | Preferred name |
| `${student.email}` | Email address |
| `${student.address}` | Address |
| `${student.postal_code}` | Postal code |
| `${student.date_of_birth}` | Date of birth |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.postal_code}` | Institution postal code |
| `${institution.contact_person}` | Institution contact person |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${institution.website}` | Institution website |
| `${grade.name}` | Education grade name |
| `${guardian.name}` | Guardian full names (comma-separated if multiple) |
| `${guardian.relation}` | Guardian relation types (comma-separated) |
| `${guardian.contact}` | Guardian contacts — email and/or mobile (comma-separated) |

---

## Example Alert Rules

### Rule 1 — Withdrawal alert

| Field | Value |
|-------|-------|
| **Name** | Student Withdrawal — Notify Guardian |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"statuses": [4]}` |
| **Security Roles** | Guardian (role 9) |

**Subject:**
```
Important: ${student.name} has been marked as Withdrawn
```

**Message body:**
```
Dear ${guardian.name},

This is to notify you that ${student.name} (${student.openemis_no}) has been
marked as Withdrawn at ${institution.name}.

If you have questions about this change, please contact the institution directly.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Transfer alert

| Field | Value |
|-------|-------|
| **Name** | Student Transfer — Notify Student and Guardian |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"statuses": [3]}` |
| **Security Roles** | Student (role 8), Guardian (role 9) |

**Subject:**
```
Transfer Confirmation: ${student.name} — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your transfer from ${institution.name} has been recorded in OpenEMIS.

Academic Period: ${academic_period.name}
Grade: ${grade.name}

Please ensure your new institution has your academic records.

This is an automated notification from OpenEMIS.
```

### Rule 3 — Graduation alert

| Field | Value |
|-------|-------|
| **Name** | Student Graduation — Notify Student |
| **Feature** | StudentStatus |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"statuses": [6]}` |
| **Security Roles** | Student (role 8) |

**Subject:**
```
Congratulations: Graduation Recorded — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your graduation from ${institution.name} has been recorded in OpenEMIS.

OpenEMIS ID: ${student.openemis_no}
Institution: ${institution.name}

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

`StudentStatus` is one of the most versatile alert types because different transitions require different responses. Using separate rules:

- **Withdrawal rule** → `{"statuses": [4]}` — sends to guardian with follow-up instructions
- **Transfer rule** → `{"statuses": [3]}` — sends to student and guardian confirming the transfer
- **Graduation rule** → `{"statuses": [6]}` — sends to student as a congratulatory notice
- **Promotion rule** → `{"statuses": [7]}` — sends to student confirming class advancement

Each rule has its own threshold, its own message, and its own recipient roles. All rules for `StudentStatus` are evaluated when the alert fires — each rule matches independently based on its own configured statuses.

---

## Technical Notes

- Artisan command: `alerts:student-status-change`
- Dispatched from: `StudentsTable::afterSave()` (processes `institution_students` records)
- Required parameters: `--user_id`, `--rule_id`, `--process_id`, `--entity_id`
- `--entity_id` is the `institution_students.id` of the record that triggered the status change
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-status-change \
     --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<institution_students_id>"
  ```
