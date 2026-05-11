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

### Files Changed Summary

* **Added:** 2 files
* **Modified:** 12 files
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
| `api/tests/Feature/SuperAdminEscalationProtectionTest.php` | **New.** 8 feature tests covering every layer of the fix. All pass. |

### Database Migrations

None. No schema change required.

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
