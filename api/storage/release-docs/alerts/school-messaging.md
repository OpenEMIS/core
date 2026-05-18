# School Messaging — `Messaging`

> **Module:** Institution → Communications → Messaging
> **Trigger:** Manual — composed and sent by a staff member
> **Delivery:** Email and/or SMS
> **Status model:** Draft → Sent (one-way, no repeats)

---

## What It Is

School Messaging is a direct communication tool that lets authorised institution staff compose and send free-form messages to targeted groups of students, guardians, or staff within their institution. Unlike the Alerts module — which fires automatically based on rules and thresholds — Messaging is always **manually initiated** by a person with the appropriate permissions.

Think of it as a school-level broadcast email or SMS, but scoped precisely: you choose whether to address the whole institution, a specific programme, a grade, a class, or a subject group.

---

## How It Differs from Alerts

| Feature | Alerts | School Messaging |
|---------|--------|-----------------|
| Triggered by | Rules + thresholds / data events | A person composing a message |
| Content | Template with `${placeholder}` tokens | Free-form subject and body |
| Frequency | Repeats (Daily/Weekly/Monthly) | Sends once — cannot be resent |
| Scope | System-wide or institution | Always within one institution |
| Recipients | Resolved automatically by security role | Chosen by the sender (level → group → role) |
| Logs | Alert Logs + Alert Queue | Alert Logs (type: Messaging) |
| Use case | Automated compliance reminders | Teacher announcement, parent notice, urgent event |

---

## Navigation

**Institution → Communications → Messaging**

From an institution's main page, navigate to the **Communications** tab and select **Messaging**.

---

## The Messaging Screen

The Messaging index lists all messages created within the current institution — both drafts and sent messages. Each row shows:

| Column | Description |
|--------|-------------|
| Subject | Message subject line |
| Recipient Level | Institution / Programme / Grade / Class / Subject |
| Status | Draft or Sent |
| Created | Date created |
| Created By | Staff member who composed the message |

---

## Recipient Levels

The **Recipient Level** controls the scope of the message. It determines what groups appear in the **Recipient Group** dropdown and which security roles can be selected as recipients.

| Level | Who receives it | When to use |
|-------|----------------|-------------|
| **Institution** | All staff with selected roles at the institution | School-wide announcements to staff |
| **Programme** | Students (and their guardians) in a specific programme | Programme-specific notices |
| **Grade** | Students (and their guardians) in a specific grade | Grade-level announcements |
| **Class** | Students (and their guardians) in a specific class | Class teacher to class parents/students |
| **Subject** | Students (and their guardians) in a specific subject | Subject teacher to subject group |

> **Important:** For **Programme** and **Grade** levels, only two roles are available: **Student** and **Guardian**. For **Institution**, **Class**, and **Subject** levels, all security roles assigned at the institution are available (plus Guardian).

---

## Composing a Message

### Step 1 — Open the compose form

1. Go to **Institution → Communications → Messaging**
2. Click **Add** in the toolbar

### Step 2 — Fill in the message fields

| Field | Required | Description |
|-------|----------|-------------|
| **Academic Period** | Yes | Scopes the recipient group to the selected year |
| **Recipient Level** | Yes | Choose: Institution, Programme, Grade, Class, or Subject |
| **Recipient Group** | Yes | Appears after selecting Level — the specific group (e.g., "Grade 5", "Class 5A", "Mathematics") |
| **Security Role** | Yes | Who within the group receives the message (e.g., Student, Guardian, Class Teacher) — multi-select |
| **Method** | Yes | Email, SMS, or both |
| **Subject** | Yes | Message subject line (plain text) |
| **Message** | Yes | Full message body (plain text) |

> **Cascading dropdowns:** Recipient Group and Security Role are dynamic — they reload when you change Recipient Level. Always select Recipient Level first, then Recipient Group, then Security Role.

### Step 3 — Save as draft or send immediately

Two buttons are available:

| Button | Action |
|--------|--------|
| **Save** | Saves the message as a **Draft** — no delivery occurs. You can return to edit and send later. |
| **Send** | Saves and immediately delivers the message to all resolved recipients. |

> **Once sent, a message cannot be edited or resent.** The Edit button is removed from the action bar for Sent messages.

---

## How Recipients Are Resolved

When you click **Send**, the system resolves the actual recipient list in two steps:

1. **Role matching** — based on the selected Recipient Level and Recipient Group, the system finds all users who hold the selected security roles within that scope (e.g., all guardians of students in Class 5A)
2. **Contact filtering** — for Email: only users with a non-null `email` field are included. For SMS: only users with a non-null `mobile_number` are included.

If no users have the required contact information, the system shows:
> *"Failed to send: No Recipients With Contacts Found."*

If no users match the role/group combination at all:
> *"Failed to send: No Recipients Found."*

**Recipients are de-duplicated** — if a guardian is a parent of two students in the same class, they receive only one email/SMS.

---

