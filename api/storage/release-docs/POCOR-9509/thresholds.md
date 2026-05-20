# Threshold Configuration Reference

> All alert rules require a threshold that controls **what** to alert on. This reference covers every threshold format, every field, and concrete examples for every alert type.

---

## What Is a Threshold?

The threshold is a value stored on an **alert rule** record. When the alert command runs, it reads the threshold to decide which records qualify for notification. Different alert types use different threshold formats.

A single alert type can have **multiple rules**, each with a different threshold — allowing you to alert at 60 days, 30 days, and 7 days out, all for the same underlying condition, each with different message templates and recipient roles.

---

## Threshold Formats at a Glance

| Alert | Feature Key | Format | Example |
|-------|-------------|--------|---------|
| Student Absence | `StudentAttendance` | Integer | `5` |
| Student Admission | `StudentAdmission` | None | _(not used)_ |
| Student Enrolment | `StudentEnrolment` | None | _(not used)_ |
| Student Status Change | `StudentStatus` | JSON (status transition) | `{"old_status_id": 1, "new_status_id": 5}` |
| Retirement Warning | `RetirementWarning` | JSON (days window) | `{"value": 90}` |
| Staff Employment End | `StaffEmployment` | JSON (days window) | `{"value": 30}` |
| Staff Leave End | `StaffLeave` | JSON (days + leave type) | `{"value": 3, "staff_leave_type": 2}` |
| Staff Type | `StaffType` | JSON (days + staff type) | `{"value": 30, "staff_type_id": 2}` |
| License Validity | `LicenseValidity` | JSON (days + type + condition) | `{"value": 30, "license_type": 3, "condition": 1}` |
| License Renewal | `LicenseRenewal` | JSON (days + type + condition + CPD) | `{"value": 60, "license_type": 3, "condition": 1, "training_categories": [1,2], "hour": 20}` |
| Scholarship Application | `ScholarshipApplication` | JSON (days + condition + category) | `{"value": 7, "condition": 1, "category": "PENDING"}` |
| Scholarship Disbursement | `ScholarshipDisbursement` | JSON (days + condition) | `{"value": 7, "condition": 1}` |
| Case Escalation | `CaseEscalation` | JSON (days + workflow steps) | `{"value": 7, "workflow_steps": [12]}` |
| System Updates | `SystemUpdates` | None | _(not used)_ |

---

## Detailed Field Reference

### Integer threshold

Used by: **Student Absence**

A plain integer representing the number of absence days before the alert fires.

```
5
```

| Value | Effect |
|-------|--------|
| `3` | Fire after 3 absence days |
| `5` | Fire after 5 absence days |
| `10` | Fire after 10 absence days |

---

### `value` — days window

Used by: **RetirementWarning**, **StaffEmployment**, **StaffLeave**, **StaffType**, **LicenseValidity**, **LicenseRenewal**, **ScholarshipApplication**, **ScholarshipDisbursement**, **CaseEscalation**

A positive integer representing the number of days for the alert window. The meaning of "days" varies by alert:

| Alert | `value` means |
|-------|--------------|
| RetirementWarning | Days before retirement date |
| StaffEmployment | Days before employment end date |
| StaffLeave | Days before leave end date |
| StaffType | Days before contract review/end date |
| LicenseValidity | Days before (or after) license expiry |
| LicenseRenewal | Days before license expiry to start checking CPD |
| ScholarshipApplication | Days before scholarship close date |
| ScholarshipDisbursement | Days before (or after) disbursement date |
| CaseEscalation | Cases older than this many days are escalated |

---

### `condition` — direction of date check

Used by: **LicenseValidity**, **LicenseRenewal**, **ScholarshipApplication**, **ScholarshipDisbursement**

Controls whether the alert fires for records approaching a date (before) or records that have already passed it (after).

| Value | Meaning | Use case |
|-------|---------|---------|
| `1` | **Before** — the date is in the future, within `value` days | Advance warning: "expires in 30 days" |
| `2` | **After** — the date has already passed, within `value` days | Post-expiry follow-up: "expired 7 days ago" |

Example — two rules for license expiry, both before and after:

```json
// Rule A — advance warning
{"value": 30, "license_type": 3, "condition": 1}

// Rule B — post-expiry compliance chase
{"value": 7, "license_type": 3, "condition": 2}
```

---

### `old_status_id` / `new_status_id` — status transition

Used by: **StudentStatus**

Specifies the status change to watch. Both fields reference IDs from the `student_statuses` table.

```json
{"old_status_id": 1, "new_status_id": 5}
```

| Field | Description |
|-------|-------------|
| `old_status_id` | Status before the change |
| `new_status_id` | Status after the change |

To find status IDs:
```sql
SELECT id, name FROM student_statuses ORDER BY name;
```

Omitting both fields matches any status change. Specifying only one field matches any change to/from that status.

---

### `staff_leave_type` — leave type filter

Used by: **StaffLeave**

Optional. Filters the alert to a specific leave type. Omit to alert on all leave types.

```json
{"value": 3, "staff_leave_type": 2}
```

To find leave type IDs:
```sql
SELECT id, name FROM staff_leave_types ORDER BY name;
```

---

### `staff_type_id` — staff contract type

Used by: **StaffType**

Filters the alert to staff with a specific contract type. Required.

```json
{"value": 30, "staff_type_id": 2}
```

