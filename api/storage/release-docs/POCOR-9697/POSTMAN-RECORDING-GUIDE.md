# POCOR-9697 — Postman + DB side-by-side recording guide

Use this collection to record **two videos**:

1. **BEFORE.mp4** — run against `master`. The attack steps go ✅ in Postman but the DB shows the system was compromised. This is the proof of the vulnerability.
2. **AFTER.mp4** — run against `POCOR-9697`. The same requests run, the attack steps still return 200/201, but every defence assertion goes ✅ and the DB stays clean. This is the proof of the fix.

Same collection, same requests, same order. Only the deployed code changes between recordings.

The collection now covers **four waves of hardening**:

| Wave | Items | Defence |
|---|---|---|
| Wave 1a — write-side mass-assignment | 01–07 | Silent-strip `super_admin` + plaintext-password hashing |
| Wave 1b — response & schema leaks | 06–09 | Hide `super_admin` / `password` from JSON + Swagger |
| Wave 1c — read-side enumeration | 09a–09d | Silently drop `_conditions` filters on non-`$fillable` columns |
| Wave 2 — audit-trail forgery | AUDIT-1..5 | Always derive `created_user_id` / `modified_user_id` from JWT |
| Wave 3 — audit log | WAVE3-1..4 | Mirror CakePHP `user_activities` audit trail in Laravel |

---

## Setup (do once)

1. **Disable SSL verification** — Postman → Settings → General → uncheck *SSL certificate verification*. (Local dev cert is self-signed.)
2. **Import** `POCOR-9697.postman_collection.json` into Postman.
3. **Edit collection variables** if your environment differs from defaults:
   - `base_url` → e.g. `https://localhost:8482/core`
   - `username` / `password` → an admin account (e.g. `admin` / `demo`)
   - `api_key` → `apikeytest` for the seeded test API key
4. **Open the database in a second pane** so it's visible on screen at the same time as Postman. Use whichever client you prefer; the commands below assume the local MySQL CLI:
   ```bash
   mysql -h 127.0.0.1 -P 8136 -u root -prootpassword openemis_core_v5
   ```
   Or phpMyAdmin at http://localhost:8183/.
5. **Record one screen** that shows Postman on one side and the DB pane on the other. A small terminal for narration is optional.

### Recommended screen layout

```
┌────────────────────────────┬───────────────────────┐
│                            │                       │
│   Postman Runner panel     │   mysql / phpMyAdmin  │
│   (test results, body)     │   (security_users +   │
│                            │    user_activities)   │
└────────────────────────────┴───────────────────────┘
```

The DB pane is what makes the BEFORE video convincing — the audience must SEE the `super_admin=1` row appear, then see it stay at `0` in AFTER.

---

## DB queries to keep ready (paste into a notes panel)

These are the queries you'll run during the recording, in roughly the order they're needed. Don't memorise them — keep them in a `.sql` scratchpad and copy-paste.

```sql
-- (A) Before/After snapshot of any exploit attempts
SELECT id, username, super_admin, status, created_user_id, modified_user_id, created, modified
FROM security_users WHERE username LIKE 'exploit_%' ORDER BY id DESC LIMIT 5;

-- (B) Existing low-priv user (id=3, poc_v5_update) — Wave 1a step 02 attacks this row
-- We do NOT target id=2 because that's the admin we log in as; it's already super_admin=1.
SELECT id, username, super_admin, modified_user_id, modified
FROM security_users WHERE id = 3;

-- (C) Audit log — Wave 3 writes here
SELECT id, model, model_reference, field, operation, security_user_id, created_user_id, created,
       LEFT(old_value, 30) AS old_value, LEFT(new_value, 30) AS new_value
FROM user_activities WHERE model_reference = @demo_user_id ORDER BY id DESC LIMIT 20;
-- (replace @demo_user_id with the actual new-user id from WAVE3-1 response)

-- (D) Pre-flight cleanup if you want to re-run from scratch
DELETE FROM user_activities WHERE model = 'SecurityUsers' AND model_reference IN
  (SELECT id FROM security_users WHERE username LIKE 'exploit_%' OR username LIKE 'demo_%');
DELETE FROM security_users WHERE username LIKE 'exploit_%' OR username LIKE 'demo_%';
```

---

## Pre-flight (run before EACH video, not between every request)

```sql
-- Reset the elevation target (id=3, poc_v5_update) back to super_admin=0 in case a prior BEFORE run elevated it
UPDATE security_users SET super_admin = 0 WHERE id = 3 AND username = 'poc_v5_update';
-- Admin (id=2) is the JWT login account; it must STAY at super_admin=1 — do not reset it.

-- Run cleanup (D) from the section above
```

Then check out the right branch and rebuild:

```bash
# BEFORE video
git checkout master

# AFTER video
git checkout POCOR-9697

# After either checkout — clear Laravel caches so route + config changes take effect
docker exec poe-application sh -c "cd /var/www/html/emis/core/api && php artisan route:clear && php artisan config:clear && php artisan cache:clear"
```

---

## Recording protocol

For each recording, in Postman:

