# Student Absence — `StudentAttendance`

> **Feature key:** `StudentAttendance` · **Process:** `AlertStudentAbsence`
> **Trigger:** Event-based · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student has accumulated absence days equal to or greater than a configured threshold within the current academic period.

---

## Purpose

Chronic absenteeism is one of the strongest early predictors of academic failure and school dropout. Early notification — triggered at the moment the threshold is crossed — allows teachers, administrators, and guardians to intervene before absence becomes a deep-rooted pattern rather than discovering it months later during a data review.

---

## When and How It Fires

This is an **event-based** alert. It fires immediately when an absence record is saved for a student in `InstitutionStudentAbsences`. The system counts the student's total absence days in the current academic period and compares the count to the rule threshold value.

- If the count **has just reached or exceeded** the threshold → alert fires
- If the count is still below the threshold → nothing happens
- If the student already triggered an alert today → deduplication prevents a repeat

The CakePHP `InstitutionStudentAbsencesTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentAbsence', ...)` in its `afterSave` callback, which dispatches the `alerts:student-absence` artisan command with the specific student and academic period IDs.

---

## Frequency

**`Once` per event.** The alert is tied to a specific save event rather than a scheduled scan. Running it daily would mean rechecking every student in the system every day and generating duplicate alerts. Tying it to the moment of data entry means it fires precisely when the information is most accurate and actionable.

---

## Recipients

Security roles scoped to **the student's institution and class**:
- Class teachers (closest relationship, first-line responders)
- Institution administrators (oversight and escalation)
- Guardian roles (if configured with email addresses)

The class-level scoping ensures only staff with a direct relationship to that specific student receive the alert — not system-wide administrators who cannot meaningfully intervene.

---

## Threshold Configuration

The threshold for this alert is a **single integer** — the number of absence days that must be reached before the alert fires.

```
threshold: 5
```

This means: fire the alert when the student's total absence count in the current academic period reaches 5 days.

### Setting the threshold

| Value | Meaning |
|-------|---------|
| `3` | Early warning — fire after just 3 days missed |
| `5` | Standard warning — moderate absence level |
| `10` | Late-stage warning — significant absenteeism |
| `15` | Critical threshold — near-chronic |

Choose a value appropriate to the academic calendar length and school policy. Many deployments use two separate rules (e.g., 5 days and 10 days) with different message urgency and different role assignments.

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
| `${institution.telephone}` | Institution telephone |
| `${institution.email}` | Institution email |
| `${total_days}` | Total absence days in the current academic period |
| `${threshold}` | The configured threshold value |

---

## Example Alert Rule

### Minimal setup (early warning)

| Field | Value |
|-------|-------|
| **Name** | Early Absence Warning — 5 Days |
| **Feature** | StudentAttendance |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `5` |
| **Security Roles** | Class Teacher, Institution Principal |

**Subject:**
```
Attendance Warning: ${student.name} has reached ${total_days} absences
```

**Message body:**
```
Dear Colleague,

This is an automated alert from OpenEMIS.

Student ${student.name} (OpenEMIS ID: ${student.openemis_no}) at ${institution.name}
has now accumulated ${total_days} absence days in the current academic period,
reaching the alert threshold of ${threshold} days.

Please review the student's attendance record and take the appropriate action:
- Contact the student's guardian to discuss the absences
- Schedule a meeting if there is an underlying issue
- Document any agreed actions in the student's case record

This notification was triggered automatically when the threshold was reached.
```

### Second rule — critical threshold

You are not limited to one rule. Create a **second rule** for the same feature to send a more urgent notification when absences reach a critical level:

| Field | Value |
|-------|-------|
| **Name** | Critical Absence Warning — 15 Days |
| **Feature** | StudentAttendance |
| **Enabled** | Yes |
| **Threshold** | `15` |
| **Security Roles** | Institution Principal, District Coordinator |

**Subject:**
```
CRITICAL: ${student.name} has ${total_days} unexcused absences — immediate action required
```

**Message body:**
```
ATTENTION — ACTION REQUIRED

${student.name} (${student.openemis_no}) at ${institution.name} has now missed
${total_days} days. This exceeds the critical threshold of ${threshold} days.

This student is at serious risk of academic failure and may require a formal
intervention, referral, or transfer review.

Please escalate this case immediately.
```

---

## Multiple Rules for One Alert

You can create **as many rules as needed** for the same alert type. Each rule is independent:

- **Different name** — e.g., "Early Warning (5 days)" and "Critical Warning (15 days)"
- **Different threshold** — triggers at different absence counts
- **Different subject and message** — escalating urgency, different tone
- **Different security roles** — class teacher at 5 days; principal + district coordinator at 15 days
- **Different method** — email for early warning; SMS + email for critical

All rules sharing the same feature key (`StudentAttendance`) are evaluated each time the alert command runs. A student who reaches 15 days will trigger **both** the 5-day rule and the 15-day rule on that day (if neither has already been triggered for them).

---

## Technical Notes

- Artisan command: `alerts:student-absence`
- Dispatched from: `InstitutionStudentAbsencesTable::afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`, `--student_id`, `--academic_period_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-absence \
     --user_id=1 --rule_id=<id> --process_id=0 \
     --student_id=<id> --academic_period_id=<id>"
  ```
