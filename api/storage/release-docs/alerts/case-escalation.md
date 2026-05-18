# Case Escalation — `CaseEscalation`

> **Feature key:** `CaseEscalation` · **Process:** `AlertCaseEscalation`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`
> **Added in:** POCOR-9509

---

## What It Is

An alert sent when an institution case — a student welfare issue, infrastructure problem, compliance matter, or any tracked incident — has been sitting in a specific workflow step for longer than a configured number of days without any update. It surfaces stale, untouched cases before they become serious failures.

---

## Purpose

Cases are opened with good intentions but action is not always guaranteed. Staff get busy, cases get buried, and the problem that prompted the case may worsen in the meantime. Without accountability, a case opened months ago can remain perpetually "Open" with no one having taken a single action.

This alert creates that accountability. It fires specifically for cases that have **never been touched since they were created** — not just cases that haven't been closed. Once a staff member makes any update to the case, it immediately drops out of the alert entirely. The signal is: someone has eyes on this case. Before that moment, the system escalates daily.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:case-escalation` at the configured frequency.

The command queries `institution_cases` and applies all three conditions simultaneously:

1. `status_id` is in the list of monitored `workflow_steps` IDs (e.g., the "Open" step)
2. `modified IS NULL` **and** `modified_user_id IS NULL` — the case record has **never been updated** since it was created
3. `DATEDIFF(NOW(), created) > threshold.value` — the case has been open longer than the threshold

A case that passes all three conditions generates a notification to the institution's configured roles.

---

## Frequency

**`Daily`** is the standard frequency. Staleness is gradual — there is no single moment when a case "becomes" stale. Daily scanning ensures each case is caught on the exact day it crosses the threshold, and continues sending reminders every day until someone acts. This is intentional pressure.

---

## Recipients

Security roles scoped to **the case's institution** — specifically the staff with management responsibility at the school where the case was opened. Assigning the Institution Principal or Institution Coordinator role to this rule ensures the correct management level is notified. System-wide administrators at other institutions are not alerted.

---

## Threshold Configuration

The threshold specifies the number of days before escalating and which workflow steps to monitor.

```json
{"value": 7, "workflow_steps": [12]}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Cases older than this many days (and unmodified) will be escalated |
| `workflow_steps` | Array of integers | Yes | IDs from `workflow_steps` table — only cases in these steps are checked |

### Finding the correct workflow step IDs

Run this query against your database to find the step IDs for your case workflow:

```sql
SELECT ws.id, ws.name, w.name AS workflow_name
FROM workflow_steps ws
JOIN workflows w ON w.id = ws.workflow_id
WHERE w.name LIKE '%Case%'
ORDER BY w.name, ws.name;
```

Typical result for a "Cases — General" workflow:

| id | name | workflow_name |
|----|------|---------------|
| 12 | Open | Cases — General |
| 13 | In Progress | Cases — General |
| 14 | Closed | Cases — General |

You would typically monitor only the `Open` step (cases that have never progressed). Including `In Progress` is also valid if you want to catch cases that were started but then abandoned.

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 7, "workflow_steps": [12]}` | Escalate cases in "Open" step, untouched for more than 7 days |
| `{"value": 3, "workflow_steps": [12]}` | Shorter tolerance — escalate after just 3 days of inactivity |
| `{"value": 14, "workflow_steps": [12, 13]}` | Escalate both "Open" and "In Progress" cases untouched for 14 days |
| `{"value": 30, "workflow_steps": [12]}` | Longer grace period — escalate only persistent inaction (30+ days) |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${case.case_number}` | Case reference number |
| `${case.title}` | Case title |
| `${case.description}` | Case description (may be long) |
| `${case.created}` | Date and time the case was opened |
| `${case.status}` | Current workflow step name |
| `${case.type}` | Case type name |
| `${case.priority}` | Case priority name |
| `${days_open}` | Number of days since the case was created |
| `${assignee.name}` | Full name of the case assignee |
| `${assignee.first_name}` | Assignee first name |
| `${assignee.last_name}` | Assignee last name |
| `${assignee.email}` | Assignee email address |
| `${institution.name}` | Institution name |
| `${institution.code}` | Institution code |
| `${institution.address}` | Institution address |
| `${institution.telephone}` | Telephone |
| `${institution.email}` | Institution email |
| `${threshold.value}` | Configured day threshold |

---

## Example Alert Rules

### Rule 1 — Standard escalation (7 days)

| Field | Value |
|-------|-------|
| **Name** | Case Escalation — 7 Day Inactivity |
| **Feature** | CaseEscalation |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 7, "workflow_steps": [12]}` |
| **Security Roles** | Institution Principal, Institution Coordinator |

