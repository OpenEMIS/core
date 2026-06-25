# POCOR-9697 — Integration merge plan

**Audience:** whoever integrates POCOR-9697 with POCOR-9660 and the `tst-5.10.x` line.
**Captured:** 2026-05-11; **Refreshed:** 2026-05-12 after commit `892af27276` (Wave 1c hardening — silent-drop → HTTP 400).
**Branch tip:** `892af27276` on `origin/POCOR-9697`. 23 commits ahead of master.

## TL;DR

| Target | Expected outcome | Risk |
|---|---|---|
| POCOR-9697 + POCOR-9660 | **1 conflict** in `CrudApiController::handleGetRequest` | Medium — keep both fixes |
| POCOR-9697 + `tst-5.10.0` | **Clean merge** (no overlap) | None |
| POCOR-9697 + `tst-5.10.0-1` | **1 conflict** in `Api5/SecurityUsers.php` (`$fillable` + 3× Swagger) | **High — security regression if resolved wrong** |

Always merge POCOR-9660 **before** POCOR-9697 (smaller surface, simpler conflict). Then layer POCOR-9697 onto whichever `tst-5.10.x` line is current.

---

## Conflict 1 — `CrudApiController.php` (POCOR-9697 ↔ POCOR-9660)

### What each branch did

**POCOR-9660** rewrote `handleGetRequest` so all GET-pipeline calls run inside `try/catch (InvalidArgumentException)`. Invalid orderby / filter values now surface as `400` (not `500`). It also injected `parseImplicitIdFilter` above the order block.

```php
//POCOR-9660: implicit id-list segment → filter
try { $implicitIdFilter = $this->parseImplicitIdFilter($model, $segments); ... }
catch (\InvalidArgumentException $e) { return $this->errorResponse($e->getMessage(), 400); }

try {
    $order = $this->parseOrderParams($request, $segments);
    $query = $this->parseSelectParams($request, $segments, $query, $model);
    $query = $this->applyFilters($query, $filters);          // ← legacy signature
    $query = $this->applyOrder($query, $order, $model);
} catch (\InvalidArgumentException $e) { return $this->errorResponse($e->getMessage(), 400); }
```

**POCOR-9697** (after the 2026-05-12 hardening) added its OWN try/catch wrapper around `applyFilters`, plus passed `$model` so the allowlist can fire. The current `handleGetRequest` block on POCOR-9697 looks like:

```php
$query = $this->parseSelectParams($request, $segments, $query, $model);
//POCOR-9697: applyFilters throws InvalidArgumentException when a filter key is
//not in the per-model allowlist. We translate to a generic 400 with no field
//name so the response never fingerprints which key was rejected.
try {
    $query = $this->applyFilters($query, $filters, $model);
} catch (\InvalidArgumentException $e) {
    return $this->errorResponse($e->getMessage(), 400);
}
$query = $this->applyOrder($query, $order, $model);
```

`applyFilters` signature is `($query, array $filters, $model = null)`. Both branches now use `\InvalidArgumentException` → 400 — so the merge can use a single shared try/catch.

### Resolution

Combine both wrappers — POCOR-9660's implicit-id-list block stays as its own try/catch, then POCOR-9660's main try/catch absorbs POCOR-9697's by passing `$model` into `applyFilters`:

```php
//POCOR-9660: implicit id-list segment (e.g. /resource/4,5,6) → filter
try {
    $implicitIdFilter = $this->parseImplicitIdFilter($model, $segments);
    if (!empty($implicitIdFilter)) {
        $filters = array_merge($filters, $implicitIdFilter);
    }
} catch (\InvalidArgumentException $e) {
    return $this->errorResponse($e->getMessage(), 400);
}

//POCOR-9660 + POCOR-9697: surface invalid orderby column AND non-present filter
//field names as 400. Both throw \InvalidArgumentException, one wrapper covers both.
try {
    $order = $this->parseOrderParams($request, $segments);
    $query = $this->parseSelectParams($request, $segments, $query, $model);
    $query = $this->applyFilters($query, $filters, $model); //POCOR-9697: pass $model so applyFilters enforces per-model column allowlist
    $query = $this->applyOrder($query, $order, $model);
} catch (\InvalidArgumentException $e) {
    return $this->errorResponse($e->getMessage(), 400);
}
```

One added token (`, $model`) is the difference between secure and silently-bypassed. **If `$model` is omitted, the Wave 1c hardening silently no-ops** even though PHP doesn't error (`$model = null` makes the allowlist empty, which short-circuits the check).

### Verification after resolution

```bash
cd api
php artisan test --filter=SuperAdminEscalationProtectionTest   # POCOR-9697 suite — 22 tests
php artisan test --filter=CrudApi                              # POCOR-9660 suite (whichever name)
```

Then a manual two-curl smoke test:

```bash
# POCOR-9697: read-side enumeration must return HTTP 400 with generic message
curl -ks -w '\nStatus: %{http_code}\n' -H "Authorization: Bearer $TOKEN" \
  "https://localhost:8482/core/api/v5/security-users?_conditions=super_admin:1"
# Expected: Status 400 + body "Request contains filter field names that are not present in this resource..."
# REGRESSION SIGNAL: Status 200 with rows → Wave 1c bypassed — $model probably missing from applyFilters call

# POCOR-9660: implicit id-list must still work
curl -ks -H "Authorization: Bearer $TOKEN" \
  "https://localhost:8482/core/api/v5/academic-periods/1,2,3" | jq '.data | length'
# Expected: 3
```

