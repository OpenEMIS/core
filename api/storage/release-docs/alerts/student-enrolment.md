# Student Enrolment — `StudentEnrolment`

> **Feature key:** `StudentEnrolment` · **Process:** `AlertStudentEnrolment`
> **Trigger:** Event-based (workflow step match) · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student enrolment application reaches one of the configured workflow steps. It notifies the student and/or their guardians that their enrolment has been processed, approved, or rejected.

---

## Purpose

In systems where enrolment is processed centrally (district or ministry level), the student and their family may not know the enrolment has been actioned until they check manually. This alert bridges that gap — the student and their guardians are informed immediately when the enrolment reaches a significant step (e.g., Approved, Rejected).

---

## When and How It Fires

This is an **event-based** alert. The CakePHP `StudentEnrolmentTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentEnrolment', ...)` in its `afterSave` callback, which dispatches the `alerts:student-enrolment` artisan command.

The command then checks the enrolment's current `status_id` against the `workflow_steps` list in the threshold JSON. **If the current status is not in the configured workflow steps, the alert is suppressed.** The threshold is required — if no workflow steps are configured, no alerts will fire.

---

## Frequency

**`Once` per event.** Each workflow step transition is a discrete event. The `Once` model ensures the notification goes out exactly when the enrolment moves to a qualifying step.

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
{"workflow_steps": [136]}
```

| Field | Description |
|-------|-------------|
| `workflow_steps` | Array of `workflow_steps.id` values from the `Student Enrolment` workflow |

### Student Enrolment workflow step IDs

These are the IDs configured in the `workflow_steps` table for the `Student Enrolment` workflow:

| ID | Step name |
|----|-----------|
| 134 | Open |
| 135 | Pending Approval |
| 136 | Approved |
| 137 | Rejected |
| 138 | Pending Cancellation |
| 139 | Cancelled |

> **Important:** These IDs were seeded during installation. On older deployments the IDs may differ — always query `SELECT id, name FROM workflow_steps WHERE workflow_id = (SELECT id FROM workflows WHERE name = 'Student Enrolment')` to confirm the correct IDs for your system. Alert rules configured with wrong step IDs will silently not fire.

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"workflow_steps": [136]}` | Alert when enrolment is Approved |
| `{"workflow_steps": [137]}` | Alert when enrolment is Rejected |
| `{"workflow_steps": [136, 137]}` | Alert on either Approved or Rejected |
| `{"workflow_steps": [134, 135, 136, 137, 138, 139]}` | Alert on any step |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${enrolment_status}` | Current enrolment workflow step name |
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

### Enrolment approved — notification to student and guardian

| Field | Value |
|-------|-------|
| **Name** | Student Enrolment — Approved |
| **Feature** | StudentEnrolment |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"workflow_steps": [136]}` |
| **Security Roles** | Student (role 8), Guardian (role 9) |

**Subject:**
```
Your Enrolment Has Been Approved — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your enrolment at ${institution.name} has been approved.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Academic Period: ${academic_period.name}
Grade: ${grade.name}
Start Date: ${start_date}

Please log in to OpenEMIS or contact the institution for further instructions.

This is an automated notification from OpenEMIS.
```

### Enrolment rejected — notification to student and guardian

| Field | Value |
|-------|-------|
| **Name** | Student Enrolment — Rejected |
| **Feature** | StudentEnrolment |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"workflow_steps": [137]}` |
| **Security Roles** | Student (role 8), Guardian (role 9) |

**Subject:**
```
Enrolment Application Update — ${institution.name}
```

**Message body:**
```
Dear ${student.name},

Your enrolment application to ${institution.name} has not been approved at this time.

Please contact ${institution.name} directly for further information.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Multiple rules for `StudentEnrolment` allow you to:

- Send a **detailed notification** to the student when Approved (`{"workflow_steps": [136]}`, role 8)
- Send a **brief summary** to the guardian for awareness (`{"workflow_steps": [136]}`, role 9)
- Send a **rejection notice** to student and guardian (`{"workflow_steps": [137]}`, roles 8 and 9)

Each rule is completely independent — different name, audience, message content, and delivery method.

---

## Technical Notes

- Artisan command: `alerts:student-enrolment`
- Dispatched from: `StudentEnrolmentTable::afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`, `--entity_id`
- `--entity_id` is the `institution_student_enrolment.id` of the record that was saved
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-enrolment \
     --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<enrolment_id>"
  ```