**Subject:**
```
Case Escalation: "${case.title}" — ${days_open} days without action at ${institution.name}
```

**Message body:**
```
Dear ${institution.name} Management,

The following case has been open for ${days_open} days with no action recorded.
This exceeds the escalation threshold of ${threshold.value} days.

Case Details:
  Reference: ${case.case_number}
  Title: ${case.title}
  Type: ${case.type}
  Priority: ${case.priority}
  Status: ${case.status}
  Assigned To: ${assignee.name}
  Date Opened: ${case.created}
  Days Open: ${days_open}

Description:
  ${case.description}

Required action:
- Log in to OpenEMIS and review this case immediately
- Assign it to the appropriate staff member if not yet assigned
- Record an initial assessment or action in the case notes
- Update the case status to reflect current progress

Once any update is made to this case, these escalation alerts will stop.

This is an automated notification from OpenEMIS.
```

### Rule 2 — Critical escalation (21 days, wider audience)

| Field | Value |
|-------|-------|
| **Name** | Case Escalation — Critical 21 Days |
| **Feature** | CaseEscalation |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 21, "workflow_steps": [12]}` |
| **Security Roles** | Institution Principal, District Education Officer, Ministry Case Coordinator |

**Subject:**
```
CRITICAL — Case Unresolved for ${days_open} Days: "${case.title}" at ${institution.name}
```

**Message body:**
```
ATTENTION — MANAGEMENT ESCALATION

The following case at ${institution.name} has been open for ${days_open} days
with no recorded action. This requires immediate management attention.

Case Reference: ${case.case_number}
Title: ${case.title}
Priority: ${case.priority}
Assigned To: ${assignee.name} (${assignee.email})
Days Open Without Action: ${days_open}

This case has now exceeded ${threshold.value} days without any update. The
district education officer and ministry coordinator have been copied on this
escalation.

This case must be actioned, escalated, or formally closed immediately.
Continued inaction will trigger further management review.

Log in to OpenEMIS to access the full case record.

This is an automated notification from OpenEMIS.
```

### Rule 3 — Priority cases with shorter threshold

| Field | Value |
|-------|-------|
| **Name** | Priority Case Escalation — 3 Days |
| **Feature** | CaseEscalation |
| **Enabled** | Yes |
| **Method** | Email + SMS |
| **Threshold** | `{"value": 3, "workflow_steps": [12]}` |
| **Security Roles** | Institution Principal |

**Subject:**
```
Urgent: Case "${case.title}" needs attention — ${days_open} days open
```

**Message body:**
```
${case.title} (${case.case_number}) at ${institution.name} has been open
for ${days_open} days without any action.

Type: ${case.type} | Priority: ${case.priority}
Assigned to: ${assignee.name}

Please log in to OpenEMIS and take action on this case today.
```

---

## Multiple Rules for One Alert

`CaseEscalation` is ideal for a tiered escalation approach:

- **3-day rule** (principal only) → quick nudge for high-priority institutional cases
- **7-day rule** (principal + coordinator) → standard escalation with full case details
- **21-day rule** (principal + district + ministry) → critical escalation for cases that have been ignored despite earlier reminders

All three rules can coexist. A case that has been open for 25 days without action will trigger all three rules daily (since all thresholds are exceeded). The 3-day rule fires first on day 4; the 7-day rule begins on day 8; the 21-day rule begins on day 22.

Once any user updates the case, it drops out of all three rules immediately.

---

## Technical Notes

- Artisan command: `alerts:case-escalation`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:case-escalation \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
- Find correct workflow step IDs before configuring:
  ```sql
  SELECT ws.id, ws.name, w.name
  FROM workflow_steps ws JOIN workflows w ON w.id = ws.workflow_id
  WHERE w.name LIKE '%Case%';
  ```
