# Staff Employment End — `StaffEmployment`

> **Feature key:** `StaffEmployment` · **Process:** `AlertStaffEmployment`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`

---

## What It Is

An alert sent when a staff member's employment contract or institution assignment end date is approaching. It warns HR officers and administrators so they can take action — renewing, extending, or planning a replacement — before the contract expires silently.

---

## Purpose

Employment contracts expire on fixed dates. Without automated tracking, HR must manually monitor every contract end date — an error-prone and labour-intensive process. When a contract lapses unnoticed, the institution faces two equally bad outcomes:
1. The staff member continues working without a valid contract (compliance risk)
2. A teaching or administrative post becomes unexpectedly vacant

This alert converts passive date tracking into active advance notification, giving HR the time needed to act appropriately.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:staff-employment` at the configured frequency. The command queries `institution_staff` for records whose employment end date falls within the next `threshold.value` days.

Every eligible staff record generates a separate notification.

---

## Frequency

**`Daily`** is the recommended frequency. Employment contracts expire on specific dates regardless of when they were recorded in the system. Daily scanning ensures no end date is missed regardless of when the contract was originally entered.

For long-notice scenarios, a weekly `Weekly` rule at a wider threshold (e.g., 60 days) can complement a daily rule at a narrow threshold (e.g., 14 days).

---

## Recipients

Security roles scoped to **the staff member's institution**. The institution's HR officer and administrator are the decision-makers for contract renewal or replacement. The institution scope prevents school A from receiving contract end alerts about staff at school B.

---

## Threshold Configuration

The threshold is a JSON object with a single `value` field — the number of days before the employment end date within which the alert fires.

```json
{"value": 30}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Days before employment end date |

### Examples

| Threshold | Frequency | Meaning |
|-----------|-----------|---------|
| `{"value": 60}` | Weekly | Fire weekly starting 60 days before end |
| `{"value": 30}` | Daily | Fire daily in the final 30 days |
| `{"value": 14}` | Daily | Fire daily in the final 2 weeks — urgent window |
| `{"value": 7}` | Daily | Final week — critical |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${user.openemis_no}` | Staff OpenEMIS ID |
| `${user.first_name}` | First name |
| `${user.last_name}` | Last name |
| `${user.email}` | Email address |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${threshold.value}` | Configured threshold (days) |

---

## Example Alert Rules

### Rule 1 — 30-day advance notice

| Field | Value |
|-------|-------|
| **Name** | Employment End — 30 Day Notice |
| **Feature** | StaffEmployment |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 30}` |
| **Security Roles** | HR Officer, Institution Principal |

**Subject:**
```
Contract Expiry Notice: ${user.first_name} ${user.last_name} — ends in ${threshold.value} days
```

**Message body:**
```
Dear HR Officer,

This is an automated reminder that the following staff member's employment
contract or assignment at ${institution.name} is due to end within
${threshold.value} days.

Staff Member: ${user.first_name} ${user.last_name}
OpenEMIS ID: ${user.openemis_no}
Institution: ${institution.name}

Recommended actions:
- Review whether the contract should be renewed, extended, or allowed to lapse
- If renewing: initiate the renewal process and update the end date in OpenEMIS
- If not renewing: begin recruitment or interim arrangement planning
- Ensure the staff member is informed of the decision in advance

Please log in to OpenEMIS → Staff → [Staff Member] → Employment to review
the contract details.

This is an automated notification from OpenEMIS.
```

### Rule 2 — 7-day critical alert

| Field | Value |
|-------|-------|
| **Name** | Employment End — Critical 7 Days |
| **Feature** | StaffEmployment |
| **Enabled** | Yes |
| **Method** | Email + SMS |
| **Threshold** | `{"value": 7}` |
| **Security Roles** | HR Officer, Institution Principal, District HR Director |

**Subject:**
```
URGENT — Contract Expiry in 7 Days: ${user.first_name} ${user.last_name} at ${institution.name}
```

**Message body:**
```
URGENT ACTION REQUIRED

${user.first_name} ${user.last_name} (${user.openemis_no}) at ${institution.name}
has a contract ending in ${threshold.value} days.

If no renewal or replacement is in place, this position will become
vacant in one week.

Please ensure this has been resolved immediately. If this is a known
planned departure, no action is needed — this is a final automated reminder.

Log in to OpenEMIS to update the contract record.
```

---

## Multiple Rules for One Alert

A layered multi-rule approach is recommended:

- **60-day rule** (weekly) → early awareness for HR planning
- **30-day rule** (daily) → active reminder once the renewal window opens
- **7-day rule** (daily, wider audience) → escalation to district HR in the critical window

Each rule is independent — the 7-day rule fires for all staff within 7 days of end date, even if the 30-day rule has already fired for them. This is by design: repeated reminders serve different purposes at different stages.

---

## Technical Notes

- Artisan command: `alerts:staff-employment`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:staff-employment \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
