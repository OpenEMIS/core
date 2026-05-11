# POCOR-9697 — Postman side-by-side recording guide

Use this collection to record **two videos**:

1. **BEFORE.mp4** — run against `master`. Most tests go **red** ❌. Demonstrates the vulnerability.
2. **AFTER.mp4** — run against `POCOR-9697`. All tests go **green** ✅. Demonstrates the fix.

Same collection, same requests, same order. Only the deployed code changes between recordings.

## Setup (do once)

1. **Disable SSL verification** — Postman → Settings → General → uncheck *SSL certificate verification*.
   (Local dev cert is self-signed.)
2. **Import** `POCOR-9697.postman_collection.json` into Postman.
3. **Edit collection variables** if your environment differs from defaults:
   - `base_url` → e.g. `https://localhost:8482/core`
   - `username` / `password` → an admin account (e.g. `admin` / `demo`)
   - `api_key` → `apikeytest` for the seeded test API key
4. **Record one screen** that shows the whole Postman window plus your console output (a small terminal for narration is optional).

## Recording protocol

For each recording, in Postman:

1. Open the collection runner (Runner → New Run → select `POCOR-9697`).
2. Make sure the requests run **in numeric order** (00, 01, …, 10).
3. Click *Run*. Postman will execute every request and show per-request test results.

That's the entire video — no manual clicking, no typing on camera. Postman records exact status codes, response bodies, and test pass/fail in one continuous run.

## What the audience will see

### On `master` (BEFORE)
| # | Request | Expected result |
|---|---|---|
| 00 | Login | ✅ 200 |
| 01 | v4 super_admin mass-assign | ❌ **200** (exploit works) — should be 422 |
| 02 | v4 elevate existing admin | ❌ **200** — should be 422 |
| 03 | v5 super_admin mass-assign | ⚠️ 201 (request OK) — but row created with super_admin=1 |
| 04 | Login as new user | ❌ usually fails (password stored as plaintext) |
| 05 | /permissions super_admin flag | ❌ **super_admin = 1** — fully escalated |
| 06 | v4 GET /users/{id} no leak | ❌ response contains `super_admin` and `password` |
| 07 | basic-information no leak | ❌ rows contain `super_admin` |
| 08 | v4 swagger ≤1 super_admin | ❌ 6 hits |
| 09 | v5 swagger 0 super_admin | ❌ 3 hits |
| 10 | Cleanup | ✅ (or 404 if step 03 failed) |

### On `POCOR-9697` (AFTER)
Every row above flips to ✅.

## Two narration prompts

For the BEFORE video, say at the start:
> "This is OpenEMIS Core on master, pre-fix. Watch what a low-priv account can do."

For the AFTER video, say at the start:
> "Same collection, same requests. Branch POCOR-9697 deployed. Nothing else changed."

End the AFTER video by opening the Tests tab on request 05 and pointing out `super_admin = 0` — that's the single line of proof.

## Re-running

The collection seeds a unique `exploit_username` per run (uses `Date.now()`), so you can re-run safely. If anything gets stuck:

```sql
DELETE FROM security_users WHERE username LIKE 'exploit_%';
```

## Files

- `POCOR-9697.postman_collection.json` — the collection.
- `POSTMAN-RECORDING-GUIDE.md` — this file.

Both also live under `api/storage/release-docs/POCOR-9697/` so they ship with the branch.
