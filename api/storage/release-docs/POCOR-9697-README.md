# POCOR-9697 — Prevent super_admin Privilege Escalation

## 1. What is the Task?

Close a critical privilege-escalation vulnerability in the OpenEMIS Core API.
Before this ticket, **any authenticated API caller** — including a low-priv
teacher, student, or guardian account — could grant themselves (or anyone
else) full `super_admin` privileges by sending a single HTTP request with
`super_admin: 1` in the body to `/api/v4/users` or `/api/v5/security-users`.
`super_admin = 1` bypasses every permission check in
`PermissionService::checkPermission` (line 90), so this was a one-request
path from a Telegram-tier account to god-mode on the entire instance.

## 2. Situation Before

| Layer | What was wrong |
|---|---|
| Models | `super_admin` was in `$fillable` on both `App\Models\SecurityUsers` and `App\Models\Api5\SecurityUsers`. v5 `CrudApiController::create/update` mass-assigned `$request->all()`, so any caller with the `SecurityUsers add/edit` permission could mint a super_admin. |
| v4 repository | `UserRepository::setUserData` copied `super_admin` from the request body verbatim into the insert payload. `UserRepository::addUsers` update branch did `update($request->all())` with no filtering. |
| v4 form request | `UsersAddRequest::rules()` validated only `first_name`, `last_name`, `gender_id`, `date_of_birth`. `super_admin` was not restricted. |
| v4 controller | `UserController::addUsers`, `saveStudentData`, `saveStaffData`, `saveGuardianData` relied on `auth.jwt` middleware only. There was no per-action permission check. |
| Password storage | `UserRepository::setUserData` wrote `$params['password']` raw into the model. Rows created via this path stored plaintext in `security_users.password`. |
| Information disclosure | `super_admin` was leaked in API responses (`GET /api/v4/users/{id}`, `POST /api/v4/users/basic-information`, model serialisations from v5 CRUD). The swagger JSONs published at `/api-docs-v4.json` and `/api-docs-v5.json` advertised the field with `"example": 1`, effectively documenting the exploit for attackers. |

A separate analysis estimated the time-to-exploit for an LLM-driven attacker
at **2–10 minutes** given the public swagger and any valid login.

## 3. What Was Implemented

The fix is layered defence-in-depth — three independent layers that each
prevent the escalation on their own.

### Issue 1 — Mass-assignment stripped

`super_admin` removed from `$fillable` on both SecurityUsers models, so
Eloquent silently drops it from any `$request->all()` payload. Also added to
`$hidden` so it is never serialised back in JSON responses.

### Issue 2 — Explicit filtering on v4 write paths

* `UserRepository::setUserData` — `super_admin` line deleted; passwords now
  routed through a new `hashPasswordIfPlaintext()` helper that bcrypt-hashes
  cleartext values and passes existing `$2[aby]$` hashes through unchanged.
* `UserRepository::addUsers` update branch — `unset(super_admin, id,
  created_user_id, created)` before the mass `update($data)`. Password also
  re-hashed if a new plaintext value was sent.
* `UsersAddRequest::prepareForValidation()` — silently strips `super_admin`
  from the request body and writes a `Log::warning('POCOR-9697: super_admin
  field detected …')` line to `storage/logs/laravel-YYYY-MM-DD.log` so ops
  has telemetry on every attempt. The response is the ordinary `200 success`
  shape with no mention of the field. Why not a `422 prohibited` rule? A
  named-field 422 would fingerprint the API — an attacker probing
  escalation vectors gets confirmation the field exists and is meaningful,
  making the next exploit easier. Silent strip + server-side log gives ops
  full visibility without giving the attacker any signal.

### Issue 3 — Permission gate on v4 user-write endpoints

`UserController` constructor now injects `PermissionService`.
`addUsers`, `saveStudentData`, `saveStaffData`, `saveGuardianData` all gate
on `checkPermission('SecurityUsers', 'add'|'edit')` and return 403 on
failure. Same gate that v5 `CrudApiController` already enforces.

### Issue 4 — Stop leaking super_admin and password in responses

* Both SecurityUsers models — added `super_admin` to `$hidden`.
* `UserService::getUsersData` — removed `password` and `super_admin` from
  the manually-built v4 `GET /users/{id}` response array.
