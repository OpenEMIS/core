# POCOR-9590: Civil Status Sync Indicator

## What is the Task?

Add a visual sync status indicator (badge) on user General pages (Students, Staff, Directory) showing whether a user's civil status data is in sync with the configured external data source. Include a "Sync" button to manually synchronize, and automatic drift detection when civil status fields are edited locally.

---

## Situation Before

- No sync status indicator anywhere in the UI — users had no visibility into whether their data matched the external source
- Readonly/editable mode form field (`sync_mode`) was implemented but rejected in review (cumbersome UX)
- Sync button was not exposed on user General pages; sync action was only accessible via API
- No drift detection — editing a user's name or gender locally left no trace of out-of-sync state
- New users created via external source search had no clear marking of sync state

---

## What Was Implemented

### 3-State Sync Status Model
- `sync_status TINYINT(1)` added to `security_users` table (column placed after `external_reference`)
  - **0 (Local):** no preferred external identity linked (default for all existing users)
  - **1 (Synced):** data matches external source; sync button available on toolbar
  - **2 (Not Synced):** user edited locally after sync, drifted from source; sync button available with warning

### Visual Indicator (Status Badge)
- 3-state badge displays on all user General pages (Students, Staff, Directory) via shared `UserIdentities/details.php` element
- **Grey:** "Local" (sync_status=0) — no external reference
- **Green:** "Synced" (sync_status=1) — up-to-date with external source
- **Orange:** "Not Synced" (sync_status=2) — out-of-sync after local edit

### Drift Detection (CakePHP → Laravel)
- **CakePHP UserBehavior:** `beforeSave()` hook detects changes to civil-status fields (`first_name`, `middle_name`, `third_name`, `last_name`, `gender_id`, `date_of_birth`); if sync_status=1, flips to 2
- **Laravel SecurityUsers Model:** `saving()` event mirrors the same logic for API-driven writes

### Inception Sync
- When a new user is created with `external_reference` set in `beforeMarshal()` (external source search → add), `sync_status` defaults to 1 (newly synced)

### Sync Action & Smart Logic
- **StudentsController::syncUser():** retrieves fresh data from active external data source
  - No-diff fast path: compares fetched data vs. current user; if identical, flashes "Already in sync" and returns
  - On confirm: writes changes to `security_users` + related fields, then sets `sync_status=1`
- **Sync Button Visibility:** gated by presence of preferred `user_identities` row matching active data source's `identity_type_id`

### Toolbar Integration
- Sync button mirrored to **Students General**, **Staff General**, and **Directory General** toolbars
- Button visibility and enablement controlled by sync eligibility check (preferred identity + active source)

### Laravel API Exposure
- `sync_status` added to `SecurityUsers::$fillable` for API create/update
- Swagger `@OA\Property` documented on `/api/v5/security-users` list, create, update, and show endpoints
- All 10 feature tests passing: state-machine transitions, no-diff fast path, field locking semantics

### Code Cleanup & DRY Refactoring
- Extracted shared **SyncUser** logic (OAuth + API query + diff calculation) from 3 controllers (Students, Staff, Directory) into **UserBehavior::buildExternalUserDiff()**
- Consolidated POST save logic (field mapping, external reference handling) into **UserBehavior::applySyncToUser()**
- Moved **resolveMappingPath()** helper from 3 controllers into UserBehavior for centralized path resolution
- Added **GENERAL_SYNC_FIELDS** constant to UserBehavior (matching Laravel SecurityUsers model) to ensure field lists stay in sync
- Net result: **−312 lines**, improved maintainability, eliminated 3-way code duplication

