h1. Student Admission — {{StudentAdmission}}

{quote}
*Feature key:* {{StudentAdmission}} · *Process:* {{AlertStudentAdmission}}
*Trigger:* Event-based (workflow step match) · *Default frequency:* {{Once}}
{quote}

----

h2. What It Is

An alert sent when a student admission application reaches one of the configured workflow steps. It notifies the student and/or their guardians so they are informed of the admission status as it progresses through the approval workflow.

----

h2. Purpose

In most education systems, student admission is a time-sensitive process. Applications need to be reviewed, documents verified, and decisions communicated. Without a notification mechanism, applicants may not know their application has moved. This alert ensures the student and their guardians are informed the moment the application advances to a significant step (e.g., Approved, Rejected).

----

h2. When and How It Fires

This is an *event-based* alert. The CakePHP {{StudentAdmissionTable}} calls {{AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentAdmission', ...)}} in its {{afterSave}} callback, which dispatches the {{alerts:student-admission}} artisan command.

The command then checks the admission's current {{status_id}} against the {{workflow_steps}} list in the threshold JSON. *If the current status is not in the configured workflow steps, the alert is suppressed.* The threshold is required — if no workflow steps are configured, no alerts will fire.

----

h2. Frequency

*{{Once}} per event.* Each workflow step transition is a discrete event. The {{Once}} model ensures the notification goes out exactly when the application moves to a qualifying step.

----

h2. Recipients

Recipients are resolved via *student-associated contact lookup* ({{getStudentAssociatedContactList}}). This method *only* resolves:

* *The student themselves* — when security role ID {{8}} (Student) is assigned to the rule
* *The student's guardians* — when security role ID {{9}} (Guardian) is assigned to the rule

Other security roles added to the rule are ignored. If neither role 8 nor 9 is in the rule's security roles, no recipients will be found and no alert will be sent.

----

h2. Threshold Configuration

The threshold defines which *workflow steps* should trigger the alert. The format is a JSON object with an array of step IDs:

{code:json}
{"workflow_steps": [82]}
{code}

|| Field || Description ||
| {{workflow_steps}} | Array of {{workflow_steps.id}} values from the {{Student Admission}} workflow |

h3. Student Admission workflow step IDs

These are the IDs configured in the {{workflow_steps}} table for the {{Student Admission}} workflow:

|| ID || Step name ||
| 80 | Open |
| 81 | Pending Approval |
| 82 | Approved |
| 83 | Rejected |
| 84 | Pending Cancellation |
| 85 | Cancelled |

{quote}
*Important:* These IDs were seeded during installation. On older deployments the IDs may differ — always query {{SELECT id, name FROM workflow_steps WHERE workflow_id = (SELECT id FROM workflows WHERE name = 'Student Admission')}} to confirm the correct IDs for your system. Alert rules configured with wrong step IDs will silently not fire.
{quote}

h3. Examples

|| Threshold || Meaning ||
| {{"workflow_steps": [82]}} | Alert when admission is Approved |
| {{"workflow_steps": [83]}} | Alert when admission is Rejected |
| {{"workflow_steps": [82, 83]}} | Alert on either Approved or Rejected |
| {{"workflow_steps": [80, 81, 82, 83, 84, 85]}} | Alert on any step |

----

h2. Available Placeholders

|| Placeholder || Value ||
| {{${admission_status}}} | Current admission workflow step name |
| {{${academic_period.name}}} | Academic period name |
| {{${start_date}}} | Student study start date |
| {{${end_date}}} | Student study end date |
| {{${student.name}}} | Student's full name |
| {{${student.openemis_no}}} | OpenEMIS ID |
| {{${student.first_name}}} | First name |
| {{${student.middle_name}}} | Middle name |
| {{${student.third_name}}} | Third name |
| {{${student.last_name}}} | Last name |
| {{${student.preferred_name}}} | Preferred name |
| {{${student.email}}} | Email address |
| {{${student.address}}} | Address |
| {{${student.postal_code}}} | Postal code |
| {{${student.date_of_birth}}} | Date of birth |
| {{${institution.name}}} | Institution name |
| {{${institution.code}}} | Institution code |
| {{${institution.address}}} | Institution address |
| {{${institution.postal_code}}} | Institution postal code |
| {{${institution.contact_person}}} | Institution contact person |
| {{${institution.telephone}}} | Telephone |
| {{${institution.email}}} | Institution email |
| {{${institution.website}}} | Institution website |
| {{${grade.name}}} | Education grade name |
| {{${guardian.name}}} | Guardian full name |
| {{${guardian.relation}}} | Guardian relation type |
| {{${guardian.contact}}} | Guardian contact (from {{user_contacts}}) |

----

h2. Example Alert Rule

h3. Admission approved notification to student/guardian

|| Field || Value ||
| *Name* | Student Admission — Approved |
| *Feature* | StudentAdmission |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"workflow_steps": [82]}} |
| *Security Roles* | Student (role 8), Guardian (role 9) |

