# System Updates — `SystemUpdates`

> **Feature key:** `SystemUpdates` · **Process:** `AlertSystemUpdates`
> **Trigger:** Scheduled · **Default frequency:** `Daily` (the only alert enabled by default)
> **Recipient scope:** Global roles — no institution filter

---

## What It Is

A daily broadcast to configured system administrators notifying them when new platform updates, version releases, or system-level announcements are available in OpenEMIS. It is the platform's built-in channel for keeping the technical team informed without requiring them to log in and check manually.

---

## Purpose

OpenEMIS receives regular updates: bug fixes, feature releases, configuration changes, and maintenance notices. System administrators and IT officers need to be aware of these changes to:
- Plan maintenance windows
- Communicate relevant changes to school staff
- Apply patches or migrations as required
- Understand new features that may require configuration

Without automated notification, update awareness depends on individuals proactively checking dashboards or documentation — which in practice rarely happens consistently. This alert ensures the technical team stays informed passively.

---

## When and How It Fires

This is a **scheduled** alert. The `alerts:check` cron job dispatches `alerts:system-updates` daily (or at whatever frequency is configured). The command queries system update records and sends a notification for each new item since the last run.

This is the **only alert type that has `Daily` frequency enabled by default** in a fresh OpenEMIS installation. All other alerts default to `Never`.

---

## Frequency

**`Daily`** is the recommended and default frequency. System administrators need prompt awareness of platform changes. Weekly would create a backlog of updates delivered in a single notification, making it harder to act on individual items. Daily ensures each update is communicated promptly.

---

## Recipients — Global Roles, No Institution Filter

System updates concern the entire platform, not individual schools. The roles assigned to this rule should be:
- System administrators
- IT infrastructure officers
- Ministry-level management with platform oversight responsibility

There is no institution scope. All users with the assigned roles across the entire system receive the notification.

---

## Threshold Configuration

There is **no threshold** for this alert — it fires whenever new system update records exist. The threshold field is not used.

---

## Available Placeholders

Placeholders for System Updates are limited as the content comes from the system update records themselves rather than student or staff data:

| Placeholder | Value |
|-------------|-------|
| `${update.title}` | Title of the system update |
| `${update.description}` | Description of the update |
| `${update.version}` | Version number if applicable |
| `${update.date}` | Release or announcement date |

> **Note:** The exact placeholder set depends on the `system_updates` table structure in your deployment. Verify available fields before crafting templates.

---

## Example Alert Rule

### Standard system update notification

| Field | Value |
|-------|-------|
| **Name** | OpenEMIS System Update Notification |
| **Feature** | SystemUpdates |
| **Enabled** | Yes |
| **Method** | Email |
| **Threshold** | _(not applicable)_ |
| **Security Roles** | System Administrator, IT Officer |

**Subject:**
```
OpenEMIS Update Available: ${update.title}
```

**Message body:**
```
Dear System Administrator,

A new OpenEMIS system update has been released and is available for your review.

Update: ${update.title}
Version: ${update.version}
Date: ${update.date}

Details:
${update.description}

Recommended actions:
- Review the update notes for any configuration changes required
- Plan and apply any necessary migrations or patches during a maintenance window
- Communicate relevant feature changes to school administrators and staff
- Update the deployment documentation if required

For full release notes and technical details, please log in to the OpenEMIS
administration panel.

This is an automated notification from OpenEMIS.
```

### Second rule — SMS for critical updates

| Field | Value |
|-------|-------|
| **Name** | OpenEMIS Critical Update — SMS Alert |
| **Feature** | SystemUpdates |
| **Enabled** | Yes |
| **Method** | SMS |
| **Security Roles** | System Administrator |

**Subject:**
```
OpenEMIS update: ${update.title} — see email for details
```

**Message body:**
```
OpenEMIS ALERT: New system update available — ${update.title}.
Check your email for full details.
```

---

## Multiple Rules for One Alert

Multiple rules for `SystemUpdates` allow you to:

- **Email detailed notifications** to IT officers with full release notes
- **SMS a brief alert** to the system administrator for immediate awareness
- **Notify ministry management** separately with a management-summary version of the message that omits technical details

Each rule can reach a different audience with a different level of detail, using the same system update data.

---

## Technical Notes

- Artisan command: `alerts:system-updates`
- Dispatched from: `CheckAndQueueAlerts` cron scheduler
- Required parameters: `--user_id`, `--rule_id`, `--process_id`
- Manual test:
  ```bash
  docker exec poe-application /bin/sh -c \
    "cd /var/www/html/emis/core/api && php artisan alerts:system-updates \
     --user_id=1 --rule_id=<id> --process_id=0"
  ```
