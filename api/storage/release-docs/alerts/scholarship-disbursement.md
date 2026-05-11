# Scholarship Disbursement — `ScholarshipDisbursement`

> **Feature key:** `ScholarshipDisbursement` · **Process:** `AlertScholarshipDisbursement`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`
> **Recipient scope:** Global roles — no institution filter

---

## What It Is

An alert sent to the scholarship management and finance team when a scholarship recipient's scheduled payment disbursement date is approaching or has recently passed without being processed. It covers both forward-looking reminders (prepare the payment) and backward-looking safety checks (flag overdue disbursements).

---

## Purpose

Scholarship disbursements must be processed on time to ensure recipients receive their funds when expected. Missed or delayed disbursements damage trust, create hardship for recipients, and expose the institution to complaints and regulatory scrutiny.

Payment schedules are recorded in OpenEMIS as estimates, but executing the actual payment requires human action. Without automated monitoring, a disbursement date can pass unnoticed if the responsible team member is absent or occupied. This alert provides both a proactive reminder (before the due date) and a safety net (after the due date) so no disbursement falls through the cracks.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:scholarship-disbursement` at the configured frequency.

The command queries `scholarship_recipient_payment_structure_estimates` and applies the configured date window condition:
- **Condition 1** — disbursement date is in the **next** `value` days (upcoming)
- **Condition 2** — disbursement date was **`value` days ago** (overdue check)

Two separate rules are recommended: one for upcoming, one for overdue.

---

## Frequency

**`Daily`** is the standard frequency. Payment calendars are fixed and require human action to execute. Daily reminders in both the approaching and overdue windows provide complete coverage without requiring anyone to manually track schedules.

---

## Recipients — Global Roles, No Institution Filter

Scholarship management is a **centralised, ministry-level function**. Disbursements are not tied to a specific school — they go to individual scholarship recipients regardless of institution. The roles assigned to this alert rule should be:
- Central scholarship management team
- Finance officers with disbursement authority
- Ministry-level oversight roles (for monitoring and audit purposes)

There is no institution scope — all users with the assigned roles across the entire system receive the notification.

---

## Threshold Configuration

The threshold specifies the time window and direction of the date check.

```json
{"value": 7, "condition": 1}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Number of days for the disbursement window |
| `condition` | Integer | Yes | `1` = disbursement due within `value` days (upcoming) · `2` = disbursement was due `value` days ago (overdue) |

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 14, "condition": 1}` | Remind about disbursements due in the next 14 days |
| `{"value": 7, "condition": 1}` | Remind about disbursements due in the next 7 days |
| `{"value": 3, "condition": 2}` | Flag disbursements that are now 1–3 days overdue |
| `{"value": 7, "condition": 2}` | Flag disbursements that are up to 7 days overdue |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${scholarship.name}` | Scholarship name |
| `${scholarship.code}` | Scholarship code |
| `${scholarship.application_close_date}` | Application close date (for context) |
| `${scholarship.maximum_award_amount}` | Maximum award amount |
| `${estimated_disbursement_date}` | Scheduled disbursement date |
| `${estimated_amount}` | Estimated payment amount for this disbursement |
| `${day_difference}` | Days until (positive) or since (negative) disbursement date |
| `${threshold.value}` | Configured threshold (days) |

---

## Example Alert Rules

### Rule 1 — Upcoming disbursement reminder (7 days)

| Field | Value |
|-------|-------|
| **Name** | Scholarship Disbursement — 7 Day Reminder |
| **Feature** | ScholarshipDisbursement |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 7, "condition": 1}` |
| **Security Roles** | Scholarship Finance Officer, Scholarship Administrator |

**Subject:**
```
Disbursement Due in ${day_difference} Days: ${scholarship.name}
```

**Message body:**
```
Dear Finance Officer,

A scholarship disbursement is due within ${day_difference} days and requires
your attention.

Scholarship: ${scholarship.name} (${scholarship.code})
Disbursement Date: ${estimated_disbursement_date}
Estimated Amount: ${estimated_amount}

Actions to complete before the disbursement date:
1. Verify the recipient's banking details are current in the system
2. Prepare and submit the payment instruction through the finance system
3. Confirm the payment reference and update the disbursement record in OpenEMIS
4. Notify the recipient of the expected payment date if required

Please log in to OpenEMIS → Scholarships → Disbursements to review all
upcoming payments.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Overdue disbursement alert

| Field | Value |
|-------|-------|
| **Name** | Scholarship Disbursement — Overdue Alert |
| **Feature** | ScholarshipDisbursement |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 3, "condition": 2}` |
| **Security Roles** | Scholarship Finance Officer, Scholarship Administrator, Ministry Scholarship Director |

**Subject:**
```
OVERDUE DISBURSEMENT: ${scholarship.name} — was due ${day_difference} days ago
```

**Message body:**
```
ATTENTION — OVERDUE PAYMENT

A scholarship disbursement for ${scholarship.name} (${scholarship.code})
was scheduled for ${estimated_disbursement_date} and has not been processed.

Estimated Amount: ${estimated_amount}
Days Overdue: ${day_difference}

This payment is overdue. Please take immediate action:
1. Determine why the disbursement was not processed on the due date
2. Process the payment immediately if all conditions are met
3. Contact the recipient to explain any delay
4. Document the reason for the delay in the disbursement record in OpenEMIS

If there is a systemic issue preventing disbursements from being processed,
escalate to the ministry scholarship director immediately.

This is an automated notification from OpenEMIS.
```

### Rule 3 — 14-day advance planning alert

| Field | Value |
|-------|-------|
| **Name** | Scholarship Disbursement — 14 Day Planning Notice |
| **Feature** | ScholarshipDisbursement |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 14, "condition": 1}` |
| **Security Roles** | Scholarship Finance Officer |

**Subject:**
```
Upcoming Disbursement (${day_difference} days): ${scholarship.name} — ${estimated_amount}
```

**Message body:**
```
Dear Finance Officer,

This is an advance planning notice for an upcoming scholarship disbursement.

Scholarship: ${scholarship.name}
Amount: ${estimated_amount}
Due Date: ${estimated_disbursement_date}
Days Until Due: ${day_difference}

Please begin preparing the payment documentation and verify all recipient
details are up to date in OpenEMIS. No immediate action is required —
this is an advance notice only.

A second reminder will be sent when the disbursement is 7 days away.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

The recommended setup for `ScholarshipDisbursement` is at minimum **two rules**:

- **14-day planning rule** → advance notice, finance officer only
- **7-day action rule** → standard reminder with full instructions
- **3-day overdue rule** (condition 2) → flags disbursements that have already been missed, wider audience

For high-value scholarships or strict SLA requirements, additional rules at 1-day or same-day can also be configured. Each rule is completely independent with its own threshold, message, and audience.

---

## Technical Notes

- Artisan command: `alerts:scholarship-disbursement`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Recipient resolution: global roles — **no institution_id filter**
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:scholarship-disbursement \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