*Subject:*
{code}
Your Admission Application Has Been Approved — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your admission application to ${institution.name} has been approved.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Academic Period: ${academic_period.name}
Grade: ${grade.name}
Start Date: ${start_date}

Please log in to OpenEMIS or contact the institution for further instructions.

This is an automated notification from OpenEMIS.
{code}

h3. Rejected notification to student/guardian

|| Field || Value ||
| *Name* | Student Admission — Rejected |
| *Feature* | StudentAdmission |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"workflow_steps": [83]}} |
| *Security Roles* | Student (role 8), Guardian (role 9) |

*Subject:*
{code}
Your Admission Application — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your admission application to ${institution.name} has not been approved at this time.

Please contact ${institution.name} directly for further information.

This is an automated notification from OpenEMIS.
{code}

----

h2. Multiple Rules for One Alert

You can configure multiple rules for the same {{StudentAdmission}} feature — for example:

* *Rule 1* — Email to student when Approved (threshold: {{"workflow_steps": [82]}})
* *Rule 2* — Email to guardian when Approved (same threshold, role 9)
* *Rule 3* — Email to student when Rejected (threshold: {{"workflow_steps": [83]}})

Each rule can target different roles, use a different method (Email vs SMS), and carry a completely different message tailored to the audience's needs.

----

h2. Technical Notes

* Artisan command: {{alerts:student-admission}}
* Dispatched from: {{StudentAdmissionTable::afterSave()}}
* Required parameters: {{--user_id}}, {{--rule_id}}, {{--process_id}}, {{--entity_id}}
* {{--entity_id}} is the {{institution_student_admission.id}} of the record that was saved
* Manual test:
{code:bash}
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan alerts:student-admission \
   --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<admission_id>"
{code}

----
----

h1. Student Enrolment — {{StudentEnrolment}}

{quote}
*Feature key:* {{StudentEnrolment}} · *Process:* {{AlertStudentEnrolment}}
*Trigger:* Event-based (workflow step match) · *Default frequency:* {{Once}}
{quote}

----

h2. What It Is

An alert sent when a student enrolment application reaches one of the configured workflow steps. It notifies the student and/or their guardians that their enrolment has been processed, approved, or rejected.

----

h2. Purpose

In systems where enrolment is processed centrally (district or ministry level), the student and their family may not know the enrolment has been actioned until they check manually. This alert bridges that gap — the student and their guardians are informed immediately when the enrolment reaches a significant step (e.g., Approved, Rejected).

----

h2. When and How It Fires