1. Open the collection runner (*Runner → New Run → select* `POCOR-9697`).
2. Make sure the requests run **in their natural order** (00 → 01 → ... → AUDIT-5 → WAVE3-1 → ... → 10).
3. Click *Run*. Postman executes every request and shows per-request test results.
4. While the run plays, occasionally Alt-Tab to the DB pane and run the relevant query from section (A), (B), or (C) above so the audience sees the live row state.

That's the entire video. Postman captures status codes, response bodies, and test pass/fail in one continuous run; the DB pane captures the ground truth.

Estimated runtime per video: **~90 seconds** for the Postman run, plus ~60 seconds of DB-pane narration interspersed. Plan for ~3 minutes of recording.

---

## What the audience will see — wave by wave

For each wave, the table shows what happens on `master` (BEFORE) vs `POCOR-9697` (AFTER) and what DB query to run on screen.

### Wave 1a — write-side mass-assignment (items 01–07)

| # | Request | BEFORE (master) | AFTER (POCOR-9697) | DB pane shows |
|---|---|---|---|---|
| 00 | Login as admin | ✅ 200 | ✅ 200 | — |
| 01 | v4 ATTACK: mass-assign `super_admin` | 200, but new row has `super_admin=1` in DB | 200, new row has `super_admin=0`. Response body contains no mention of `super_admin` (anti-fingerprinting). Server log line `POCOR-9697: super_admin field detected ... silently stripped`. | Run query (A) — BEFORE shows `super_admin=1`, AFTER shows `0` |
| 02 | v4 ATTACK: elevate existing user via id (target `id=3`) | 200, `id=3.super_admin` flipped to 1 — silent privilege escalation of a regular user | 200, `id=3.super_admin` still 0 — silently stripped | Run query (B) — BEFORE shows `super_admin=1`, AFTER shows `0` |
| 03 | v5 ATTACK: mass-assign `super_admin` | 201, DB `super_admin=1` | 201, DB `super_admin=0` | Run query (A) |
| 04 | Login as new user | ❌ master stores password as plaintext, login fails | ✅ master never hashed → AFTER bcrypt-hashes, login succeeds | — |
| 05 | `/permissions` for new user | shows `super_admin=1` (escalation complete) | shows `super_admin=0` | — |

### Wave 1b — response & schema leaks (items 06–09)

| # | Request | BEFORE (master) | AFTER (POCOR-9697) |
|---|---|---|---|
| 06 | `GET /api/v4/users/{id}` | Response leaks `super_admin` + `password` keys | Both keys hidden by `$hidden` |
| 07 | `POST /api/v4/users/basic-information` | Rows leak `super_admin` | Hidden |
| 08 | `/api-docs-v4.json` super_admin hits | 6 hits | ≤1 (only description) |
| 09 | `/api-docs-v5.json` super_admin hits | 3 hits | 0 |

No DB queries needed here — Postman shows the JSON response inline. Pause and scroll the body so the audience sees the field is gone on AFTER.

### Wave 1c — read-side enumeration via `_conditions` (items x09a–x09d)

This is the subtlest defence: a low-priv account could previously enumerate super_admins (or do a binary-search oracle on the password hash) by passing `?_conditions=super_admin:1` or `password:>$2y$` on the `/api/v5/security-users` listing.

POCOR-9697 closes this with a per-model `_conditions` allowlist (`$fillable - $hidden`). Any non-allowlist field — whether a sensitive column (`super_admin`, `password`) or a typo (`hubabuba`) — returns a **400 Bad Request** with a generic body. The body is **byte-identical for all three cases**, so an attacker cannot A/B test which fields are sensitive vs simply nonexistent. The server log distinguishes them: `WARNING: SENSITIVE filter probe — possible enumeration attempt` for credential/escalation columns (with caller IP captured), `INFO: filter dropped` for plain typos.

| # | Request | BEFORE (master) | AFTER (POCOR-9697) | Server log entry |
|---|---|---|---|---|
| x09a | `_conditions=super_admin:1` | Returns ONLY super_admins (e.g. 3 rows) — full enumeration | **400 generic body** | `WARNING: SENSITIVE filter probe — possible enumeration attempt` |
| x09b | `_conditions=hubabuba:babble` (typo) | (master would silently apply or 500) | **400 generic body** — byte-identical to x09a | `INFO: filter dropped — field not queryable` |
| x09c | `_conditions=password:>$2y$` (oracle) | Returns rows matching the prefix — leaks hash one byte at a time | **400 generic body** | `WARNING: SENSITIVE filter probe` |
| x09d | `_conditions=username:admin` (legit `$fillable` column) | filters normally | **200**, filters normally — proves the allowlist isn't a blanket block | (no log line — request was valid) |

DB pane: not required for this wave. Show two things on screen:
1. Postman *Tests* tab: x09a / x09b / x09c all green with status 400, x09d green with status 200.
2. The Laravel log (`tail -f api/storage/logs/laravel-2026-05-12.log | grep POCOR-9697`) — the SENSITIVE-probe lines pop up for x09a/x09c with IP captured; x09b shows the plain `filter dropped` entry.

