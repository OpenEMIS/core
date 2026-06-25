# Scholarship Application Closing — `ScholarshipApplication`

> **Feature key:** `ScholarshipApplication` · **Process:** `AlertScholarshipApplication`
> **Trigger:** Scheduled · **Recommended frequency:** `Daily`
> **Recipient model:** Direct to assignee — **not** role-based

---

## What It Is

A personal reminder sent directly to the staff member assigned to process a scholarship application when the scholarship's application close date is approaching and the application is still pending a decision.

---

## Purpose

Scholarship applications go through a review and approval process that requires human decisions. Each application is assigned to a specific person responsible for moving it through the workflow. As close dates approach, assigned reviewers can lose track of pending items among their other responsibilities.

This alert acts as a personal, direct reminder to the responsible individual — not a broadcast to a group. The design is intentional: broadcasting to all scholarship administrators would create confusion, duplicate responses, and diffuse responsibility. The assigned person receives the reminder; they own the decision.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:scholarship-application` at the configured frequency.

The command queries `scholarship_applications` joined to `scholarships`, and applies two simultaneous filters:
1. The scholarship's `application_close_date` is within `threshold.value` days
2. The application's workflow status category matches `threshold.category` (typically `PENDING`)

Applications that have already been approved, rejected, or progressed past the pending stage drop out of the query automatically — the alert only fires for applications still awaiting action.

---

## Frequency

**`Daily`** is the standard frequency. Scholarship deadlines are fixed and non-negotiable. Daily reminders in the final window before close date ensure the deadline is not missed — particularly if the assigned person has been absent or occupied.

---

## Recipients — Direct Assignee Only

This is the **only alert that bypasses role-based recipient resolution**. The notification goes directly to the `email` address of the application's `assignee_id` — the specific person assigned to process that application.

There are no security roles to configure for this alert. The recipient is always the assignee on the record.

This design reflects the scholarship workflow: responsibility is explicitly assigned, and the alert reinforces that personal accountability.

---

## Threshold Configuration

The threshold specifies the time window, direction, and which workflow status category to include.

```json
{"value": 7, "condition": 1, "category": "PENDING"}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | Integer | Yes | Days before `application_close_date` to start alerting |
| `condition` | Integer | Yes | `1` = before close date (standard) · `2` = after close date (overdue) |
| `category` | String | Yes | Workflow step category to filter on — typically `"PENDING"` |

### Finding workflow step categories

```sql
SELECT DISTINCT category FROM workflow_steps WHERE category IS NOT NULL ORDER BY category;
```

Common categories: `PENDING`, `OPEN`, `CLOSE`, `APPROVED`, `REJECTED`

### Examples

| Threshold | Meaning |
|-----------|---------|
| `{"value": 7, "condition": 1, "category": "PENDING"}` | Alert 7 days before close, for pending applications only |
| `{"value": 14, "condition": 1, "category": "PENDING"}` | Alert 14 days before close — wider warning window |
| `{"value": 3, "condition": 2, "category": "PENDING"}` | Alert for applications that are now past close date and still pending (overdue) |

---

## Available Placeholders

| Placeholder | Value |
|-------------|-------|
| `${assignee.name}` | Full name of the assigned reviewer |
| `${assignee.first_name}` | First name |
| `${assignee.last_name}` | Last name |
| `${assignee.email}` | Email address |
| `${scholarship.name}` | Scholarship name |
| `${scholarship.code}` | Scholarship code |
| `${scholarship.application_close_date}` | Application deadline date |
| `${scholarship.maximum_award_amount}` | Maximum award value |
| `${day_difference}` | Days until the close date |
| `${threshold.value}` | Configured threshold (days) |

---

## Example Alert Rules

### Rule 1 — 7-day deadline reminder

| Field | Value |
|-------|-------|
| **Name** | Scholarship Application — 7 Day Deadline Reminder |
| **Feature** | ScholarshipApplication |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 7, "condition": 1, "category": "PENDING"}` |
| **Security Roles** | _(not applicable — sent directly to application assignee)_ |

**Subject:**
```
Action Required: Scholarship application closing in ${day_difference} days — ${scholarship.name}
```

**Message body:**
```
Dear ${assignee.first_name},

You have a scholarship application assigned to you that requires a decision
before the application close date.

Scholarship: ${scholarship.name} (${scholarship.code})
Close Date: ${scholarship.application_close_date}
Days Remaining: ${day_difference}
Maximum Award: ${scholarship.maximum_award_amount}

This application is currently pending your review and decision. Please log in
to OpenEMIS to review the application and take the appropriate action:
  - Approve the application if it meets all criteria
  - Request additional information if required
  - Reject with documented reasons if the application does not qualify

Applications not decided before the close date may be automatically excluded
from consideration.

Please act on this application as soon as possible.

This is an automated notification from OpenEMIS.
```

### Rule 2 — 3-day urgent reminder

| Field | Value |
|-------|-------|
| **Name** | Scholarship Application — URGENT 3 Days |
| **Feature** | ScholarshipApplication |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 3, "condition": 1, "category": "PENDING"}` |

**Subject:**
```
URGENT — ${day_difference} days left to decide: ${scholarship.name} application
```

**Message body:**
```
Dear ${assignee.first_name},

URGENT REMINDER: You have ${day_difference} days to make a decision on the
following scholarship application assigned to you.

Scholarship: ${scholarship.name}
Close Date: ${scholarship.application_close_date}

This application will be past its close date in ${day_difference} days. If no
decision is recorded in OpenEMIS before that date, the application may be
excluded automatically.

Please log in to OpenEMIS immediately and process this application.

If you believe this application has been incorrectly assigned to you,
contact your administrator to reassign it before the deadline.

This is an automated notification from OpenEMIS.
```

### Rule 3 — Post-close overdue chase

| Field | Value |
|-------|-------|
| **Name** | Scholarship Application — Overdue (Post-Close) |
| **Feature** | ScholarshipApplication |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | `{"value": 5, "condition": 2, "category": "PENDING"}` |

**Subject:**
```
OVERDUE: Scholarship application past close date — ${scholarship.name}
```

**Message body:**
```
Dear ${assignee.first_name},

The following scholarship application assigned to you has passed its close date
and is still pending a decision.

Scholarship: ${scholarship.name}
Close Date: ${scholarship.application_close_date}
Status: Past close date — pending decision

Please log in to OpenEMIS and either process or formally close this application.
Leaving it in a pending state beyond the close date may affect reporting
and system integrity.

This is an automated notification from OpenEMIS.
```

---

## Multiple Rules for One Alert

Multiple rules can be created for `ScholarshipApplication`:

- **14-day rule** → early reminder with full context
- **7-day rule** → standard deadline reminder
- **3-day rule** → urgent escalation with stronger language
- **Post-close rule** (condition 2) → overdue chase for applications missed entirely

Each rule fires independently. An assignee with an application in the 3-day window will receive the 3-day reminder. If they received the 7-day and 14-day reminders earlier (on earlier days), those fired on their respective dates.

Note: because recipients are the application assignee (not a role), you cannot use different rules to target different people for the same application. To escalate to a supervisor when an application is overdue, a custom workflow step or separate module is required.

---

## Technical Notes

- Artisan command: `alerts:scholarship-application`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Recipient resolution: direct `assignee_id` lookup — **no security roles required**
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:scholarship-application \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