This is an *event-based* alert. The CakePHP {{StudentEnrolmentTable}} calls {{AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentEnrolment', ...)}} in its {{afterSave}} callback, which dispatches the {{alerts:student-enrolment}} artisan command.

The command then checks the enrolment's current {{status_id}} against the {{workflow_steps}} list in the threshold JSON. *If the current status is not in the configured workflow steps, the alert is suppressed.* The threshold is required — if no workflow steps are configured, no alerts will fire.

----

h2. Frequency

*{{Once}} per event.* Each workflow step transition is a discrete event. The {{Once}} model ensures the notification goes out exactly when the enrolment moves to a qualifying step.

----

h2. Recipients

Recipients are resolved via *student-associated contact lookup* ({{getStudentAssociatedContactList}}). This method *only* resolves:

* *The student themselves* — when security role ID {{8}} (Student) is assigned to the rule
* *The student's guardians* — when security role ID {{9}} (Guardian) is assigned to the rule

Other security roles added to the rule are ignored. If neither role 8 nor 9 is in the rule's security roles, no recipients will be found and no alert will be sent.

----

h2. Threshold Configuration

The threshold defines which *workflow steps* should trigger the alert. The format is a JSON object with an array of step IDs:

{code:json}
{"workflow_steps": [136]}
{code}

|| Field || Description ||
| {{workflow_steps}} | Array of {{workflow_steps.id}} values from the {{Student Enrolment}} workflow |

h3. Student Enrolment workflow step IDs

These are the IDs configured in the {{workflow_steps}} table for the {{Student Enrolment}} workflow:

|| ID || Step name ||
| 134 | Open |
| 135 | Pending Approval |
| 136 | Approved |
| 137 | Rejected |
| 138 | Pending Cancellation |
| 139 | Cancelled |

{quote}
*Important:* These IDs were seeded during installation. On older deployments the IDs may differ — always query {{SELECT id, name FROM workflow_steps WHERE workflow_id = (SELECT id FROM workflows WHERE name = 'Student Enrolment')}} to confirm the correct IDs for your system. Alert rules configured with wrong step IDs will silently not fire.
{quote}

h3. Examples

|| Threshold || Meaning ||
| {{"workflow_steps": [136]}} | Alert when enrolment is Approved |
| {{"workflow_steps": [137]}} | Alert when enrolment is Rejected |
| {{"workflow_steps": [136, 137]}} | Alert on either Approved or Rejected |
| {{"workflow_steps": [134, 135, 136, 137, 138, 139]}} | Alert on any step |

----

h2. Available Placeholders

|| Placeholder || Value ||
| {{${enrolment_status}}} | Current enrolment workflow step name |
| {{${academic_period.name}}} | Academic period name |
| {{${start_date}}} | Student study start date |
| {{${end_date}}} | Student study end date |
| {{${student.name}}} | Student's full name |
| {{${student.openemis_no}}} | OpenEMIS ID |
| {{${student.first_name}}} | First name |
| {{${student.middle_name}}} | Middle name |
| {{${student.third_name}}} | Third name |
| {{${student.last_name}}} | Last name |
| {{${student.preferred_name}}} | Preferred name |
| {{${student.email}}} | Email address |
| {{${student.address}}} | Address |
| {{${student.postal_code}}} | Postal code |
| {{${student.date_of_birth}}} | Date of birth |
| {{${institution.name}}} | Institution name |
| {{${institution.code}}} | Institution code |
| {{${institution.address}}} | Institution address |
| {{${institution.postal_code}}} | Institution postal code |
| {{${institution.contact_person}}} | Institution contact person |
| {{${institution.telephone}}} | Telephone |
| {{${institution.email}}} | Institution email |
| {{${institution.website}}} | Institution website |
| {{${grade.name}}} | Education grade name |
| {{${guardian.name}}} | Guardian full name |
| {{${guardian.relation}}} | Guardian relation type |
| {{${guardian.contact}}} | Guardian contact (from {{user_contacts}}) |

----

h2. Example Alert Rule

h3. Enrolment approved — notification to student and guardian

|| Field || Value ||
| *Name* | Student Enrolment — Approved |
| *Feature* | StudentEnrolment |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"workflow_steps": [136]}} |
| *Security Roles* | Student (role 8), Guardian (role 9) |

*Subject:*
{code}
Your Enrolment Has Been Approved — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your enrolment at ${institution.name} has been approved.

Student: ${student.name}
OpenEMIS ID: ${student.openemis_no}
Academic Period: ${academic_period.name}
Grade: ${grade.name}
Start Date: ${start_date}

Please log in to OpenEMIS or contact the institution for further instructions.

This is an automated notification from OpenEMIS.
{code}

h3. Enrolment rejected — notification to student and guardian

|| Field || Value ||
| *Name* | Student Enrolment — Rejected |
| *Feature* | StudentEnrolment |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"workflow_steps": [137]}} |
| *Security Roles* | Student (role 8), Guardian (role 9) |

*Subject:*
{code}
Enrolment Application Update — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your enrolment application to ${institution.name} has not been approved at this time.

Please contact ${institution.name} directly for further information.

This is an automated notification from OpenEMIS.
{code}

----

h2. Multiple Rules for One Alert

Multiple rules for {{StudentEnrolment}} allow you to:

* Send a *detailed notification* to the student when Approved ({{"workflow_steps": [136]}}, role 8)
* Send a *brief summary* to the guardian for awareness ({{"workflow_steps": [136]}}, role 9)
* Send a *rejection notice* to student and guardian ({{"workflow_steps": [137]}}, roles 8 and 9)

