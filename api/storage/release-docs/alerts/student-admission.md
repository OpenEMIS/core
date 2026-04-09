# Student Admission — `StudentAdmission`

> **Feature key:** `StudentAdmission` · **Process:** `AlertStudentAdmission`
> **Trigger:** Event-based (workflow step match) · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student admission application reaches one of the configured workflow steps. It notifies the student and/or their guardians so they are informed of the admission status as it progresses through the approval workflow.

---

## Purpose

In most education systems, student admission is a time-sensitive process. Applications need to be reviewed, documents verified, and decisions communicated. Without a notification mechanism, applicants may not know their application has moved. This alert ensures the student and their guardians are informed the moment the application advances to a significant step (e.g., Approved, Rejected).

---

## When and How It Fires

This is an **event-based** alert. The CakePHP `StudentAdmissionTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentAdmission', ...)` in its `afterSave` callback, which dispatches the `alerts:student-admission` artisan command.

The command then checks the admission's current `status_id` against the `workflow_steps` list in the threshold JSON. **If the current status is not in the configured workflow steps, the alert is suppressed.** The threshold is required — if no workflow steps are configured, no alerts will fire.

---

## Frequency

**`Once` per event.** Each workflow step transition is a discrete event. The `Once` model ensures the notification goes out exactly when the application moves to a qualifying step.

---

## Recipients

Recipients are resolved via **student-associated contact lookup** (`getStudentAssociatedContactList`). This method **only** resolves:

- **The student themselves** — when security role ID `8` (Student) is assigned to the rule
- **The student's guardians** — when security role ID `9` (Guardian) is assigned to the rule

Other security roles added to the rule are ignored. If neither role 8 nor 9 is in the rule's security roles, no recipients will be found and no alert will be sent.

---

## Threshold Configuration

The threshold defines which **workflow steps** should trigger the alert. The format is a JSON object with an array of step IDs:

```json
{"workflow_steps": [82]}
```

| Field | Description |
|-------|-------------|
| `workflow_steps` | Array of `workflow_steps.id` values from the `Student Admission` workflow |

### Student Admission workflow step IDs

These are the IDs configured in the `workflow_steps` table for the `Student Admission` workflow:

| ID | Step name |
|----|-----------|
| 80 | Open |
| 81 | Pending Approval |
| 82 | Approved |
| 83 | Rejected |
| 84 | Pending Cancellation |
| 85 | Cancelled |

> **Important:** These IDs were seeded during installation. On older deployments the IDs may differ — always query `SELECT id, name FROM workflow_steps WHERE workflow_id = (SELECT id FROM workflows WHERE name = 'Student Admission')` to confirm the correct IDs for your system. Alert rules configured with wrong step IDs will silently not fire.

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"workflow_steps": [82]}` | Alert when admission is Approved |
| `{"workflow_steps": [83]}` | Alert when admission is Rejected |
| `{"workflow_steps": [82, 83]}` | Alert on either Approved or Rejected |
| `{"workflow_steps": [80, 81, 82, 83, 84, 85]}` | Alert on any step |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${admission_status}` | Current admission workflow step name |
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
| `${guardian.name}` | Guardian full name |
| `${guardian.relation}` | Guardian relation type |
| `${guardian.contact}` | Guardian contact (from `user_contacts`) |

---

## Example Alert Rule

### Admission approved notification to student/guardian

| Field | Value |
|-------|-------|
| **Name** | Student Admission — Approved |
| **Feature** | StudentAdmission |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"workflow_steps": [82]}` |
| **Security Roles** | Student (role 8), Guardian (role 9) |

**Subject:**
```
Your Admission Application Has Been Approved — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your admission application to ${institution.name} has been approved.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Academic Period: ${academic_period.name}
Grade: ${grade.name}
Start Date: ${start_date}

Please log in to OpenEMIS or contact the institution for further instructions.

This is an automated notification from OpenEMIS.
```

### Rejected notification to student/guardian

| Field | Value |
|-------|-------|
| **Name** | Student Admission — Rejected |
| **Feature** | StudentAdmission |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"workflow_steps": [83]}` |
| **Security Roles** | Student (role 8), Guardian (role 9) |

**Subject:**
```
Your Admission Application — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your admission application to ${institution.name} has not been approved at this time.

Please contact ${institution.name} directly for further information.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

You can configure multiple rules for the same `StudentAdmission` feature — for example:

- **Rule 1** — Email to student when Approved (threshold: `{"workflow_steps": [82]}`)
- **Rule 2** — Email to guardian when Approved (same threshold, role 9)
- **Rule 3** — Email to student when Rejected (threshold: `{"workflow_steps": [83]}`)

Each rule can target different roles, use a different method (Email vs SMS), and carry a completely different message tailored to the audience's needs.

---

## Technical Notes

- Artisan command: `alerts:student-admission`
- Dispatched from: `StudentAdmissionTable::afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`, `--entity_id`
- `--entity_id` is the `institution_student_admission.id` of the record that was saved
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-admission \
     --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<admission_id>"
  ```
