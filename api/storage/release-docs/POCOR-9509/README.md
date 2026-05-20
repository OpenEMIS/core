# OpenEMIS Core — Alerts Module

> **Version:** POCOR-9509 · **Updated:** 2026-03-13

The Alerts module delivers critical, timely notifications via **Email** and **SMS** to the right people at the right moment — from a student missing too many classes to a staff contract expiring silently.

This guide covers concepts, UI navigation, queue management, all alert types, and troubleshooting. Each alert type has its own dedicated page with full threshold documentation, placeholder reference, and worked example rules with real subject and message templates.

---

## Table of Contents

1. [How Alerts Work](#how-alerts-work)
2. [Navigation](#navigation)
3. [Alerts — Managing the Scheduler](#alerts--managing-the-scheduler)
4. [Alert Rules — Configuration](#alert-rules--configuration)
5. [Multiple Rules per Alert — Key Concept](#multiple-rules-per-alert--key-concept)
6. [Alert Logs — Audit Trail](#alert-logs--audit-trail)
7. [Alert Queue — Delivery Pipeline](#alert-queue--delivery-pipeline)
8. [Workflow-Triggered Alerts](#workflow-triggered-alerts)
9. [Alert Types](#alert-types)
10. [Thresholds Reference](#thresholds-reference)
11. [Placeholders Reference](#placeholders-reference)
12. [Permissions](#permissions)
13. [Troubleshooting](#troubleshooting)

---

## How Alerts Work

OpenEMIS alerts follow a five-stage pipeline:

```
1. TRIGGER
   Event-based  → a record is saved (admission, absence, status change)
   Scheduled    → cron runs alerts:check daily/weekly/monthly
         ↓
2. RULE MATCH
   Is there an enabled alert_rule for this feature?
   Is the alert frequency anything other than "Never"?
         ↓
3. DATA QUERY
   Laravel artisan command runs, queries the database,
   applies threshold filters, resolves recipient list
         ↓
4. QUEUE
   One row inserted into alert_queue per recipient per channel (email/SMS)
         ↓
5. DELIVERY
   ProcessAlertQueue worker sends emails / SMS,
   updates queue row status, appends to Alert Logs
```

### Two trigger models

| Model | Frequency value | When it fires | Alert types |
|-------|----------------|---------------|-------------|
| **Event-based** | `Once` | Immediately when a specific record is saved | Student Absence, Student Admission, Student Enrolment, Student Status Change |
| **Scheduled** | `Daily` / `Weekly` / `Monthly` | Cron job runs once per period, scans all matching records | All other alerts |

Setting frequency to **`Never`** disables the alert entirely, regardless of any configured rules.

---

## Navigation

All alert management screens live under:

**Administration → Communications**

| Screen | Path | Purpose |
|--------|------|---------|
| **Alerts** | Administration → Communications → Alerts | Enable/disable each alert type, set frequency |
| **Alert Rules** | Administration → Communications → Alert Rules | Create and manage notification rules, thresholds, recipients |
| **Alert Logs** | Administration → Communications → Alert Logs | Audit trail of every notification sent |
| **Alert Queue** | Administration → Communications → Alert Queue | Real-time view of pending, sent, and failed delivery items |

---

## Alerts — Managing the Scheduler

The **Alerts** screen shows every alert type registered in the system with its current frequency and running status.

### View an alert

1. Go to **Administration → Communications → Alerts**
2. Click **Action Bar → View** on any row

### Start an alert

1. Open the alert record (frequency must not be `Never`)
2. Click **Start** in the Action Bar
3. The system creates a `system_processes` entry and begins scheduling

### Stop an alert

1. Open a running alert record
2. Click **Stop** in the Action Bar

> **Note:** Event-based alerts (`Once`) do not have Start/Stop controls — they fire automatically from data-save events.

### Frequency options

| Option | Behaviour |
|--------|-----------|
| `Never` | Disabled — no alerts sent under any circumstance |
| `Once` | Fires once per triggering event, no repeat |
| `Daily` | At most once per calendar day |
| `Weekly` | At most once per 7-day period |
| `Monthly` | At most once per month |

---

## Alert Rules — Configuration

An **Alert Rule** defines exactly _what_ to check, _how_ to notify, and _who_ to notify. An alert type can have **multiple rules** — see [Multiple Rules per Alert](#multiple-rules-per-alert--key-concept) below.

### Create a rule

1. Go to **Administration → Communications → Alert Rules → Add**
2. Select the **Feature** (alert type)
3. Fill in **Rule Setup**:

   | Field | Required | Notes |
   |-------|----------|-------|
   | Name | Yes | Unique name — meaningful when you have multiple rules per feature |
   | Enabled | Yes | Set to Yes to activate |
   | Method | Yes | Email, SMS, or both |
   | Threshold | Yes | Format varies per alert type — see [Thresholds Reference](#thresholds-reference) or the individual alert page |
   | Security Roles | Yes (most alerts) | Roles that receive this alert (not required for ScholarshipApplication) |

4. Fill in **Alert Content**:

   | Field | Notes |
   |-------|-------|
   | Subject | Supports `${placeholder}` tokens |
   | Message | Supports `${placeholder}` tokens |

5. Click **Save**

### Edit / Delete a rule

- **Edit**: Action Bar → Edit on the target rule
- **Delete**: Action Bar → Delete → Confirm

### Important rule conditions

- **Disabled rules** (`Enabled = No`) never execute even if the alert frequency is active
- **Null placeholder values** — token left as-is in the sent message
- **Empty placeholder values** — token replaced with a blank string
- **Tokens are case-sensitive**: `${student.name}` ≠ `${Student.Name}`

---

## Multiple Rules per Alert — Key Concept

This is one of the most powerful features of the OpenEMIS alerts system and is often underused.

**You can create as many rules as you need for the same alert type.** Each rule is completely independent:

| Rule property | Can differ between rules |
|---------------|--------------------------|
| **Name** | Yes — give each rule a descriptive name |
| **Threshold** | Yes — different day windows, different conditions |
| **Subject** | Yes — escalating urgency, different language |
| **Message body** | Yes — different detail level, different instructions |
| **Security Roles** | Yes — narrow audience for early warnings, wider audience for critical reminders |
| **Method** | Yes — Email for early warnings, SMS + Email for critical ones |
| **Enabled** | Yes — disable one rule without affecting others |

### Practical example — License Validity

Instead of one rule that alerts 30 days before expiry:

| Rule Name | Threshold | Roles | Method |
|-----------|-----------|-------|--------|
| License Warning — 60 days | `{"value": 60, ...}` | HR Officer | Email |
| License Warning — 30 days | `{"value": 30, ...}` | HR Officer, Principal | Email |
| License Warning — 7 days | `{"value": 7, ...}` | HR Officer, Principal, District HR | Email + SMS |
| License Expired — Follow Up | `{"value": 7, ..., "condition": 2}` | HR Officer, Principal, District HR, Ministry | Email |

Each rule fires independently based on its own threshold. A staff member with a license expiring in 5 days satisfies rules 2, 3, and 4 simultaneously.

### Practical example — Case Escalation (tiered escalation)

| Rule Name | Threshold | Roles |
|-----------|-----------|-------|
| Case Inactive — 3 days | `{"value": 3, ...}` | Institution Principal |
| Case Inactive — 7 days | `{"value": 7, ...}` | Principal + Coordinator |
| Case Critical — 21 days | `{"value": 21, ...}` | Principal + District + Ministry |

A case open for 25 days triggers all three rules every day until someone updates it.

> **Best practice:** Name your rules clearly (e.g., "Retirement Warning — 90 Days" not just "Retirement") so the Alert Rules list remains readable as rule counts grow.

---

## Alert Logs — Audit Trail

Alert Logs record every notification sent through the system.

### View logs

1. Go to **Administration → Communications → Alert Logs**
2. Click **Action Bar → View** for full record details

### Delete a single log record

Click **Action Bar → Delete** on the target record and confirm.

### Mass delete log records

POCOR-9509 added bulk selection to Alert Logs:

1. Go to **Administration → Communications → Alert Logs**
2. Use the **checkbox column** to select rows (or header checkbox to select all visible)
3. Click **Delete Selected** in the toolbar
4. Confirm — all selected records removed in one operation

> **Use case:** After a test run or misconfigured rule fires thousands of alerts, mass delete clears the noise in seconds.

---

## Alert Queue — Delivery Pipeline

New in POCOR-9509: the **Alert Queue** screen gives real-time visibility into the `alert_queue` delivery pipeline.

### Queue columns

| Column | Description |
|--------|-------------|
| Alert Type | Feature name (e.g., `StudentAttendance`) |
| Channel | `email` or `sms` |
| Recipient | Email address or phone number |
| Subject | Resolved subject (placeholders already replaced) |
| Status | `0` = Pending · `1` = Sent · `-1` = Failed |
| Retry Count | Number of delivery attempts |
| Available At | Earliest time item can be processed |
| Created | When the alert command queued this item |

### View the queue

1. Go to **Administration → Communications → Alert Queue**
2. Filter by status to see only pending or failed items
3. Click **Action Bar → View** for full message body

### Mass delete queue records

1. Select records using the checkbox column
2. Click **Delete Selected** in the toolbar and confirm

> **When to use:** Delete pending queue items if a misconfigured rule fired and you want to prevent erroneous notifications from being sent before the worker picks them up.

### Queue status lifecycle

```
[Queued: status=0]
       ↓
ProcessAlertQueue worker picks up the row
       ↓
   Success → status=1 (Sent) · logged to Alert Logs
   Failure → status=-1 (Failed) · retry_count++
              if retry_count >= max → permanently failed
```

---

## Workflow-Triggered Alerts

In addition to the alert module, OpenEMIS triggers notifications when records move through approval workflows (staff leave, scholarship applications, etc.).

### How it works

When a record transitions to a new workflow step:
1. The system checks if the new step has a **Security Role** configured
2. If the record's assignee has a **preferred email** or preferred contact set
3. An alert is sent to the assignee's contact

### Conditions that prevent workflow alerts

| Condition | Result |
|-----------|--------|
| Record moves to the **first (Open) step** | No alert sent |
| Destination step has **no Security Role** | No alert sent |
| Assignee has **no preferred email** | No alert sent |

### Setup

1. Go to **Administration → Workflows → Steps**
2. Open the step that should trigger a notification
3. Verify a Security Role is assigned
4. Ensure users in that role have a preferred email set on their profile (User Overview → Edit → Contact)

---

## Alert Types

Each alert type has a dedicated page with full documentation: threshold fields explained, all available placeholders, and worked example rules with real subject lines and message bodies.

| Alert | Feature Key | Trigger | Doc |
|-------|-------------|---------|-----|
| Student Absence | `StudentAttendance` | Event | [→ student-absence.md](alerts/student-absence.md) |
| Student Admission | `StudentAdmission` | Event | [→ student-admission.md](alerts/student-admission.md) |
| Student Enrolment | `StudentEnrolment` | Event | [→ student-enrolment.md](alerts/student-enrolment.md) |
| Student Status Change | `StudentStatus` | Event | [→ student-status-change.md](alerts/student-status-change.md) |
| Retirement Warning | `RetirementWarning` | Scheduled | [→ retirement-warning.md](alerts/retirement-warning.md) |
| Staff Employment End | `StaffEmployment` | Scheduled | [→ staff-employment-end.md](alerts/staff-employment-end.md) |
| Staff Leave End | `StaffLeave` | Scheduled | [→ staff-leave-end.md](alerts/staff-leave-end.md) |
| Staff Type | `StaffType` | Scheduled | [→ staff-type.md](alerts/staff-type.md) |
| License Validity | `LicenseValidity` | Scheduled | [→ license-validity.md](alerts/license-validity.md) |
| License Renewal | `LicenseRenewal` | Scheduled | [→ license-renewal.md](alerts/license-renewal.md) |
| Scholarship Application | `ScholarshipApplication` | Scheduled | [→ scholarship-application.md](alerts/scholarship-application.md) |
| Scholarship Disbursement | `ScholarshipDisbursement` | Scheduled | [→ scholarship-disbursement.md](alerts/scholarship-disbursement.md) |
| Case Escalation | `CaseEscalation` | Scheduled | [→ case-escalation.md](alerts/case-escalation.md) |
| System Updates | `SystemUpdates` | Scheduled | [→ system-updates.md](alerts/system-updates.md) |
| Staff Attendance | `StaffAttendance` | — | [→ staff-attendance.md](alerts/staff-attendance.md) ⚠️ Not implemented |

---

## Thresholds Reference

Each alert type uses a different threshold format. Below is a quick-reference table. For full field-by-field documentation, see the individual alert page.

| Alert | Threshold format | Key fields |
|-------|-----------------|------------|
| Student Absence | `5` | Integer — absence day count |
| Student Admission | _(none)_ | Fires on every save event |
| Student Enrolment | _(none)_ | Fires on every save event |
| Student Status Change | `{"old_status_id": 1, "new_status_id": 5}` | Specific status transition to watch |
| Retirement Warning | `{"value": 90}` | Days before retirement date |
| Staff Employment End | `{"value": 30}` | Days before employment end date |
| Staff Leave End | `{"value": 3, "staff_leave_type": 2}` | Days before leave end; optional leave type filter |
| Staff Type | `{"value": 30, "staff_type_id": 2}` | Days before relevant date; staff type to monitor |
| License Validity | `{"value": 30, "license_type": 3, "condition": 1}` | `condition`: 1=before expiry, 2=after expiry |
| License Renewal | `{"value": 60, "license_type": 3, "condition": 1, "training_categories": [1,2], "hour": 20}` | CPD hours check + expiry window |
| Scholarship Application | `{"value": 7, "condition": 1, "category": "PENDING"}` | Days before close; workflow status category |
| Scholarship Disbursement | `{"value": 7, "condition": 1}` | `condition`: 1=before due, 2=after due |
| Case Escalation | `{"value": 7, "workflow_steps": [12]}` | Days open; workflow step IDs to monitor |
| System Updates | _(none)_ | Fires on new system update records |

For full field documentation, see [ALERTS_GUIDE.md](ALERTS_GUIDE.md) → Threshold Deep-Dive section, or the individual alert page.

---

## Placeholders Reference

Placeholders use the `${token}` format and are replaced at send time. Tokens are case-sensitive.

### Behaviour rules

| Situation | Result |
|-----------|--------|
| Database value is `NULL` | Token left as-is in the sent message |
| Database value is empty string | Token replaced with blank |
| Token not available for this alert type | Token left as-is |

### Common tokens (most alerts)

| Token | Value |
|-------|-------|
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${threshold.value}` | Configured threshold value |

### Student tokens

| Token | Value |
|-------|-------|
| `${student.name}` | Full name |
| `${student.openemis_no}` | OpenEMIS ID |
| `${student.first_name}` | First name |
| `${student.last_name}` | Last name |
| `${student.email}` | Email |
| `${student.date_of_birth}` | Date of birth |
| `${student.gender}` | Gender |
| `${total_days}` | Total absence days (StudentAbsence only) |
| `${threshold}` | Configured threshold value (StudentAbsence) |

### Staff / User tokens

| Token | Value |
|-------|-------|
| `${user.openemis_no}` | OpenEMIS ID |
| `${user.first_name}` | First name |
| `${user.last_name}` | Last name |
| `${user.email}` | Email |
| `${user.date_of_birth}` | Date of birth |

### Case tokens

| Token | Value |
|-------|-------|
| `${case.case_number}` | Case reference number |
| `${case.title}` | Case title |
| `${case.description}` | Case description |
| `${case.created}` | Date opened |
| `${case.status}` | Current workflow step name |
| `${case.type}` | Case type |
| `${case.priority}` | Priority |
| `${days_open}` | Days since creation |
| `${assignee.name}` | Assignee full name |
| `${assignee.email}` | Assignee email |

### License tokens

| Token | Value |
|-------|-------|
| `${license_type.name}` | License type name |
| `${license_number}` | License number |
| `${issue_date}` | Issue date |
| `${expiry_date}` | Expiry date |
| `${day_difference}` | Days until / since expiry |
| `${total_credit_hours}` | CPD hours (LicenseRenewal) |
| `${threshold.hour}` | Required hours (LicenseRenewal) |

### Scholarship tokens

| Token | Value |
|-------|-------|
| `${scholarship.name}` | Scholarship name |
| `${scholarship.code}` | Code |
| `${scholarship.application_close_date}` | Application deadline |
| `${scholarship.maximum_award_amount}` | Maximum award |
| `${day_difference}` | Days until close / disbursement |
| `${estimated_disbursement_date}` | Disbursement date |
| `${estimated_amount}` | Payment amount |
| `${assignee.name}` | Application assignee name |
| `${assignee.email}` | Application assignee email |

---

## Permissions

| Permission level | Access |
|-----------------|--------|
| Full access | View, create, edit, delete rules; start/stop alerts; view and delete logs and queue |
| Execute only | Can use Start/Stop; cannot edit rules |
| View only | Browse alerts, rules, logs, queue; no modifications |

Configure in **Administration → Security → Roles**.

---

## Troubleshooting

### Alert fired but no email was received

1. Check **Alert Queue** — is there a row with `status = 0` (pending) or `status = -1` (failed)?
2. **Pending**: the `ProcessAlertQueue` worker may not be running
3. **Failed**: check `retry_count` — if at max, delivery permanently failed; check mail server config in `api/config/alerts.php`
4. **No queue row**: the command found no matching records, or the threshold was not met — run the command manually to check:
   ```bash
   php artisan alerts:<command> --user_id=1 --rule_id=X --process_id=0
   ```

### Alert rule enabled but never fires

- Confirm alert **frequency** is not `Never` in **Administration → Communications → Alerts**
- Confirm the rule has `Enabled = Yes`
- Confirm at least one **Security Role** is assigned (except ScholarshipApplication)
- Confirm the threshold JSON is valid and matches actual data in the database

### Workflow alert not firing

- Verify the destination workflow step has a **Security Role** assigned
- Verify the record assignee has a **preferred email** on their profile
- The first (Open) workflow step never triggers a workflow alert — by design

### Duplicate alerts in the queue

- Check for multiple enabled rules for the same feature with overlapping thresholds
- Check `system_processes` for duplicate checksum entries from today (deduplication should prevent re-runs)
- For event-based alerts: verify the triggering `afterSave` does not fire multiple times

### Mass delete not removing all selected records

- Ensure your role has full access permissions, not view-only
- Select records individually rather than "select all" if the list spans multiple pages

---

*For technical implementation details, artisan command syntax, and the full command inventory, see [ALERTS_GUIDE.md](ALERTS_GUIDE.md).*