Both must pass; if either regresses, the merge is wrong.

---

## Conflict 2 — `Api5/SecurityUsers.php` (POCOR-9697 ↔ tst-5.10.0-1)

### What each branch did

**tst-5.10.0-1** carries POCOR-9590 (Civil Status Sync indicator). It added `sync_status` to `$fillable` and Swagger PHPDoc, and **still has `super_admin` listed in `$fillable`** + visible in 3 Swagger `@OA\Property` blocks (basic-information / create / update).

**POCOR-9697** explicitly **removed** `super_admin` from `$fillable` (commit `79f0baace4`) and from all Swagger response examples (commit `40eb6869b8`). This is the central security fix.

### The conflict regions (3 spots in one file)

1. **`$fillable` array** — POCOR-9697 dropped `super_admin`; POCOR-9590 kept it and added `sync_status`.
2. **Swagger response examples ×3** — POCOR-9590 added `sync_status` + kept `super_admin`; POCOR-9697 removed `super_admin`.

### Resolution rule (memorise this)

> **Never accept their side on `super_admin`.**
> **Always accept their side on `sync_status`.**

So the merged `$fillable` should look like:

```php
protected $fillable = [
    ...,
    'sync_status', //POCOR-9590: 0=Local, 1=Synced, 2=Not Synced
    'status', 'last_login',
    // NO 'super_admin' — POCOR-9697
];
```

And each of the 3 Swagger blocks:

```php
@OA\Property(property="sync_status", type="integer", enum={0,1,2}, example=0, description="..."),
// NO @OA\Property(property="super_admin", ...) — POCOR-9697
```

### Verification after resolution

```bash
cd api
php artisan test --filter=SuperAdminEscalationProtectionTest    # 21 must pass
php artisan test --filter=SecurityUsersApiTest                  # POCOR-9590 sync_status checks
php artisan l5-swagger:generate                                 # regenerate api-docs-v4/v5.json
grep -c '"super_admin"' public/api-docs-v5.json                 # should be 0 (or only `description` mentions)
```

Then a curl smoke test:

```bash
# Mass-assignment must still be blocked
curl -ks -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"username":"x","first_name":"x","last_name":"x","super_admin":1}' \
  "https://localhost:8482/api/v5/security-users" | jq '.data.super_admin'
# Expected: null or 0 (silent-strip — never 1)
```

If the response shows `"super_admin":1`, the merge undid the security fix. Stop and re-resolve.

---

## Recommended merge order

1. **POCOR-9660 → release line** (when QA signs off). One isolated branch, two files, no surprises.
2. **POCOR-9697 → release line**, *after* POCOR-9660 lands. Resolve **Conflict 1** as documented above.
3. **POCOR-9697 → `tst-5.10.x`** (whichever is current). Resolve **Conflict 2** as documented above.

Reversed order also works (POCOR-9697 first, then POCOR-9660 on top) — the same one-line resolution applies, just inverted ours/theirs. Pick whatever order matches the QA pipeline.

---

## Cross-references

- POCOR-9697 README: `api/storage/release-docs/POCOR-9697-README.md`
- POCOR-9660 README: `api/storage/release-docs/POCOR-9660-README.md`
- Security catalog (cross-product): mempalace wing `openemis-vX-v5-security-monitoring`
- Test suite: `api/tests/Feature/SuperAdminEscalationProtectionTest.php` (21 tests, all green at `42e34ef633`)
- Postman collection: `api/storage/release-docs/POCOR-9697/POCOR-9697.postman_collection.json`

---

## Appendix — files changed by each branch (for the integrator)

**POCOR-9697 vs master (19 files):**

```
api/app/Http/Controllers/AttendanceController.php
api/app/Http/Controllers/BaseApi/CrudApiController.php
api/app/Http/Controllers/DirectoryController.php
api/app/Http/Controllers/MealController.php
api/app/Http/Controllers/UserController.php
api/app/Http/Controllers/WorkbenchController.php
api/app/Http/Requests/UsersAddRequest.php
api/app/Models/Api5/SecurityUsers.php
api/app/Models/Concerns/UserActivityLog.php
api/app/Models/SecurityUsers.php
api/app/Repositories/DirectoryRepository.php
api/app/Repositories/UserRepository.php
api/app/Services/UserService.php
api/public/api-docs-v4.json
api/public/api-docs-v5.json
api/storage/release-docs/POCOR-9697-README.md
api/storage/release-docs/POCOR-9697/POCOR-9697.postman_collection.json
api/storage/release-docs/POCOR-9697/POSTMAN-RECORDING-GUIDE.md
api/tests/Feature/SuperAdminEscalationProtectionTest.php
```

**POCOR-9660 vs master (2 files):**

```
api/app/Http/Controllers/BaseApi/CrudApiController.php
api/storage/release-docs/POCOR-9660-README.md
```

**Intersection:** `api/app/Http/Controllers/BaseApi/CrudApiController.php` — the one file that needs a manual merge.

**tst-5.10.0 vs master:** 20 files, all under `plugins/` and `src/Controller/AppController.php`. Zero overlap with POCOR-9697 → clean merge.

**tst-5.10.0-1 vs master:** 46 files (includes POCOR-9590). One overlap with POCOR-9697: `api/app/Models/Api5/SecurityUsers.php` → manual merge per rule above.
