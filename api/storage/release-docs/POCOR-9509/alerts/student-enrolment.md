# Student Enrolment — `StudentEnrolment`

> **Feature key:** `StudentEnrolment` · **Process:** `AlertStudentEnrolment`
> **Trigger:** Event-based · **Default frequency:** `Once`

---

## What It Is

An alert sent when a student is formally enroled into a class or academic period. It notifies teachers and administrators at the receiving institution that a student has been placed in their school.

---

## Purpose

In systems where enrolment is processed centrally (district or ministry level), the receiving institution may not know a student has been assigned until they discover it manually. This alert bridges that gap — the class teacher and institution administrators are informed immediately, giving them time to prepare for the student's arrival, update class lists, and allocate resources.

---

## When and How It Fires

This is an **event-based** alert. It fires immediately when a `StudentEnrolment` record is saved. The CakePHP `StudentEnrolmentTable` calls `AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentEnrolment', ...)` in its `afterSave` callback, which dispatches the `alerts:student-enrolment` artisan command.

---

## Frequency

**`Once` per event.** A student is enroled once per class per academic period. Repeating this notification would create noise for the class teacher who received it on day one. The `Once` model ensures confirmation is sent precisely when the enrolment is recorded.

---

## Recipients

Security roles scoped to **the receiving institution**. Class teachers and institution administrators are the primary audience — they need to update their class lists and prepare. The institution scope prevents other schools from receiving enrolment notifications that do not affect them.

---

## Threshold Configuration

There is **no threshold** for this alert — it fires for every enrolment save event. The threshold field is not used.

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

### Class teacher notification

| Field | Value |
|-------|-------|
| **Name** | Student Enrolment — Teacher Notification |
| **Feature** | StudentEnrolment |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | _(not applicable)_ |
| **Security Roles** | Class Teacher |

**Subject:**
```
New Student Enrolment: ${student.name} has been added to your class
```

**Message body:**
```
Dear Teacher,

A new student has been enroled in your class at ${institution.name}.

Student Details:
  Name: ${student.name}
  OpenEMIS ID: ${student.openemis_no}
  Date of Birth: ${student.date_of_birth}
  Gender: ${student.gender}

Please log in to OpenEMIS to review the student's complete profile and
update your class records accordingly.

If you have any questions regarding this enrolment, please contact
your institution administrator.

This is an automated notification from OpenEMIS.
```

### Second rule — principal awareness

| Field | Value |
|-------|-------|
| **Name** | Student Enrolment — Principal Awareness |
| **Feature** | StudentEnrolment |
| **Enabled** | Yes |
| **Method** | Email |
| **Security Roles** | Institution Principal |

**Subject:**
```
Enrolment Update: ${student.name} enrolled at ${institution.name}
```

**Message body:**
```
Dear Principal,

For your records: ${student.name} (${student.openemis_no}) has been
formally enroled at ${institution.name}.

No action is required unless this enrolment is unexpected.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Multiple rules for `StudentEnrolment` allow you to:

- Send a **detailed notification** to the class teacher with all student information
- Send a **brief summary** to the institution principal for awareness only
- Send an **SMS alert** to the school administrator for immediate notification
- Apply **different rules** for different institutions or class types by assigning different security roles per rule

Each rule is completely independent — different name, audience, message content, and delivery method.

---

## Technical Notes

- Artisan command: `alerts:student-enrolment`
- Dispatched from: `StudentEnrolmentTable::afterSave()`
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:student-enrolment \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