To find staff type IDs:
```sql
SELECT id, name FROM staff_types ORDER BY name;
```

---

### `license_type` — professional license type

Used by: **LicenseValidity**, **LicenseRenewal**

Filters the alert to a specific license type. Required — without this field, the alert would scan all licenses of all types simultaneously.

```json
{"value": 30, "license_type": 3, "condition": 1}
```

To find license type IDs:
```sql
SELECT id, name FROM license_types ORDER BY name;
```

A separate rule with a different `license_type` value is required for each license type you want to monitor. This is intentional — different license types may have different expiry windows, CPD requirements, and responsible roles.

---

### `training_categories` — CPD category filter

Used by: **LicenseRenewal** only

An array of training category IDs. Only training records in these categories count towards the CPD hour requirement. This allows the alert to enforce compliance with specific categories defined by the licensing authority (e.g., "Teaching Methodology" and "Subject Knowledge" count — "Health and Safety" does not).

```json
{
  "value": 60,
  "license_type": 3,
  "condition": 1,
  "training_categories": [1, 2],
  "hour": 20
}
```

To find category IDs:
```sql
SELECT id, name FROM staff_training_categories ORDER BY name;
```

---

### `hour` — minimum CPD hours

Used by: **LicenseRenewal** only

The minimum number of credit hours a staff member must have accumulated (within the license validity period, in the specified training categories) to pass the renewal check. Staff with **fewer than** this many hours are alerted. Staff who meet or exceed this threshold are silently skipped.

```json
{"value": 60, "license_type": 3, "condition": 1, "training_categories": [1,2], "hour": 20}
```

Set this to match your licensing authority's requirements. If the requirement changes, update the rule threshold — existing alert logs are not retroactively affected.

---

### `category` — workflow step category

Used by: **ScholarshipApplication**

Filters scholarship applications to those whose current workflow step has a category matching this value. Typically `"PENDING"` — applications that have not yet been approved or rejected.

```json
{"value": 7, "condition": 1, "category": "PENDING"}
```

To find workflow step categories:
```sql
SELECT DISTINCT category FROM workflow_steps WHERE category IS NOT NULL ORDER BY category;
```

Common values: `PENDING`, `OPEN`, `CLOSE`, `APPROVED`, `REJECTED`

---

### `workflow_steps` — step ID list

Used by: **CaseEscalation**

An array of `workflow_steps.id` values. Only cases whose `status_id` is in this list are checked for escalation. Typically this contains the "Open" step ID for the Cases workflow.

```json
{"value": 7, "workflow_steps": [12]}
```

To find step IDs:
```sql
SELECT ws.id, ws.name, w.name AS workflow
FROM workflow_steps ws
JOIN workflows w ON w.id = ws.workflow_id
WHERE w.name LIKE '%Case%'
ORDER BY w.name, ws.name;
```

Multiple step IDs can be included: `[12, 13]` would monitor both "Open" and "In Progress" steps.

---

## Multi-Rule Threshold Strategy

Because an alert type can have multiple rules, you can implement **layered threshold strategies** for the same underlying condition:

### Example: License validity — 3-layer strategy

| Rule Name | Threshold | Frequency | Roles | Purpose |
|-----------|-----------|-----------|-------|---------|
| License Validity — 60 Days | `{"value": 60, "license_type": 3, "condition": 1}` | Weekly | HR Officer | Early planning |
| License Validity — 30 Days | `{"value": 30, "license_type": 3, "condition": 1}` | Daily | HR Officer, Principal | Active reminder |
| License Validity — 7 Days | `{"value": 7, "license_type": 3, "condition": 1}` | Daily | HR Officer, Principal, District HR | Urgent escalation |
| License Validity — Expired | `{"value": 7, "license_type": 3, "condition": 2}` | Daily | HR Officer, Principal, District HR, Ministry | Compliance alert |

All four rules share the same `LicenseValidity` feature. They fire independently based on their own threshold values. A staff member with a license expiring in 5 days would trigger **all three pre-expiry rules** on the same day; once the license has expired, the post-expiry rule takes over.

### Example: Case escalation — tiered management alert

| Rule Name | Threshold | Roles | Purpose |
|-----------|-----------|-------|---------|
| Case Escalation — 3 Days | `{"value": 3, "workflow_steps": [12]}` | Principal | Immediate nudge |
| Case Escalation — 7 Days | `{"value": 7, "workflow_steps": [12]}` | Principal, Coordinator | Standard escalation |
| Case Escalation — 21 Days | `{"value": 21, "workflow_steps": [12]}` | Principal, District Officer, Ministry | Critical escalation |

A case that is 25 days old fires all three rules every day until action is taken.

---

## Validation Rules

- **JSON must be valid** — invalid JSON causes the rule to be silently skipped
- **Integer IDs must exist** — if a `license_type`, `staff_type_id`, or `workflow_steps` ID does not exist in the database, no records will match and no alerts will fire
- **`training_categories` must be an array** — even for a single category, use `[1]` not `1`
- **`workflow_steps` must be an array** — use `[12]` not `12`
- **`value` must be positive** — a value of `0` or negative is not meaningful for most alerts
- **`hour` must be positive** — a value of `0` would alert all staff with any license, regardless of CPD

---

*For full placeholder reference and recipient configuration, see [README.md](README.md).*
*For technical implementation details, see [ALERTS_GUIDE.md](ALERTS_GUIDE.md).*