* `DirectoryRepository::getUserByBasicInfo` — removed `super_admin` from
  the manually-built `POST /users/basic-information` response rows.
* Stripped stale `password` and `super_admin` `@OA\Property` annotations
  from response schemas in AttendanceController, DirectoryController,
  MealController, WorkbenchController, UserController, and the v5
  SecurityUsers model. **Request-body** annotations were left untouched
  (legitimate input). `GET /api/v4/users/generate-password` and
  `GET /api/v4/permissions` (caller's own self-introspection) also kept
  intentionally.
* Regenerated the public swagger JSONs (`api/public/api-docs-v[45].json`)
  via `php artisan l5-swagger:generate`. After regeneration the only
  remaining `super_admin` reference is in `GET /api/v4/permissions`
  response schema (legitimate, frontend depends on it).

### Issue 5 — Password hashing

Both SecurityUsers models now have a `setPasswordAttribute()` Eloquent
mutator that bcrypt-hashes any plaintext value on assignment. The mutator
is idempotent — values starting with `$2a$`, `$2b$`, or `$2y$` pass through
unchanged so seeders, imports, and re-saves of an already-loaded row stay
safe.

For v4 paths that use the query builder (`SecurityUsers::insert()` /
`SecurityUsers::update()`, which bypass Eloquent mutators), the same
guarantee is provided by `UserRepository::hashPasswordIfPlaintext()`.

### Issue 6 — Read-side `_conditions` filter column allowlist

Even after Issues 1–5 closed the write side and stripped `super_admin` /
`password` from response bodies, the v5 `_conditions` query parameter was
still accepted as a free-form `field:operator:value` triple by
`CrudApiController::parseConditions()`. The WHERE clause then executed
server-side with no column allowlist, so any authenticated v5
SecurityUsers-list user could:

1. Enumerate every super_admin: `GET /api/v5/security-users?_conditions=super_admin:1`
   returns the membership of the set — the value is hidden in the body but
   the row IDs and names leak the membership inference.
2. Run a blind binary-search oracle on `security_users.password` (the
   bcrypt hash) via inequality and `like` operators —
   `_conditions=password:>$2y$`, `password:*$2y$10$abc*`, etc.

**Fix** (`CrudApiController.php`):

* New private helper `getQueryableColumns($model)` returns the per-model
  read-side allowlist as `getFillable()` minus `getHidden()`. This mirrors
  the published write surface so the rule is internally consistent — a
  client cannot read-filter on any column they could not already write to.
* `applyFilters()` now takes the resolved model. Filter keys not present
  in the model schema cause it to throw `\InvalidArgumentException`; the
  caller (`handleGetRequest`) translates that to **HTTP 400** with a fixed
  generic body: `"Request contains filter field names that are not present
  in this resource. Check API documentation for the fields of this
  resource."` Wording matches the Swagger/OpenAPI vocabulary — the docs
  describe field *presence*, not an *allow list*, so the error sends
  callers to the same mental model the docs use. The rejected field name
  is **never** echoed (anti-fingerprinting); three cases (`super_admin`,
  `password`, typo `hubabuba`) produce byte-identical responses, so the
  attacker cannot A/B test which keys are sensitive vs simply nonexistent.
* The earlier silent-drop iteration was a defensive overcorrection — it
  also swallowed honest client typos (e.g. `studnet_id` instead of
  `student_id`) and returned the wrong dataset with no warning. The 400
  surfaces those typos to legitimate clients while preserving
  anti-fingerprinting for sensitive fields.
* Server-side logging is **escalated for credential-bearing columns**.
  A class constant `SENSITIVE_FILTER_FIELDS = ['super_admin', 'password',
  'remember_token', 'password_hash']` controls the split:
  * Probes against any sensitive field log at `Log::warning` with the
    message `"POCOR-9697: SENSITIVE filter probe — possible enumeration
    attempt"`, the dropped fields, and the caller IP — SOC tooling can
    alert on this prefix.
  * Plain non-allowlist fields (typos, deprecated columns) log at
    `Log::info` with `"POCOR-9697: filter dropped — field not queryable"`.

Live PoC against the patched container:

```
# Sensitive probe — 400 generic, no field name in body, SOC log entry created
curl -k -H "Authorization: Bearer $TOKEN" '…/api/v5/security-users?_conditions=super_admin:1&limit=1'
# → {"error":"Request contains filter field names that are not present in this resource. Check API documentation for the fields of this resource."}

# Typo — same 400, same body (anti-fingerprinting)
curl -k -H "Authorization: Bearer $TOKEN" '…/api/v5/security-users?_conditions=hubabuba:babble&limit=1'
# → identical body to the super_admin probe

# Legitimate $fillable column still filters
curl -k -H "Authorization: Bearer $TOKEN" '…/api/v5/security-users?_conditions=username:admin&limit=5' | jq '.data.total'  # → 1
```

**Tests** (`SuperAdminEscalationProtectionTest.php`, 5 cases):

* `test_v5_conditions_filter_rejects_hidden_super_admin` — status 400, no
  `super_admin` in body.
* `test_v5_conditions_filter_rejects_password_oracle` — status 400, no
  `password` in body.
* `test_v5_conditions_filter_rejects_typo_field` — `hubabuba` rejected
  with the same 400 and same body shape (anti-fingerprinting cross-check).
* `test_v5_conditions_filter_allows_legitimate_field` — `username:admin`
  still narrows; sanity check that we did not break existing clients.
* `test_v5_conditions_rejected_field_no_named_response_leak` — response
  body must not contain `super_admin`, `"password"`, `unknown column`.

**Postman** (`POCOR-9697.postman_collection.json`, items x09a–x09d):
* `x09a` — `_conditions=super_admin:1` — asserts 400 + no field name.
* `x09b` — `_conditions=hubabuba:babble` — asserts 400 + no field name
  (repurposed from "baseline"; now the anti-fingerprinting comparison
  partner for x09a).
* `x09c` — `_conditions=password:>$2y$` — asserts 400 + no field name.
* `x09d` — `_conditions=username:admin` — asserts 200 + result narrows.

### Issue 7 — Audit-trail integrity: `created_user_id` / `modified_user_id` always from JWT

**Threat model.** Distinct from privilege escalation: an authenticated caller
who knows a target user id could forge audit-trail attribution by sending
`{"created_user_id": <victim_id>}` on any v5 write. Every downstream auditor
(`security_user_logins`, workflow comments, webhook events, history tables…)
would then misattribute the action. This is the *audit-trail forgery* vector
— independent of `super_admin` membership but exploitable from the same
write surface.

**Situation before.** `CrudApiController` filled the audit fields only when
the request omitted them:

```php
// handleSingleCreate (old)
if (!isset($data['created_user_id'])) {
    $data['created_user_id'] = $current_user_id;
}
```

So a body containing `created_user_id: 2` overrode the JWT user wholesale.
v4 was already correct (`UserRepository::setUserData` line 1779 and the
update path at line 1711 both stamp from `JWTAuth::user()->id` unconditionally
and never copy the request value — re-verified in this branch across ~9 call
sites). v5 was the gap.

**Fix** (`CrudApiController.php`):

* New private helper `stampAuditFieldsOnCreate(array $data, array $fillable,
  $currentUserId)` — for any model whose `$fillable` includes
  `created_user_id` and/or `modified_user_id`, the field is **always**
  overwritten with the JWT user. Any client-supplied value that differs is
  logged via `Log::warning('POCOR-9697: created_user_id forgery attempt —
  overwritten with JWT user', …)` *before* the overwrite, so ops can grep
  forgery attempts in production.
* `handleSingleCreate` and `handleBatchCreate` both call the helper.
* `handleUpdateRequest` unconditionally overwrites `modified_user_id` and
  silently strips `created_user_id` (immutable on update) — also logged.
* Response never echoes the field name in any error/diagnostic capacity
  (anti-fingerprinting, matches the Wave-1 super_admin strip pattern).

**v4 path.** Re-verified — `UserRepository::setUserData` line 1779
(`$userArr['created_user_id'] = JWTAuth::user()->id;`), `addUsers` update
branch line 1711 (`$data['modified_user_id'] = JWTAuth::user()->id;`), and
the ~9 other write sites all derive from the JWT user. No code changes
needed in v4. The new test `test_v4_create_overwrites_forged_created_user_id`
pins the contract.

**Tests** (`SuperAdminEscalationProtectionTest.php`, +5 cases):

* `test_v5_create_overwrites_forged_created_user_id`
* `test_v5_update_overwrites_forged_modified_user_id`
* `test_v5_update_silently_ignores_created_user_id`
* `test_v5_create_no_field_fingerprint_in_response`
* `test_v4_create_overwrites_forged_created_user_id`

All 17 cases in the regression file pass (12 Wave-1 + 5 Wave-2).

**Postman** (`POCOR-9697.postman_collection.json`, items `AUDIT-1`…`AUDIT-5`):
forge `created_user_id` / `modified_user_id` on v5 create, forge
`modified_user_id` on update, attempt to mutate `created_user_id` on update,
plus follow-up GETs that re-read the row to assert the JWT user landed (and
the forged 99999 did not). Each request ships with `pm.test()` assertions
mirroring the feature tests.

### Files Changed Summary

* **Added:** 2 files
* **Modified:** 13 files
* **Removed:** 0 files

| File | Change |
|---|---|
| `api/app/Models/SecurityUsers.php` | `super_admin` removed from `$fillable`, added to `$hidden`; idempotent `setPasswordAttribute()` mutator. |
| `api/app/Models/Api5/SecurityUsers.php` | Same as above + stripped `super_admin` and stale `password` from the three swagger blocks. |
| `api/app/Repositories/UserRepository.php` | `addUsers` update path unsets `super_admin`/`id`/`created_*` and hashes any new password; `setUserData` no longer copies `super_admin`; new `hashPasswordIfPlaintext()` helper. |
| `api/app/Repositories/DirectoryRepository.php` | `getUserByBasicInfo` no longer puts `super_admin` into each result row. |
| `api/app/Http/Requests/UsersAddRequest.php` | `prepareForValidation()` silently strips `super_admin` and logs the attempt server-side. No 422, no field-name fingerprint in the response. |
| `api/app/Http/Controllers/UserController.php` | `PermissionService` DI; gate on `addUsers`/`saveStudentData`/`saveStaffData`/`saveGuardianData`; stale `password` swagger response examples stripped. |
| `api/app/Http/Controllers/AttendanceController.php` | Stale `super_admin` and `password` swagger response entries stripped. |
| `api/app/Http/Controllers/DirectoryController.php` | Stale `super_admin` and `password` swagger response entries stripped. |
| `api/app/Http/Controllers/MealController.php` | Stale `super_admin` and `password` swagger response entries stripped. |
| `api/app/Http/Controllers/WorkbenchController.php` | 32 stale `password` swagger response entries + `super_admin` stripped. |
| `api/app/Services/UserService.php` | `getUsersData` no longer puts `password` or `super_admin` into the v4 `GET /users/{id}` response. |
| `api/public/api-docs-v4.json` | Regenerated. Drops 5 of 6 `super_admin` references; only `/api/v4/permissions` self-introspection remains. |
| `api/public/api-docs-v5.json` | Regenerated. All 3 `super_admin` references gone. |
| `api/app/Http/Controllers/BaseApi/CrudApiController.php` | **POCOR-9697 (Issue 6).** New `getQueryableColumns()` helper + `applyFilters()` takes the resolved model and **rejects with HTTP 400** any filter key not in `$fillable - $hidden`. Generic body, no field name echo (anti-fingerprinting). Sensitive-field probes (`super_admin`, `password`, `remember_token`, `password_hash`) logged at `Log::warning` with caller IP for SOC alerting; plain typos at `Log::info`. Closes read-side enumeration / oracle holes via `_conditions` while surfacing typos to legit clients. |
| `api/tests/Feature/SuperAdminEscalationProtectionTest.php` | **New.** 23 feature tests covering every layer of the fix (write-side strip, response leak, swagger leak, read-side `_conditions` 400-on-rejection, audit-trail forgery, Wave-3 user_activities create/edit, **plus the per-field delete-snapshot logic, exercised directly because hard-delete itself is schema-blocked — see below**). All pass. |
| `src/Model/Behavior/TrackActivityBehavior.php` | **CakePHP-side parity.** New `_redact = ['password', 'super_admin', 'photo_content']` list emits `[REDACTED]` rows that previously were silently dropped under `_excludeType='binary'`. `afterDelete` keeps the summary row and adds a per-field snapshot loop (dormant infrastructure — see schema note below). Header docblock now describes the parity contract with the Laravel trait. |

### Database Migrations

**None.** No schema change ships on this branch — a deliberate design choice, not an oversight.

#### Hard-delete is schema-blocked (existing OpenEMIS behaviour)

`user_activities.security_user_id` has a FK to `security_users.id` with `ON DELETE RESTRICT`. Any single `create` / `edit` audit row blocks the hard-delete of that user — and since this trait writes one on every create / edit, users who have ever been touched are un-deletable at the schema level. In practice OpenEMIS soft-deletes users via the `status` column; the production `user_activities` table holds **zero** `delete` rows over years of operation, confirming this is the supported norm.

#### Delete-snapshot ships as dormant infrastructure

The per-field delete-snapshot code (`UserActivityLog::logDeleteSnapshot` on the Laravel side, the `afterDelete` snapshot loop in `TrackActivityBehavior` on the CakePHP side) is fully implemented and tested. Under the current FK it sits dormant for `security_users`:

* If the `deleted` event somehow fires (e.g. an admin manually clears the user's audit rows to defeat RESTRICT), the snapshot `INSERT`s will fail with a FK violation against the now-vanished parent. The trait's `try/catch` and the CakePHP `Log::write('debug', getErrors())` swallow that failure without breaking the request.
* On any other table that adopts the trait without a RESTRICT-ed FK, the snapshot fires normally.

The Laravel test exercises the snapshot via direct invocation (`$user->logUserActivity('delete')`) — same code path the Eloquent `deleted` event would fire — so the row shape is provable today (23 / 23 passing).

#### What a future ticket would need

To activate the snapshot for `security_users`:

1. Migration relaxing the FK to `ON DELETE SET NULL`:
   ```sql
   ALTER TABLE user_activities MODIFY security_user_id INT NULL;
   ALTER TABLE user_activities DROP FOREIGN KEY user_activ_fk_secur_user_id;
   ALTER TABLE user_activities
     ADD CONSTRAINT user_activ_fk_secur_user_id
     FOREIGN KEY (security_user_id) REFERENCES security_users(id)
     ON DELETE SET NULL ON UPDATE RESTRICT;
   ```
2. Flip Eloquent event from `static::deleted` → `static::deleting` (so snapshot `INSERT`s land while parent still exists).
3. Flip CakePHP `afterDelete` → `beforeDelete` for the same reason.
4. Update the dashboard query for "user history" to use `WHERE model='Users' AND model_reference=X` (immutable id) rather than the FK column, since `security_user_id` will be SET NULL for deleted users.

This was prototyped during POCOR-9697 review, reverted as out-of-scope (weakens referential integrity on a previously NOT NULL column; the existing "users with history can't be hard-deleted" rule is the accepted OpenEMIS norm). The dormant snapshot code stays so this future ticket reduces to the four steps above with no application logic to write.

## 3a. Integration / Merge Notes

POCOR-9697 touches `api/app/Http/Controllers/BaseApi/CrudApiController.php` and `api/app/Models/Api5/SecurityUsers.php`. Two sibling branches edit those same files. The detailed merge plan with conflict-region dumps, paste-ready resolution snippets, regression-signal smoke tests, and recommended merge order ships alongside this README at **`api/storage/release-docs/POCOR-9697/merge-plan.md`**.

Quick matrix (verified by dry-run merge against `origin/POCOR-9697@892af27276`):

| Target | Result | Conflict file | Severity |
|---|---|---|---|
| POCOR-9697 ↔ **POCOR-9660** | 1 conflict | `CrudApiController::handleGetRequest` | Medium — combine both try/catch wrappers; **must pass `$model` to `applyFilters`** or Wave 1c silently no-ops |
| POCOR-9697 ↔ **tst-5.10.0** | Clean | — | None |
| POCOR-9697 ↔ **tst-5.10.0-1** | 1 conflict | `Api5/SecurityUsers.php` (`$fillable` + Swagger ×3) | **High — security regression risk.** Resolution rule: never accept their side on `super_admin`, always accept their side on `sync_status` (POCOR-9590) |

Recommended merge order: **POCOR-9660 first** (smaller surface), then POCOR-9697 on top. Post-merge regression smoke:

```bash
# Wave 1c must still 400 with generic body
curl -ks -w '\nStatus: %{http_code}\n' -H "Authorization: Bearer $TOKEN" \
  "https://localhost:8482/core/api/v5/security-users?_conditions=super_admin:1"
# Expected: 400 + "...not present in this resource..."
# REGRESSION: 200 + rows → `$model` was dropped from applyFilters during the merge

# POCOR-9660 implicit id-list must still work
curl -ks -H "Authorization: Bearer $TOKEN" \
  "https://localhost:8482/core/api/v5/academic-periods/1,2,3" | jq '.data | length'
# Expected: 3

# 22-test suite must still pass
cd api && php artisan test --filter=SuperAdminEscalationProtectionTest
```

## 4. Deployment Instructions

This is a hot-fix; deploy ASAP on any instance that exposes the public swagger.

```bash
# 1. Pull the branch
git pull origin POCOR-9697     # or merge POCOR-9697 -> master then pull master

# 2. Inside the application container — clear Laravel caches
docker exec poe-application sh -c \
  "cd /var/www/html/emis/core/api && \
   php artisan config:clear && \
   php artisan route:clear && \
   php artisan cache:clear"

# 3. CRITICAL — regenerate the swagger JSONs and copy to public/.
#    Skipping this step leaves the OLD JSON files in place, which
#    still publish 'super_admin: example=1' to anyone hitting
#    /api-docs-v4.json or /api-docs-v5.json.
docker exec poe-application sh -c \
  "cd /var/www/html/emis/core/api && \
   php artisan l5-swagger:generate v4 && \
   php artisan l5-swagger:generate v5 && \
   cp storage/api-docs/api-docs-v4.json public/api-docs-v4.json && \
   cp storage/api-docs/api-docs-v5.json public/api-docs-v5.json"

# 4. Sanity check — the only remaining super_admin reference should be
#    GET /api/v4/permissions (self-introspection). v5 must show 0.
grep -c '"super_admin"' \
  api/storage/api-docs/api-docs-v[45].json \
  api/public/api-docs-v[45].json
# Expected output:
#   api/storage/api-docs/api-docs-v4.json:1
#   api/storage/api-docs/api-docs-v5.json:0
#   api/public/api-docs-v4.json:1
#   api/public/api-docs-v5.json:0

# 5. Run the regression test
docker exec poe-application sh -c \
  "cd /var/www/html/emis/core/api && \
   php artisan test --filter=SuperAdminEscalationProtectionTest"
# Expected: 8 passed.
```

## 5. System Administrator Guide

### Audit existing super admins

Run on every instance immediately after deploying the fix. Pre-fix exploits
left no log; the only forensic trail is the row itself.

```sql
SELECT id, openemis_no, username, first_name, last_name,
       created, created_user_id, modified, modified_user_id
FROM security_users
WHERE super_admin = 1
ORDER BY created DESC;
```

Investigate any row that was not created by a known operator. Rotate
credentials on any account suspected of being created via exploit.

### Public swagger sanity check (post-deploy)

```bash
curl -sk https://<host>/api-docs-v4.json | grep -c '"super_admin"'
# expected: 1   (the /api/v4/permissions self-introspection — see below)

curl -sk https://<host>/api-docs-v5.json | grep -c '"super_admin"'
# expected: 0
```

If either count is higher, step 3 above was skipped during deploy.

### Why one `super_admin` reference remains in v4 swagger

`GET /api/v4/permissions` returns the **caller's own** `super_admin` flag.
The frontend reads this to decide whether to show admin-only UI. It is
listed in the **response schema only**, not in any request body, and the
endpoint cannot be used to set the flag. This is intentional.

A follow-up ticket may rename the response field to `isSuperAdmin: bool`
to stop using the raw column name in public docs, but that is a cosmetic
change with no security impact.

### Known follow-ups (not in this ticket)

1. `api/app/Models/Api5/DataManagementConnections.php` — the `password`
   field on this model is for **external DB connection credentials**,
   unrelated to user auth. The pattern (manually documenting password in
   a list-response swagger) is the same as we just fixed for SecurityUsers,
   but the data class and threat model are different. Separate ticket.
2. `plugins/Institution/webroot/js/angular/comments/institutions.comments.svc.js`
   (8 occurrences) and `plugins/Institution/.../students.svc.js` (line 324)
   pass `super_admin` as a query parameter to CakePHP `/restful/v2/`. v2
   does not write `security_users.super_admin` from this path, so it is
   not an escalation vector, but the field name is visible in the JS
   bundle. Lower priority.


---

## Wave 3 — User-change audit log (CakePHP / Laravel parity)

### What changed

Every API write to `security_users` — v4 *and* v5 — now produces a row in
the existing `user_activities` table, the same table the CakePHP backend
has always written to. The dashboard at **User → Activities** therefore
shows API-originated changes alongside UI-originated ones, with no
schema change required.

Two implementation halves, kept in lockstep:

| Stack | File | Role |
|---|---|---|
| Laravel | `api/app/Models/Concerns/UserActivityLog.php` | Trait mixed into both `App\Models\SecurityUsers` and `App\Models\Api5\SecurityUsers`. Wires Eloquent `created` / `updated` / `deleted` events; exposes a static `logExternalUserChange()` for v4 query-builder paths. |
| CakePHP | `src/Model/Behavior/TrackActivityBehavior.php` | Pre-existing behavior attached to `StudentUser`, `StaffUser`, `Profiles`, `Directories`, `Staff`. Now updated to match the new shared row contract verbatim. |

Both stacks read from a single docblock contract — change one, update the other. The header docblocks reference each other.

### Audit row shape (matches CakePHP `TrackActivityBehavior` verbatim)

| Operation | Rows | `field` | `field_type` | `old_value` / `new_value` | `operation` |
|---|---|---|---|---|---|
| edit | one per dirty field | column name | column type (`string`/`integer`/`decimal`/…) | real values, truncated to 252+`...` if >255 chars | `'edit'` |
| delete (summary) | 1 | `''` | `''` | `''` / `''` | `'delete'` |
| delete (per-field snapshot — see §"Delete snapshot" below) | N | column name | column type | previous value (or redaction placeholder) | `''` | `'delete'` |
| create | 1 | `''` | `''` | `''` / `''` | `'create'` |

`operation` value matches the Cake enum exactly — `'edit'` (not `'update'`), `'create'` (not `'add'`), `'delete'`. `field=''` / `field_type=''` / empty-string values on the summary rows match CakePHP `TrackActivityBehavior::afterDelete` byte-for-byte. Production DB confirms only these values appear (`SELECT operation, COUNT(*) FROM user_activities GROUP BY operation` returns `edit` and `create` only).

Constant columns: `model='Users'`, `model_reference=security_users.id`, `security_user_id=security_users.id`, `created_user_id=JWT caller id (0 for CLI / queue / seed)`, `created=now()`.

### Redaction policy — same list on both stacks

| Field | On `edit` | On `delete` snapshot |
|---|---|---|
| `password` | `'[REDACTED]'` | `'[REDACTED]'` |
| `super_admin` | `'[REDACTED]'` | `'[REDACTED]'` |
| `photo_content` (longblob) | `'[REDACTED]'` | `'[...]'` |
| any non-redacted `binary` column | row skipped (`$_excludeType` on Cake, not in `$fillable` on Laravel) | row skipped |
| everything else | real value, truncated to 252+`...` if >255 chars | same |

`[REDACTED]` means "value omitted for security" (password, super_admin); `[...]` means "value omitted for size / format" (longblob photo). The two markers are deliberately distinct so a forensic reader can tell *why* a value isn't there. The list is parity-mirrored: Laravel `userActivityRedactedFields()` and CakePHP `$_redact` both return `['password', 'super_admin', 'photo_content']`.

Reason `password` and `super_admin` are redacted: `user_activities.old_value` / `new_value` are `varchar(255)`, so would otherwise persist either plaintext input or a bcrypt hash — both unacceptable. Same threat model as the Wave-1 `$hidden` rules. Reason `photo_content` is redacted: it's a `longblob`, never fits into 255 chars and is binary garbage anyway.

### Delete snapshot — ships, but dormant for `security_users`

The trait additionally emits, *on delete*, a per-field snapshot row for every column of the deleted entity so a forensic / undelete code path can reconstruct who the user was. See the "Database Migrations" section above for the schema constraint that keeps this dormant for `security_users` today and the four-step recipe to activate it on a future ticket.

### Coverage

- **v5 CRUD (`/api/v5/security-users`)** — covered by the trait's Eloquent model events.
- **v4 create / update (`POST /api/v4/users`)** — `UserRepository::addUsers` uses query-builder `insert()` / `update()`, which bypass Eloquent events. We close that gap by calling `SecurityUsers::logExternalUserChange()` explicitly after each write, with the diff computed against the pre-update row.
- **CakePHP UI flow (Students, Staff, Directories, Profiles)** — covered by the pre-existing `TrackActivityBehavior` (now updated to write the new row shape).

### How to read the trail via the API

```bash
# After creating user id=14693 via /api/v5/security-users:
curl -k -H "Authorization: Bearer $TOKEN" \
  "https://localhost:8482/core/api/v5/user-activities?_conditions=security_user_id:14693"
```

Returns the create summary row plus one row per dirty field on every update. For users that have been deleted under a future SET NULL FK, query by `model_reference` instead of `security_user_id` — see the migration section.

### Regression tests

`api/tests/Feature/SuperAdminEscalationProtectionTest.php` — Wave-3 tests:

- `test_v5_create_user_logs_audit_row`
- `test_v5_update_user_logs_audit_row_per_dirty_field`
- `test_v5_update_user_password_change_logs_event_without_value` (asserts `'[REDACTED]'`)
- `test_v4_create_user_logs_audit_row`
- `test_delete_user_writes_per_field_snapshot` (exercises the dormant delete-snapshot via direct `logUserActivity('delete')` invocation since the FK blocks the DB delete itself)

Full suite: **23 / 23 passing.**

### Postman section

Items **WAVE3-1 … WAVE3-4** in `POCOR-9697.postman_collection.json` demonstrate the create → GET-activities → update → GET-activities flow and assert the same redaction guarantees as the PHP tests.

### Known limitation

Legacy rows created before this branch landed do not have a `user_activities` entry — the trait is forward-looking. The pingdom / seed accounts will remain unbacked by audit rows; that is the same gap the CakePHP behavior has always had.

---

## Logging policy — SUSPICIOUS-only, never normal CRUD

The branch adds 9 `Log::` sites across the Laravel app. Every single one fires *only* on a defensive condition that an SOC / ops engineer should be alertable on — legitimate authenticated CRUD writes **zero** extra Laravel log lines. The `user_activities` table records the WHAT; the Laravel log records the SUSPICIOUS.

| # | File | Level | Fires when |
|---|---|---|---|
| 1 | `api/app/Http/Requests/UsersAddRequest.php` | warning | **`super_admin` field present in request body** — silently stripped, attempt recorded |
| 2 | `api/app/Http/Controllers/UserController.php` | info | ACL denial for `SecurityUsers:{action}` |
| 3 | `api/app/Http/Controllers/BaseApi/CrudApiController.php`:881 | warning | `created_user_id` supplied on update — immutable, silently stripped |
| 4 | `api/app/Http/Controllers/BaseApi/CrudApiController.php`:895 | warning | `modified_user_id` ≠ JWT user — forgery attempt overwritten |
| 5 | `api/app/Http/Controllers/BaseApi/CrudApiController.php`:1307 | warning | **Filter probe against sensitive column** (`password`, `super_admin`, `remember_token`, `password_hash`) — enumeration / oracle attempt |
| 6 | `api/app/Http/Controllers/BaseApi/CrudApiController.php`:1315 | info | Filter dropped — field not queryable (client typo) |
| 7 | `api/app/Http/Controllers/BaseApi/CrudApiController.php`:1583 | warning | `created_user_id` / `modified_user_id` forgery on create |
| 8 | `api/app/Models/Concerns/UserActivityLog.php`:174 | warning | Audit-row INSERT itself failed — defensive fallback inside `logUserActivity()` |
| 9 | `api/app/Models/Concerns/UserActivityLog.php`:404 | warning | Same fallback inside the static `logExternalUserChange()` helper |

Rows 1 and 5 are the direct password / super_admin tampering signals; rows 3 / 4 / 7 cover audit-field forgery; rows 8 / 9 ensure we don't silently lose the audit trail itself.

A matching policy paragraph is documented in the trait header docblock and in `src/Model/Behavior/TrackActivityBehavior.php` — a developer reading either stack sees the same contract. Volume on dmo-dev during a full SuperAdminEscalationProtection test run: zero extra `Log::` lines from authenticated CRUD; one line per simulated attack.