Each rule is completely independent — different name, audience, message content, and delivery method.

----

h2. Technical Notes

* Artisan command: {{alerts:student-enrolment}}
* Dispatched from: {{StudentEnrolmentTable::afterSave()}}
* Required parameters: {{--user_id}}, {{--rule_id}}, {{--process_id}}, {{--entity_id}}
* {{--entity_id}} is the {{institution_student_enrolment.id}} of the record that was saved
* Manual test:
{code:bash}
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan alerts:student-enrolment \
   --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<enrolment_id>"
{code}

----
----

h1. Student Status Change — {{StudentStatus}}

{quote}
*Feature key:* {{StudentStatus}} · *Process:* {{AlertStudentStatus}}
*Trigger:* Event-based (student status match) · *Default frequency:* {{Once}}
{quote}

----

h2. What It Is

An alert sent when a student's enrolment record ({{institution_students}}) is saved with a {{student_status_id}} that matches one of the statuses configured in the threshold. It notifies the student and/or their guardians that a status change — such as Transfer, Withdrawal, or Graduation — has been recorded.

----

h2. Purpose

Student status changes carry downstream consequences:
* A *transferred* student must be removed from class lists and added at the new institution
* A *withdrawal* may require follow-up to understand the reason and prevent dropout
* A *promotion* or *graduation* triggers class reassignment and administrative processing

Without this alert, status changes sit in the database unnoticed until someone runs a report. The alert surfaces the change at the moment it is recorded, giving the student and guardians timely information.

----

h2. When and How It Fires

This is an *event-based* alert. The CakePHP {{StudentsTable}} calls {{AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentStatus', ...)}} in its {{afterSave}} callback when it processes an {{institution_students}} record, which dispatches the {{alerts:student-status-change}} artisan command.

The command then checks the {{institution_students.student_status_id}} of the saved record against the {{statuses}} list in the threshold JSON. *If the current status ID is not in the configured list, the alert is suppressed.* The threshold is required — if no statuses are configured, no alerts will fire.

----

h2. Frequency

*{{Once}} per event.* Each status change is a discrete, non-repeating event. The alert fires when the record is saved with a matching status.

----

h2. Recipients

Recipients are resolved via *student-associated contact lookup* ({{getStudentAssociatedContactList}}). This method *only* resolves:

* *The student themselves* — when security role ID {{8}} (Student) is assigned to the rule
* *The student's guardians* — when security role ID {{9}} (Guardian) is assigned to the rule

Other security roles added to the rule are ignored. If neither role 8 nor 9 is in the rule's security roles, no recipients will be found and no alert will be sent.

----

h2. Threshold Configuration

The threshold defines which *student statuses* should trigger the alert. The format is a JSON object with an array of {{student_statuses.id}} values:

{code:json}
{"statuses": [4]}
{code}

|| Field || Description ||
| {{statuses}} | Array of {{student_statuses.id}} values that should trigger the alert |

h3. Finding status IDs

{code:sql}
SELECT id, name FROM student_statuses ORDER BY id;
{code}

Status IDs from this deployment:

|| ID || Status ||
| 1 | Enrolled |
| 3 | Transferred |
| 4 | Withdrawn |
| 6 | Graduated |
| 7 | Promoted |
| 8 | Repeated |

{quote}
*Important:* These IDs were seeded during installation and can vary significantly between deployments. Always query {{SELECT id, name FROM student_statuses}} to confirm the correct IDs for your system before configuring alert thresholds. Alert rules configured with wrong status IDs will silently not fire.
{quote}

h3. Examples

|| Threshold || Meaning ||
| {{"statuses": [4]}} | Alert when a student is marked Withdrawn |
| {{"statuses": [3]}} | Alert when a student is Transferred |
| {{"statuses": [6]}} | Alert when a student Graduates |
| {{"statuses": [3, 4]}} | Alert on either Transfer or Withdrawal |
| {{"statuses": [1, 3, 4, 6, 7, 8]}} | Alert on any status change |

----

h2. Available Placeholders

|| Placeholder || Value ||
| {{${student_status}}} | Student status name (e.g., "Withdrawn") |
| {{${academic_period.name}}} | Academic period name |
| {{${start_date}}} | Student study start date |
| {{${end_date}}} | Student study end date |
| {{${student.name}}} | Student's full name |
| {{${student.openemis_no}}} | OpenEMIS ID |
| {{${student.first_name}}} | First name |
| {{${student.middle_name}}} | Middle name |
| {{${student.third_name}}} | Third name |
| {{${student.last_name}}} | Last name |
| {{${student.preferred_name}}} | Preferred name |
| {{${student.email}}} | Email address |
| {{${student.address}}} | Address |
| {{${student.postal_code}}} | Postal code |
| {{${student.date_of_birth}}} | Date of birth |
| {{${institution.name}}} | Institution name |
| {{${institution.code}}} | Institution code |
| {{${institution.address}}} | Institution address |
| {{${institution.postal_code}}} | Institution postal code |
| {{${institution.contact_person}}} | Institution contact person |
| {{${institution.telephone}}} | Telephone |
| {{${institution.email}}} | Institution email |
| {{${institution.website}}} | Institution website |
| {{${grade.name}}} | Education grade name |
| {{${guardian.name}}} | Guardian full names (comma-separated if multiple) |
| {{${guardian.relation}}} | Guardian relation types (comma-separated) |
| {{${guardian.contact}}} | Guardian contacts — email and/or mobile (comma-separated) |

----

h2. Example Alert Rules

h3. Rule 1 — Withdrawal alert

|| Field || Value ||
| *Name* | Student Withdrawal — Notify Guardian |
| *Feature* | StudentStatus |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"statuses": [4]}} |
| *Security Roles* | Guardian (role 9) |