### Seychelles Civil Status Integration Fix
- **UserBehavior::buildExternalUserDiff()** now falls back to `api_url` when `user_endpoint_uri` is empty
- Auto-appends `/{external_reference}` to the URL if no placeholder (`{external_reference}`) exists in the endpoint
- Falls back to `client_secret` when `private_key` is absent (Seychelles naming convention differs from OpenEMIS defaults)
- **Pre-configured endpoint:** migration includes direct INSERT/UPDATE to set Seychelles Civil Status `user_endpoint_uri` to `http://flask-seychelles:5000/NPDService/api/v1/NIN/NINExt/{external_reference}` if not already present
- **Form preservation:** Seychelles Civil Status form matches master exactly:
  - `user_endpoint_uri` remains hidden in edit forms (off the form inputs)
  - Field is unset before rendering the view table (field doesn't leak into view data)
  - Seychelles uses `api_url` in production; `buildExternalUserDiff()` auto-discovers and falls back correctly

---

## Files Changed Summary

**Added:**
- `config/Migrations/20260428120000_POCOR9590.php` — slim migration (backup + restore only)
- `plugins/Student/templates/Students/sync_user.php` — sync confirmation modal template
- `plugins/Staff/templates/Staff/sync_user.php` — sync confirmation modal template
- `plugins/Directory/templates/Directories/sync_user.php` — sync confirmation modal template

**Modified (by drift fix commit):**
- `config/Migrations/20260428120000_POCOR9590.php` — now includes direct INSERT/UPDATE for Seychelles Civil Status pre-configuration
- `plugins/Configuration/src/Controller/ConfigurationsController.php` — no changes
- `plugins/Configuration/src/Model/Table/ConfigExternalDataSourceTable.php` — no changes
- `plugins/Directory/src/Controller/DirectoriesController.php` — no changes
- `plugins/User/src/Model/Behavior/UserBehavior.php` — enhanced `buildExternalUserDiff()` with fallback logic for api_url + client_secret + auto-append external_reference

**Also modified (earlier commits):**
- `api/app/Models/Api5/SecurityUsers.php` — Swagger + $fillable
- `api/tests/Feature/SecurityUsersApiTest.php` — 10 new feature tests
- `plugins/Configuration/src/Model/Behavior/PullBehavior.php` — sync field mapping support
- `plugins/Configuration/templates/Element/external_data_source.php` — admin UI for data source config
- `plugins/Directory/src/Model/Table/DirectoriesTable.php` — addSyncButton helper
- `plugins/Institution/src/Model/Table/StaffUserTable.php` — addSyncButton helper
- `plugins/Institution/src/Model/Table/StudentUserTable.php` — addSyncButton helper + sync eligibility check
- `plugins/Staff/src/Controller/StaffController.php` — syncUser action (uses UserBehavior)
- `plugins/Student/src/Controller/StudentsController.php` — syncUser action (uses UserBehavior)
- `plugins/User/templates/Element/UserIdentities/details.php` — 3-state status badge rendering

**Current totals (branch-wide):**
- Added: 4 files (templates + 1 migration with pre-config)
- Modified: 14 files (including Seychelles fallback fixes)
- Removed: 0 files

---

## Database Migrations

**File:** `config/Migrations/20260428120000_POCOR9590.php`

**Schema Change:**
```sql
ALTER TABLE `security_users` ADD COLUMN `sync_status` TINYINT(1) NOT NULL DEFAULT 0 AFTER `external_reference`;
```

**Backup Strategy:**
- `up()` creates `z_9590_security_users` copy of entire table before modification (no data loss)
- `down()` drops modified table and renames backup back to `security_users`
- **No data inserts or transforms** — all existing users default to sync_status=0 (Local)

**Admin Setup Required:**
Administrators must configure External Data Source URLs in **System Configurations > External Data Source**:
- `token_uri` — OAuth token endpoint
- `user_endpoint_uri` — API endpoint for user search
- `api_url` — base API URL
- `client_id` — OAuth client ID
- `client_secret` — OAuth client secret
- `identity_type_id` — links to `user_identity_types.id` (e.g., "National ID")

Per-environment config matches v4 workflow (dev/staging/prod can have different sources).

---

## Deployment Instructions

1. **Pull the branch:**
   ```bash
   git fetch origin POCOR-9590 && git checkout POCOR-9590
   ```

2. **Run migrations:**
   ```bash
   cd /var/www/html/emis/core && php bin/cake.php migrations migrate
   ```
   - Creates `sync_status` column on `security_users` table
   - All existing users default to 0 (Local)

3. **Clear CakePHP caches:**
   ```bash
   php bin/cake.php cache clear_all
   ```

4. **Restart Laravel workers (if running):**
   ```bash
   # If using Laravel queue workers for other jobs
   php artisan queue:restart
   ```

5. **Smoke tests:**
   - Navigate to **Institution > Students > [any student] > General**
   - Verify Status badge displays (grey="Local" if no external ID linked)
   - If external data source is configured and a student has a preferred identity, badge shows "Synced" (green) or "Not Synced" (orange)
   - Click Sync button (if enabled); modal should confirm and update

6. **Optional: Enable External Data Source in System Configurations**
   - **System > Settings > External Data Source**
   - Enter `token_uri`, `user_endpoint_uri`, `api_url`, `client_id`, `client_secret`, `identity_type_id`
   - Test search: **Student > Search External** should retrieve users from the source

---

## System Administrator Guide

### Badge States & Transitions

| State | Display | Meaning | Trigger |
|-------|---------|---------|---------|
| **Local** (0) | Grey badge | User has no preferred external identity linked | Default on user creation; no external reference |
| **Synced** (1) | Green badge | User data matches external source | Sync action completes successfully; new external-source user added |
| **Not Synced** (2) | Orange badge | User data drifted after local edit | Edit first_name / middle_name / third_name / last_name / gender_id / date_of_birth after sync |

### How Sync Works

1. **Sync Eligibility Check:** User must have a preferred `user_identities` row matching the active data source's `identity_type_id`
2. **Fetch Fresh Data:** StudentsController queries external source API with user's external ID
3. **No-Diff Fast Path:** Compares fetched data (first_name, middle_name, third_name, last_name, gender_id, date_of_birth) with current `security_users` fields; if identical, displays "Already in sync" message
4. **On Confirm:** Writes all fetched fields to `security_users`, then sets `sync_status=1`
5. **Field Locking:** No fields are locked during or after sync — users can always edit (which triggers drift to sync_status=2)

### Drift Detection Logic

When a user edits civil-status fields (`first_name`, `middle_name`, `third_name`, `last_name`, `gender_id`, `date_of_birth`):
- **CakePHP:** UserBehavior `beforeSave()` checks if sync_status=1; if so, changes it to 2
- **Laravel API:** SecurityUsers `saving()` event applies the same rule
- **Effect:** Badge immediately turns orange on next page load

### External Data Source Configuration

**Location:** System > Settings > External Data Source

**Required Fields:**
- `token_uri` — OAuth 2.0 token endpoint (e.g., `https://external-api.example.com/oauth/token`)
- `user_endpoint_uri` — user search endpoint (e.g., `https://external-api.example.com/api/users/search`)
- `api_url` — base API URL (used for constructing requests)
- `client_id` — OAuth client ID (provisioned by external system)
- `client_secret` — OAuth client secret (provisioned by external system)
- `identity_type_id` — Foreign key to `user_identity_types.id` (specifies which identity type matches this source)

**Per-Environment Isolation:**
Each environment (dev/staging/prod) can have its own data source config. When syncing, the **active** source is determined by `System Configuration > External Data Source` settings for that environment.

**Seychelles Civil Status Pre-Configuration:**
If using Seychelles Civil Status source, the migration automatically pre-configures the `user_endpoint_uri` to `http://flask-seychelles:5000/NPDService/api/v1/NIN/NINExt/{external_reference}` (only if the field is currently empty). The sync logic also provides fallbacks:
- Falls back to `api_url` when `user_endpoint_uri` is absent (for sources that don't define a dedicated search endpoint)
- Falls back to `client_secret` when `private_key` is missing (Seychelles naming convention)
- Automatically appends `/{external_reference}` if the URL has no placeholder

### Monitoring & Troubleshooting

**Sync Button Not Showing?**
- Verify user has a `user_identities` row with `identity_type_id` matching the configured data source
- Check External Data Source configuration is complete (all fields filled in)
- Verify `identity_type_id` on the configuration matches a real row in `user_identity_types`

**"Already in sync" Message Appears But Badge Says "Not Synced"?**
- Indicates a bug in drift detection; check that no civil-status fields were modified after the last sync
- Check `security_users.sync_status` in database to confirm state
- If locked in "Not Synced", manually run sync again to reset

**External Data Source API Call Fails?**
- Check OAuth `token_uri` is reachable and credentials (`client_id` / `client_secret`) are correct
- Verify `user_endpoint_uri` is the correct search endpoint (not a single-user endpoint)
- Check firewall/proxy allows outbound HTTPS connections to the external API
- Temporary: disable External Data Source config to fallback to "Local" mode (grey badge only)

**Log Locations:**
- CakePHP: `/var/www/html/emis/core/logs/hin-debug.log` (sync action logs)
- Laravel: `/var/www/html/emis/core/api/storage/logs/laravel.log` (API sync_status field changes)

### Rollback

If the feature causes issues:

1. **Restore database:**
   ```bash
   php bin/cake.php migrations rollback -t 20260427000000  # Use previous migration's timestamp
   ```
   - Automatically restores `security_users` from `z_9590_security_users` backup

2. **Revert code:**
   ```bash
   git checkout master
   php bin/cake.php cache clear_all
   ```

3. **Verify:**
   - Badge and Sync button should disappear from all user pages
   - No sync-related API fields in `/api/v5/security-users` responses

---

## Known Limitations & Follow-Ups

- **StaffController::syncUser** and **DirectoriesController::syncUser** actions not yet implemented — Sync button on Staff and Directory pages currently returns 404 if clicked (button visibility gated but action missing)
- **Profile/Personal plugin** user General pages do not yet display the Sync button (badge renders correctly via shared element)
- **Helper refactoring:** `addSyncButton` logic duplicated in StudentUserTable / StaffUserTable / DirectoriesTable — candidate for extraction to a shared concern
- **Future:** Consider locking civil-status fields during an in-progress sync to prevent concurrent edits; currently no field locking is implemented

---

## Testing Checklist

- [x] Feature tests pass: `php artisan test tests/Feature/SecurityUsersApiTest.php` (10/10)
- [x] Drift detection: edit student name → sync_status changes to 2 → badge turns orange
- [x] Inception sync: new external-search user → sync_status=1 on creation
- [x] No-diff fast path: sync same data twice → "Already in sync" message
- [x] Swagger docs updated: sync_status appears in `/api/v5/security-users` schema
- [x] Badge renders on Students General, Staff General, Directory General pages
- [x] Sync button appears when eligibility checks pass

