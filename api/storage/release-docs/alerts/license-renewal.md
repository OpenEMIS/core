# License Renewal — `LicenseRenewal`

> **Feature key:** `LicenseRenewal` · **Process:** `AlertLicenseRenewal`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`

---

## What It Is

An alert sent when a staff member's professional license is approaching expiry **and** they have not yet accumulated enough continuing professional development (CPD) training hours within the license validity period to qualify for renewal. It identifies staff who are at risk of failing the renewal requirement before the license expires.

---

## Purpose

In many education systems, renewing a teaching or professional license is not simply a matter of paying a fee — it requires documented evidence of CPD activity. A staff member may be aware their license is expiring, yet not realise they fall short of the required training hours until it is too late to arrange the necessary courses.

This alert flags the gap proactively, giving the staff member and their institution time to enrol in appropriate training before the license cannot be renewed. Staff who already have sufficient hours are **never alerted** — the command skips them automatically.

---

## When and How It Fires

This is a **scheduled** alert with a two-step logic:

1. **Find expiring licenses** — query `staff_licenses` for records of the configured type where the expiry date falls within `threshold.value` days
2. **Check training hours** — for each license found, sum `credit_hours` from `staff_trainings` records where:
   - The training was completed within the license's issue and expiry dates
   - The training category is in the configured `training_categories` list
3. **Compare to required hours** — if the total is **less than** `threshold.hour`, the alert fires. If the total meets or exceeds the requirement, the staff member is skipped silently.

Recipients are resolved through `institution_staff` (ASSIGNED status) — same as License Validity.

---

## Frequency

**`Daily`** is the standard frequency. Training hours balances change as staff complete training. Daily scanning:
- Catches staff the moment they enter the expiry window
- Automatically stops alerting once they accumulate sufficient hours (they drop out of the query)
- Ensures no staff member is missed if they enter the window mid-period

---

## Recipients

Security roles scoped to **the staff member's institution** (via `institution_staff` ASSIGNED status). The institution's professional development coordinator and HR officer need to know so they can facilitate training access and track completion. A district-level CPD coordinator may also be included.

---

## Threshold Configuration

The threshold is the most complex of all alert types — it combines a time window, a license type, a condition, a list of training categories, and a minimum hours requirement.

```json
{
  "value": 60,
  "license_type": 3,
  "condition": 1,
  "training_categories": [1, 2],
  "hour": 20
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Days before license expiry to start checking |
| `license_type` | Integer | Yes | ID from `license_types` table |
| `condition` | Integer | Yes | Always `1` (before expiry — post-expiry renewal is not meaningful) |
| `training_categories` | Array of integers | Yes | IDs from `staff_training_categories` table — only trainings in these categories count towards the requirement |
| `hour` | Integer | Yes | Minimum credit hours required for renewal |

### Finding category IDs

```sql
SELECT id, name FROM staff_training_categories ORDER BY name;
```

### Finding license type IDs

```sql
SELECT id, name FROM license_types ORDER BY name;
```

### Example configurations

| Threshold | Meaning |
|-----------|---------|
| `{"value": 60, "license_type": 3, "condition": 1, "training_categories": [1,2], "hour": 20}` | Alert staff with < 20 CPD hours (categories 1 or 2) whose teaching cert (type 3) expires within 60 days |
| `{"value": 90, "license_type": 3, "condition": 1, "training_categories": [1,2,3], "hour": 30}` | Wider window, more categories, higher hour requirement |
| `{"value": 30, "license_type": 5, "condition": 1, "training_categories": [4], "hour": 8}` | Specialist license requiring 8 hours of category 4 training |

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
| `${license_type.name}` | License type name |
| `${license_number}` | License reference number |
| `${issue_date}` | License issue date |
| `${expiry_date}` | License expiry date |
| `${day_difference}` | Days until expiry |
| `${total_credit_hours}` | Total CPD hours accumulated by this staff member within the license period |
| `${threshold.hour}` | Required CPD hours as configured in the rule |
| `${threshold.value}` | Configured day window |

---

## Example Alert Rules

### Rule 1 — CPD shortfall warning (60 days)

| Field | Value |
|-------|-------|
| **Name** | Teaching License Renewal — Insufficient CPD Hours |
| **Feature** | LicenseRenewal |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 60, "license_type": 3, "condition": 1, "training_categories": [1, 2], "hour": 20}` |
| **Security Roles** | HR Officer, CPD Coordinator |

**Subject:**
```
CPD Hours Shortfall: ${user.first_name} ${user.last_name} — ${license_type.name} at risk
```

**Message body:**
```
Dear CPD Coordinator,

The following staff member's professional license is expiring in ${day_difference} days,
but they have not yet accumulated the required CPD hours for renewal.

Staff Member: ${user.first_name} ${user.last_name}
OpenEMIS ID: ${user.openemis_no}
Institution: ${institution.name}
License: ${license_type.name} (No. ${license_number})
Issue Date: ${issue_date}
Expiry Date: ${expiry_date}
Days Until Expiry: ${day_difference}

CPD Status:
  Hours Completed (within license period): ${total_credit_hours}
  Hours Required: ${threshold.hour}
  Shortfall: ${total_credit_hours} / ${threshold.hour} hours

Action required:
- Identify suitable CPD courses that count towards the license renewal requirement
- Enrol the staff member in the necessary training as soon as possible
- Ensure training is completed and recorded in OpenEMIS before the license expires
- Confirm that training categories match the licensing authority's requirements

Note: This alert will stop automatically once the staff member's CPD hours
meet the required threshold.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Urgent CPD shortfall (14 days)

| Field | Value |
|-------|-------|
| **Name** | Teaching License Renewal — URGENT (14 days left) |
| **Feature** | LicenseRenewal |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 14, "license_type": 3, "condition": 1, "training_categories": [1, 2], "hour": 20}` |
| **Security Roles** | HR Officer, CPD Coordinator, Institution Principal, District HR Director |

**Subject:**
```
URGENT — License Renewal at Risk: ${user.first_name} ${user.last_name} — ${day_difference} days remaining
```

**Message body:**
```
URGENT — IMMEDIATE ACTION REQUIRED

${user.first_name} ${user.last_name} (${user.openemis_no}) at ${institution.name}
has only ${day_difference} days remaining on their ${license_type.name}
(expires: ${expiry_date}) and has completed only ${total_credit_hours} of the
required ${threshold.hour} CPD hours.

Without the required CPD hours, this license may not be renewable.

Escalation actions:
1. Contact the staff member today to discuss their training status
2. Check whether any completed training has not yet been recorded in OpenEMIS
3. Identify any intensive or online CPD options that can be completed before expiry
4. Contact the licensing authority to understand if a short extension is possible
5. Document all steps taken in the staff member's record

This alert will clear automatically if the CPD hours are recorded in OpenEMIS
and meet the required threshold.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Using multiple `LicenseRenewal` rules:

- **Wide-window rule** (60 days, HR and CPD coordinator) → early planning, time to enrol in regular training
- **Narrow-window rule** (14 days, wider audience including principal and district HR) → escalation for urgent cases
- **Different license types** → create a separate set of rules for each license type with different CPD category and hour requirements
- **Different CPD requirements** — if your licensing authority changed the required hours, create a new rule with the updated `hour` value and disable the old one

Each rule is self-contained. A staff member can appear in both the 60-day rule and the 14-day rule on different days as their expiry approaches.

---

## Technical Notes

- Artisan command: `alerts:license-renewal`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:license-renewal \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
