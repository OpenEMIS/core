# POCOR-9660 — CrudApiController v5 GET Hardening

## 1. What is the Task?

The generic v5 GET pipeline in `CrudApiController` had several gaps that made it brittle for integrators:

- No way to filter by multiple IDs in a single request — every caller had to loop.
- The `_conditions` parameter accepted only single-value comparisons; there was no IN-operator syntax.
- An invalid `orderby` column crashed with a 500 instead of returning a descriptive 400.
- Identical CSV-split logic was duplicated across three places in the same file.
- The try/catch boundary around order/filter/select was split, so one failure path bypassed the 400 surface.

---

## 2. Situation Before

All `GET /api/v5/{resource}` requests were handled by `CrudApiController::handleGetRequest()`. The method supported:

- Single-record retrieval: `/api/v5/security-users/42`
- Simple field filters: `?institution_id=5`
- Conditions: `?_conditions=status:1;name:John`
- Ordering: `?orderby=first_name&order=asc` (column not validated — any string passed through to SQL)

**Missing before this PR:**
- `?id=1,2,3` — only single value worked; comma-separated was passed as a literal string.
- `_conditions=id:IN(1,2,3)` — `IN` operator not supported.
- `/api/v5/security-users/1,2,3` — implicit multi-ID segment not recognized.
- Invalid `orderby` produced an unhandled 500 error.
- Duplicate CSV-split code in three locations.

---

## 3. What Was Implemented

### Multi-ID filter via query parameter
`?id=1,2,3` and `?institution_id=1,2,3` are now split into arrays and resolved via `WHERE … IN (…)`.
Any field named exactly `id` or ending with `_id` qualifies. All values must pass `isValidIdentifier()` (numeric or UUID-safe); mixed or alphabetic values fall back to the original single-value path.

### IN operator in `_conditions`
`?_conditions=id:IN(1,2,3)` and `?_conditions=id:IN(1,2,3)` both work. Parentheses are optional: `id:IN1,2,3` is accepted. The parser is in `parseInConditionValues()`.

### Implicit ID-list URL segment
`GET /api/v5/security-users/1,2,3` is now recognized as a multi-ID request. The segment must contain only valid identifiers separated by commas; anything else raises a 400.

### Schema-derived orderby allowlist with hidden-column protection (cached 10 min)
`getAllowedOrderColumns()` reads the real column list from the DB schema via `SchemaBuilder::getColumnListing()`, then removes every column in the model's `$hidden` array before caching. Two reasons to block `$hidden` columns from sorting:
- **Schema enumeration**: `orderby=password` returning 200 vs 400 reveals that the column exists, even though it is never returned in the response body.
- **Value exposure via ordering**: the sort order of results leaks relative hash values across pages, even without the column being returned.

Any `orderby` value not in the allowlist raises an `InvalidArgumentException`, which the merged try/catch surfaces as HTTP 400. Cache key: `crud_api_sortable_columns:v3:{ModelClass}:{table}`.

### 400 instead of 500 for bad input
A single try/catch now wraps `parseOrderParams`, `parseSelectParams`, `applyFilters`, and `applyOrder`. Any `InvalidArgumentException` thrown inside any of these returns a 400 JSON error.

### `splitAndTrimValues()` helper
Extracted from three identical inline `explode/trim/filter` chains. Splits a CSV string, trims each item, and drops empty entries.

### Simplify (code-quality pass)
- Merged two separate try/catch blocks into one.
- Replaced `strpos($value, 'IN') !== 0` with `str_starts_with($value, 'IN')`.
- Simplified double-`substr` parenthesis-strip to `trim(substr($value, 2), '()')`.
- Removed a redundant `strtolower` call already covered by `parseOrderParams`.

### Files Changed

| File | Change |
|------|--------|
| `api/app/Http/Controllers/BaseApi/CrudApiController.php` | All new features and cleanup |

### Database Migrations

None. This is a pure PHP change.

---

## 4. Deployment Instructions

1. Pull/merge the branch.
2. Clear the Laravel application cache (orderby column cache TTL is 10 min, but clearing ensures a clean start):
   ```bash
   php artisan cache:clear
   ```
3. No migration required.
4. No environment variable changes required.

---

## 5. System Administrator Guide

### New query capabilities for all `/api/v5/{resource}` GET endpoints

#### Multiple IDs — query param
```
GET /api/v5/security-users?id=1,2,3
GET /api/v5/institution-students?institution_id=4,5,6
```

#### Multiple IDs — URL segment (explicit key)
```
GET /api/v5/security-users/id/1,2,3
```

#### Multiple IDs — URL segment (implicit)
```
GET /api/v5/security-users/1,2,3
```

#### IN operator in `_conditions`
```
GET /api/v5/security-users?_conditions=id:IN(1,2,3)
GET /api/v5/security-users?_conditions=status:IN(0,1)
```
Parentheses are optional: `id:IN1,2,3` also works.

#### Invalid orderby now returns 400
```
GET /api/v5/security-users?orderby=password
→ HTTP 400  {"error": "Invalid orderby column: password"}
```

#### Orderby column allowlist
Sortable columns are derived from the actual DB schema for each resource table. The list is cached in Laravel's cache backend for 10 minutes. After a schema change (e.g. new column added), the cache expires automatically within 10 minutes, or sooner after `php artisan cache:clear`.

### Security notes
- All multi-ID values are validated by `isValidIdentifier()` before being passed to `whereIn()`. Non-numeric/non-UUID values cause the CSV to fall back to single-value equality or raise a 400.
- `orderby` is validated against the real schema column list — arbitrary column names cannot be injected.
- `_conditions` values are passed through Eloquent's parameterized query builder — no raw SQL interpolation.