Narration cue: *"Same 400 for the attack and the typo — that's deliberate. An attacker can't tell from the response whether `super_admin` is a real column or not. But ops can: the server log escalates credential-bearing probes for SOC alerting."*

### Wave 2 — audit-trail forgery (items AUDIT-1..AUDIT-5)

The previous v5 code auto-filled `created_user_id` / `modified_user_id` only when the client *omitted* them. A caller could send `{"created_user_id": 2}` and forge admin attribution on any audit-trail record. v4 was already correct; v5 was not.

| # | Request | BEFORE (master) | AFTER (POCOR-9697) | DB shows |
|---|---|---|---|---|
| AUDIT-1 | v5 create with forged `created_user_id=99999` | DB row has `created_user_id=99999` | DB row has `created_user_id=<JWT user id, e.g. 2>` — server overwrites silently and logs the attempt | Query (A): `created_user_id` column |
| AUDIT-2 | v5 update with forged `modified_user_id=99999` | DB row has `modified_user_id=99999` | DB row has `modified_user_id=<JWT user id>` | Query (A) |
| AUDIT-3 | Re-read the user via GET | Shows the forged values from the forger | Shows JWT user as both audit fields | — |
| AUDIT-4 | v5 update with forged `created_user_id` | `created_user_id` overwritten (lost) | `created_user_id` silent-stripped, original value preserved (immutable) | Query (A) — original `created_user_id` intact |
| AUDIT-5 | Re-read confirms AUDIT-4 | Confirms the forgery | Confirms the immutability | — |

### Wave 3 — `user_activities` audit log (items WAVE3-1..WAVE3-4)

The CakePHP side has always written to `user_activities` when users are created or changed. The Laravel API didn't — every API-originated change was invisible in the audit dashboard. Wave 3 closes that gap via a `UserActivityLog` Eloquent trait.

| # | Request | BEFORE (master) | AFTER (POCOR-9697) | DB shows |
|---|---|---|---|---|
| WAVE3-1 | v5 create fresh user, capture `demo_user_id` | New row in `security_users` only | New row in `security_users` AND one row in `user_activities` with `operation='created'` | Run query (C) — should see one row labelled `created` |
| WAVE3-2 | `GET /api/v5/user-activities?_conditions=model_reference:<id>` | Empty | Returns the create row | Postman body matches DB query (C) |
| WAVE3-3 | PUT changes `first_name` + `email` | No audit rows | One audit row PER dirty field — two `updated` rows: one for `first_name`, one for `email`, each with old_value/new_value | Run query (C) again — three rows total now |
| WAVE3-4 | Re-GET activities, assert 3+ rows for the user | — | Assertion passes | Query (C) shows: 1 `created` + 2 `updated` |

**Important detail** — `password` and `super_admin` changes ARE audited (so the operator sees the field changed), but the trait forces `old_value` / `new_value` to `[REDACTED]`. We never persist credentials into a varchar audit column. If you want, demo this on camera with one extra PUT changing `password`: the row appears, but the values are redacted.

---

## Narration prompts

Four prompts — one per wave. Read them slowly, give the runner a few seconds between each, and pause on the DB pane queries.

**Wave 1 opening (before clicking *Run*):**
> "This is OpenEMIS Core. The same collection runs on master and on POCOR-9697. The HTTP responses look almost identical — the difference is in the database, in the logs, and in what an attacker can read back."

**Wave 2 mid-roll (when AUDIT-1 starts):**
> "Watch the `created_user_id` column. The attacker tries to forge it. On master, the DB now thinks user 99999 created this row. On POCOR-9697, the same request returns success, but the JWT-authenticated user is what actually gets stored. The attacker can't repaint history."

**Wave 3 mid-roll (when WAVE3-1 starts):**
> "Every change is now mirrored into `user_activities` — the same table the CakePHP audit dashboard reads. API changes finally show up alongside UI changes. Credentials are redacted in the log but the fact-of-change is recorded."

**Closing (AFTER video only):**
> "Same collection, same requests, branch POCOR-9697 deployed. Every defence assertion passed, the DB never accepted an escalation, and every user change is now in the audit log."

End the AFTER video by opening request 05 (`/permissions`) Tests tab — `super_admin = 0` is the one-line proof — then switching to the DB pane and running query (C) one last time so the closing frame is the audit-log table.

---

## Re-running

The collection seeds a unique `exploit_username` and `demo_username` per run (uses `Date.now()`), so you can re-run safely without uniqueness errors. If anything gets stuck:

```sql
DELETE FROM user_activities WHERE model = 'SecurityUsers' AND model_reference IN
  (SELECT id FROM security_users WHERE username LIKE 'exploit_%' OR username LIKE 'demo_%');
DELETE FROM security_users WHERE username LIKE 'exploit_%' OR username LIKE 'demo_%';
```

---

## Files

- `POCOR-9697.postman_collection.json` — the collection (25 requests including 4 waves of tests).
- `POSTMAN-RECORDING-GUIDE.md` — this file.
- `../POCOR-9697-README.md` — full release notes describing each wave at the code level.

All three ship with the branch so the reviewer can follow along.
