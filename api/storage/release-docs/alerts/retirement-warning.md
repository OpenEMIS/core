# Retirement Warning — `RetirementWarning`

> **Feature key:** `RetirementWarning` · **Process:** `AlertRetirementWarning`
> **Trigger:** Scheduled · **Recommended frequency:** `Monthly` (early window) + `Daily` (final window)

---

## What It Is

A reminder alert sent when a staff member's retirement date is approaching within a configured number of days. It gives HR officers and management the lead time they need to begin succession planning before the vacancy arises.

---

## Purpose

Succession planning in education requires advance action: advertising the post, recruiting and interviewing, completing paperwork, onboarding a replacement, and ensuring continuity of teaching or administration. Without advance warning, retirement dates can pass unnoticed until a vacancy appears unexpectedly — at which point it is too late to prevent disruption.

This alert provides a proactive, automated reminder at whatever lead time your deployment deems appropriate.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job runs on the configured frequency (daily, weekly, or monthly) and dispatches `alerts:retirement-warning` for every alert rule with feature `RetirementWarning` that is enabled.

The command queries all staff whose calculated or recorded retirement date falls within the next `threshold.value` days. Every eligible staff member generates a separate notification.

---

## Frequency

**Scheduled — `Monthly`, `Weekly`, or `Daily`** depending on how far in advance you want to alert.

A common practice is to configure **two rules** at different thresholds and frequencies:
- **90-day rule** with `Monthly` frequency → one reminder 3 months out
- **30-day rule** with `Daily` frequency → daily reminders in the final month

This avoids flooding recipients with daily reminders 90 days ahead while ensuring they are reminded regularly in the critical final window.

The scheduler deduplicates: if the command has already run today (same alert + checksum), it will not run again until the next period.

---

## Recipients

Security roles scoped to **the staff member's institution** — specifically the HR officers and administrators at that institution who are responsible for succession planning. A ministry-level HR role can also be included for system-wide oversight.

The institution scope ensures that a retirement at one school does not trigger notifications at all other schools.

---

## Threshold Configuration

The threshold is a JSON object with a single `value` field — the number of days before retirement within which the alert fires.

```json
{"value": 90}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Number of days before the retirement date |

### Examples

| Threshold | Frequency | Meaning |
|-----------|-----------|---------|
| `{"value": 90}` | Monthly | Fire once when retirement is 90 days away |
| `{"value": 60}` | Weekly | Fire weekly when retirement is within 60 days |
| `{"value": 30}` | Daily | Fire daily when retirement is within 30 days |
| `{"value": 7}` | Daily | Final urgent reminder in the last week |

---

## Available Placeholders

> **Note:** RetirementWarning uses bare field names without a `user.` prefix (unlike other staff alerts).

| Placeholder | Value |
|-------------|-------|
| `${threshold.value}` | Configured day threshold |
| `${age}` | Staff member's current age (calculated) |
| `${openemis_no}` | Staff OpenEMIS ID |
| `${first_name}` | First name |
| `${middle_name}` | Middle name |
| `${third_name}` | Third name |
| `${last_name}` | Last name |
| `${preferred_name}` | Preferred name |
| `${email}` | Email address |
| `${address}` | Address |
| `${postal_code}` | Postal code |
| `${date_of_birth}` | Date of birth |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.postal_code}` | Institution postal code |
| `${institution.contact_person}` | Institution contact person |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${institution.website}` | Institution website |

---

## Example Alert Rules

### Rule 1 — 90-day advance notice

| Field | Value |
|-------|-------|
| **Name** | Retirement Warning — 90 Days |
| **Feature** | RetirementWarning |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 90}` |
| **Security Roles** | HR Officer, Institution Principal |

**Subject:**
```
Retirement Notice (90 days): ${first_name} ${last_name} — ${institution.name}
```

**Message body:**
```
Dear HR Officer,

This is an advance notice that the following staff member is approaching their
retirement date within the next ${threshold.value} days.

Staff Member: ${first_name} ${last_name}
OpenEMIS ID: ${openemis_no}
Institution: ${institution.name}

Recommended actions at this stage:
- Review the staff member's position and assess succession requirements
- Begin the process of advertising and recruiting for the vacancy
- Plan knowledge transfer activities with the retiring staff member
- Coordinate with the district HR office if required

Please log in to OpenEMIS for full staff details.

This is an automated notification from OpenEMIS.
```

### Rule 2 — 30-day final reminder

| Field | Value |
|-------|-------|
| **Name** | Retirement Warning — Final 30 Days |
| **Feature** | RetirementWarning |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 30}` |
| **Security Roles** | HR Officer, Institution Principal, District HR Director |

**Subject:**
```
REMINDER: ${first_name} ${last_name} retires within ${threshold.value} days — ${institution.name}
```

**Message body:**
```
Dear Colleague,

FINAL REMINDER: The following staff member retires within ${threshold.value} days.

Staff Member: ${first_name} ${last_name} (${openemis_no})
Institution: ${institution.name}

Immediate actions required:
1. Confirm the replacement or interim arrangement is in place
2. Complete all HR paperwork and benefits processing
3. Schedule a formal handover session
4. Update the staff records in OpenEMIS once the retirement is finalised

If succession planning has not yet started, escalate immediately to district HR.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Configuring multiple rules for `RetirementWarning` is the recommended approach for most deployments:

- **Early-warning rule** (90 days, monthly) → broad notification for planning purposes, goes to HR and principal
- **Mid-term rule** (60 days, weekly) → prompt for active recruitment, adds district HR
- **Final-window rule** (30 days, daily) → urgent daily reminder with escalation instructions, widest audience
- **SMS rule** (7 days, daily) → last-resort SMS to principal if email has been ignored

This layered approach ensures no retirement date is missed at any planning stage.

---

## Technical Notes

- Artisan command: `alerts:retirement-warning`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:retirement-warning \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