*Subject:*
{code}
Important: ${student.name} has been marked as Withdrawn
{code}

*Message body:*
{code}
Dear ${guardian.name},

This is to notify you that ${student.name} (${student.openemis_no}) has been
marked as Withdrawn at ${institution.name}.

If you have questions about this change, please contact the institution directly.

This is an automated notification from OpenEMIS.
{code}

h3. Rule 2 — Transfer alert

|| Field || Value ||
| *Name* | Student Transfer — Notify Student and Guardian |
| *Feature* | StudentStatus |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"statuses": [3]}} |
| *Security Roles* | Student (role 8), Guardian (role 9) |

*Subject:*
{code}
Transfer Confirmation: ${student.name} — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your transfer from ${institution.name} has been recorded in OpenEMIS.

Academic Period: ${academic_period.name}
Grade: ${grade.name}

Please ensure your new institution has your academic records.

This is an automated notification from OpenEMIS.
{code}

h3. Rule 3 — Graduation alert

|| Field || Value ||
| *Name* | Student Graduation — Notify Student |
| *Feature* | StudentStatus |
| *Enabled* | Yes |
| *Method* | Email |
| *Threshold* | {{"statuses": [6]}} |
| *Security Roles* | Student (role 8) |

*Subject:*
{code}
Congratulations: Graduation Recorded — ${institution.name}
{code}

*Message body:*
{code}
Dear ${student.name},

Your graduation from ${institution.name} has been recorded in OpenEMIS.

OpenEMIS ID: ${student.openemis_no}
Institution: ${institution.name}

This is an automated notification from OpenEMIS.
{code}

----

h2. Multiple Rules for One Alert

{{StudentStatus}} is one of the most versatile alert types because different transitions require different responses. Using separate rules:

* *Withdrawal rule* → {{"statuses": [4]}} — sends to guardian with follow-up instructions
* *Transfer rule* → {{"statuses": [3]}} — sends to student and guardian confirming the transfer
* *Graduation rule* → {{"statuses": [6]}} — sends to student as a congratulatory notice
* *Promotion rule* → {{"statuses": [7]}} — sends to student confirming class advancement

Each rule has its own threshold, its own message, and its own recipient roles. All rules for {{StudentStatus}} are evaluated when the alert fires — each rule matches independently based on its own configured statuses.

----

h2. Technical Notes

* Artisan command: {{alerts:student-status-change}}
* Dispatched from: {{StudentsTable::afterSave()}} (processes {{institution_students}} records)
* Required parameters: {{--user_id}}, {{--rule_id}}, {{--process_id}}, {{--entity_id}}
* {{--entity_id}} is the {{institution_students.id}} of the record that triggered the status change
* Manual test:
{code:bash}
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan alerts:student-status-change \
   --user_id=1 --rule_id=<id> --process_id=0 --entity_id=<institution_students_id>"
{code}