## Delivery Process

After Send is clicked:

```
Message saved to database (status = Draft)
         ↓
message_recipients table populated (one row per resolved recipient)
         ↓
sendEmailMessages() / sendSmsMessages() called
         ↓
AlertLogs entries created (one per recipient, type = "Email" or "SMS", module = "Messaging")
         ↓
Message status set to Sent
         ↓
Success: "Message has been queued for sending"
```

Delivery is handled by the same `ProcessAlertQueue` worker used by the Alerts module. You can verify delivery in **Administration → Communications → Alert Logs** — filter by module "Messaging".

---

## Viewing a Sent Message

1. Go to **Institution → Communications → Messaging**
2. Click **View** on any message row

The view shows all fields plus:
- **Message status** — Draft or Sent
- **Modified** — last modification date
- **Modified By** — who last edited (before sending)
- **Created** — creation date
- **Created By** — who composed it

A **Recipient Logs** tab may appear alongside the message tab, showing individual delivery records.

---

## Editing a Draft

Drafts can be edited at any time:

1. Click **Edit** on a Draft message in the index
2. Any field can be changed — recipient level, group, roles, method, subject, message
3. Click **Save** to keep as draft, or **Send** to deliver

> **Sent messages have no Edit button.** If you need to send a correction, compose a new message.

---

## Deleting a Message

Both drafts and sent messages can be deleted from the index:

1. Click **Delete** in the Action Bar for the target message
2. Confirm deletion

Deleting a sent message removes the `messaging` record but does **not** recall already-delivered emails or SMS messages. Alert Log entries for that message remain in the audit trail.

---

## Common Failure Reasons

| Error message | Cause | Fix |
|---------------|-------|-----|
| "No Recipients Found" | No users match the selected role/group combination | Verify the selected Recipient Group has enrolled students or assigned staff with the selected role |
| "No Recipients With Contacts Found" | Users exist but have no email or mobile number on their profile | Ask users to add contact information to their profile under User Overview → Edit → Contact |
| "Message Already Sent" | Attempting to send a message that is already in Sent status | A sent message cannot be resent — compose a new one if needed |
| "Validation or Save Error" | Required field missing or invalid | Ensure all required fields are filled and the Security Role selection is not empty |

---

## Permissions

Access to Messaging is controlled by security role permissions:

| Permission | Access |
|------------|--------|
| Full access | Compose, save drafts, send, view, delete |
| View only | Browse and view messages; cannot compose or send |
| No access | Messaging tab not visible |

Configure in **Administration → Security → Roles → Institution → Communications → Messaging**.

---

## Integration with Alert Logs

Every message sent via School Messaging is recorded in **Administration → Communications → Alert Logs** — the same audit trail used by automated alerts. Filter by:
- **Module:** `Messaging`
- **Type:** `Email` or `SMS`

This allows administrators to review all school communications in one place, regardless of whether they were automated alerts or manually composed messages.

---

## Practical Examples

### Example 1 — Class teacher announcing a field trip

| Field | Value |
|-------|-------|
| Recipient Level | Class |
| Recipient Group | Grade 7 — Class 7B |
| Security Role | Guardian |
| Method | Email |
| Subject | Field Trip Notice: Grade 7B — National Museum, 15 April |
| Message | Dear Parent/Guardian, This is to inform you that Grade 7B will be attending a field trip to the National Museum on Wednesday 15 April. Students should arrive at school by 7:30 AM. Please ensure the consent form is signed and returned by 10 April. Contact the school office if you have questions. |

### Example 2 — Principal sending a school closure notice to all staff

| Field | Value |
|-------|-------|
| Recipient Level | Institution |
| Recipient Group | _(the institution itself)_ |
| Security Role | All staff roles |
| Method | Email + SMS |
| Subject | School Closed — Public Holiday 18 April |
| Message | Dear Staff, Please note that the school will be closed on Friday 18 April in observance of the public holiday. Normal operations resume on Monday 21 April. |

### Example 3 — Subject teacher contacting students directly

| Field | Value |
|-------|-------|
| Recipient Level | Subject |
| Recipient Group | Mathematics — Grade 9 |
| Security Role | Student |
| Method | Email |
| Subject | Homework reminder: Chapter 5 exercises due Monday |
| Message | Dear Students, This is a reminder that Chapter 5 exercises (pages 87–92) are due on Monday. Please ensure all work is completed and submitted at the start of class. |

---

## Technical Notes

- **Table:** `messaging`
- **Related tables:** `messaging_security_roles`, `message_recipients`, `alert_logs`
- **Status values:** `0` = Draft · `1` = Sent
- **Recipient level constants:** `1` = Institution · `2` = Programme · `3` = Grade · `4` = Class · `5` = Subject
- **Delivery logging:** `AlertLogsTable::insertAlertLog()` — same method used by the Alerts module
- **Source file:** `plugins/Institution/src/Model/Table/MessagingTable.php`
