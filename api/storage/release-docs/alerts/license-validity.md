# License Validity — `LicenseValidity`

> **Feature key:** `LicenseValidity` · **Process:** `AlertLicenseValidity`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`

---

## What It Is

An alert sent when a staff member's professional license (teaching certificate, driver's license for school bus drivers, first aid certification, etc.) is approaching its expiry date or has recently expired. It notifies both the staff member's institution administrators and HR officers before a compliance issue arises.

---

## Purpose

Operating with an expired professional license is a legal and compliance risk for both the individual and the institution. Many teaching certificates and specialist qualifications carry regulatory requirements — an expired certificate may invalidate a teacher's right to practise, triggering insurance, safeguarding, or regulatory consequences.

Without automated tracking, license expiry dates must be monitored manually — a task that becomes unmanageable as staff numbers grow. This alert automates that monitoring, providing advance warning so renewal can be arranged before the license lapses.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:license-validity` at the configured frequency.

The command queries `staff_licenses` filtered by the configured license type and applies the expiry window condition. For each license found, the system then checks `institution_staff` to find all institutions where the staff member is **actively assigned** (status = ASSIGNED). One notification is generated per institution assignment — so a staff member assigned to two schools triggers two separate alert notifications (one to each school's roles).

---

## Frequency

**`Daily`** is the standard frequency. License expiry is not a system event — there is no moment when "expiry is near" is recorded in the database. Daily scanning with a rolling window catches every license in the danger zone and continues reminding until action is taken. Deduplication ensures only one notification per rule per day even if the command runs multiple times.

---

## Recipients

Security roles scoped to **each institution the staff member is actively assigned to** (via `institution_staff` ASSIGNED status). Licenses are personal credentials but their lapse affects the institution where the staff member works. By resolving recipients through the institution assignment, only directly affected schools are notified — not every school in the system.

---

## Threshold Configuration

The threshold specifies the license type, the time window, and the direction of the check (before or after expiry).

```json
{"value": 30, "license_type": 3, "condition": 1}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Number of days for the expiry window |
| `license_type` | Integer | Yes | ID from `license_types` table |
| `condition` | Integer | Yes | `1` = expiring within `value` days · `2` = expired within the last `value` days |

### Condition values explained

| Condition | SQL equivalent | Use case |
|-----------|---------------|---------|
| `1` | `DATEDIFF(expiry_date, NOW()) BETWEEN 0 AND value` | Advance warning before expiry |
| `2` | `DATEDIFF(NOW(), expiry_date) BETWEEN 0 AND value` | Post-expiry follow-up |

### Finding license type IDs

```sql
SELECT id, name FROM license_types ORDER BY name;
```

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 60, "license_type": 3, "condition": 1}` | Teaching certificate expiring within 60 days |
| `{"value": 30, "license_type": 3, "condition": 1}` | Teaching certificate expiring within 30 days |
| `{"value": 14, "license_type": 5, "condition": 1}` | Driver's license expiring within 14 days |
| `{"value": 7, "license_type": 3, "condition": 2}` | Teaching certificate that has already expired (post-expiry chase) |

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
| `${license_type.name}` | License type name (e.g., "Teaching Certificate") |
| `${license_number}` | License reference number |
| `${issue_date}` | Date the license was issued |
| `${expiry_date}` | License expiry date |
| `${day_difference}` | Days until expiry (positive) or days since expiry (negative) |
| `${threshold.value}` | Configured threshold (days) |

---

## Example Alert Rules

### Rule 1 — Teaching certificate expiring in 30 days

| Field | Value |
|-------|-------|
| **Name** | Teaching Certificate — 30 Day Expiry Warning |
| **Feature** | LicenseValidity |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 30, "license_type": 3, "condition": 1}` |
| **Security Roles** | HR Officer, Institution Principal |

**Subject:**
```
License Expiry Warning: ${user.first_name} ${user.last_name} — ${license_type.name} expires in ${day_difference} days
```

**Message body:**
```
Dear HR Officer,

This is an automated alert regarding a professional license approaching its
expiry date at ${institution.name}.

Staff Member: ${user.first_name} ${user.last_name}
OpenEMIS ID: ${user.openemis_no}
License Type: ${license_type.name}
License Number: ${license_number}
Issue Date: ${issue_date}
Expiry Date: ${expiry_date}
Days Until Expiry: ${day_difference}
Institution: ${institution.name}

Action required:
- Contact the staff member immediately to initiate the renewal process
- Verify what documentation is required by the licensing authority
- Ensure the renewal is submitted well before the expiry date to avoid a gap in coverage
- Update the license record in OpenEMIS once renewal is confirmed

Failure to renew before expiry may result in the staff member being unable
to practise legally.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Post-expiry follow-up

| Field | Value |
|-------|-------|
| **Name** | Teaching Certificate — Post-Expiry Follow-Up |
| **Feature** | LicenseValidity |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 7, "license_type": 3, "condition": 2}` |
| **Security Roles** | HR Officer, Institution Principal, District HR Director |

**Subject:**
```
URGENT: Expired License — ${user.first_name} ${user.last_name} at ${institution.name}
```

**Message body:**
```
URGENT — COMPLIANCE ISSUE

${user.first_name} ${user.last_name} (${user.openemis_no}) holds a ${license_type.name}
(No. ${license_number}) that expired on ${expiry_date} — ${day_difference} days ago.

This staff member may not be authorised to practise until the license is renewed.

Immediate actions:
1. Verify whether the staff member is currently performing duties requiring this license
2. If yes: suspend those duties until the license is renewed
3. Contact the licensing authority to expedite renewal
4. Document the compliance gap in the staff member's record
5. Update OpenEMIS once the renewed license is received

Please escalate to the district HR office if the renewal cannot be completed
within 5 working days.

This is an automated notification from OpenEMIS.
```

### Rule 3 — Driver's license (different license type)

| Field | Value |
|-------|-------|
| **Name** | Driver's License — 14 Day Warning |
| **Feature** | LicenseValidity |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 14, "license_type": 5, "condition": 1}` |
| **Security Roles** | Transport Coordinator, Institution Administrator |

**Subject:**
```
Driver License Expiry: ${user.first_name} ${user.last_name} — expires in ${day_difference} days
```

**Message body:**
```
Dear Transport Coordinator,

${user.first_name} ${user.last_name}'s driver's license (${license_number})
expires on ${expiry_date} — in ${day_difference} days.

This staff member operates a school vehicle at ${institution.name}.

Please arrange renewal immediately and update the vehicle assignment record
in OpenEMIS. The staff member should not operate school vehicles after the
license expiry date until a renewed license is confirmed.
```

---

## Multiple Rules for One Alert

`LicenseValidity` is designed for multi-rule use because:
- Different license types require different urgency levels and different recipients
- Pre-expiry and post-expiry scenarios require different messaging and actions
- Wide (60-day) and narrow (7-day) windows serve different planning purposes

Recommended configuration:
- **60-day rule** (condition 1) → early planning notice to HR
- **30-day rule** (condition 1) → active renewal reminder with full instructions
- **7-day rule** (condition 1) → urgent final warning, escalated audience
- **7-day rule** (condition 2) → post-expiry compliance alert, widest audience

Repeat the pattern for each license type that requires monitoring in your deployment.

---

## Technical Notes

- Artisan command: `alerts:license-validity`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:license-validity \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
