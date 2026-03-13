# Student Admission — `StudentAdmission`

> **Feature key:** `StudentAdmission` · **Process:** `AlertStudentAdmission`
> **Trigger:** Event-based · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student admission application is created or changes status in the system. It notifies the relevant staff so they can review documentation, allocate places, and progress the application through the approval workflow without delay.

---

## Purpose

In most education systems, student admission is a time-sensitive process. Applications need to be reviewed, documents verified, and decisions communicated. Without a notification mechanism, applications can sit unnoticed in the system. This alert ensures the responsible staff are informed the moment an application enters the pipeline.

---

## When and How It Fires

This is an **event-based** alert. It fires immediately when a `StudentAdmission` record is saved (created or its status updated). The CakePHP `StudentAdmissionTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentAdmission', ...)` in its `afterSave` callback.

There is no threshold check — the alert fires for every qualifying save event.

---

## Frequency

**`Once` per event.** Admission is a singular, discrete event. Repeating the notification would create confusion — a record has either been submitted or it hasn't. `Once` ensures the notification goes out exactly when the action happens.

---

## Recipients

Security roles resolved **globally** (no institution filter by default). This is because admission decisions are often made at a central, ministry, or district level rather than purely within a single school. The roles assigned to the alert rule define the correct audience for your deployment.

---

## Threshold Configuration

There is **no threshold** for this alert — it fires on every admission save event. The threshold field is not used.

If you want to limit alerts to specific admission statuses (e.g., only alert when the application is formally submitted, not on every edit), this must be handled in the rule configuration or in the triggering code. Contact your system administrator if selective triggering is required.

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

## Example Alert Rule

### Admission received notification

| Field | Value |
|-------|-------|
| **Name** | New Student Admission Received |
| **Feature** | StudentAdmission |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | _(leave blank — not applicable)_ |
| **Security Roles** | Admissions Officer, Institution Principal |

**Subject:**
```
New Admission Application: ${student.name}
```

**Message body:**
```
Dear Colleague,

A new student admission application has been submitted in OpenEMIS and requires your attention.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Date of Birth: ${student.date_of_birth}
Institution: ${institution.name}

Please log in to OpenEMIS and review the application:
  Administration → Students → Admissions

Ensure all required documents have been submitted and proceed with the
verification process according to your institution's admissions policy.

This is an automated notification from OpenEMIS.
```

### Second rule — SMS confirmation to admissions coordinator

| Field | Value |
|-------|-------|
| **Name** | Admission — SMS to Coordinator |
| **Feature** | StudentAdmission |
| **Enabled** | Yes |
| **Method** | SMS |
| **Security Roles** | Admissions Coordinator |

**Subject:**
```
OpenEMIS: New admission for ${student.name} at ${institution.name}
```

**Message body:**
```
OpenEMIS ALERT: New admission application from ${student.name} (${student.openemis_no})
at ${institution.name}. Please review in the system.
```

---

## Multiple Rules for One Alert

You can configure multiple rules for the same `StudentAdmission` feature — for example:

- **Rule 1** — Email to admissions officer with full application details
- **Rule 2** — SMS to the admissions coordinator as a quick notification
- **Rule 3** — Email to the school principal for high-level awareness

Each rule can target different roles, use a different method (Email vs SMS), and carry a completely different message tailored to the audience's needs.

---

## Technical Notes

- Artisan command: `alerts:student-admission`
- Dispatched from: `StudentAdmissionTable::afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-admission \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
