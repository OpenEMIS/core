# POCOR-9591 — Account Locked Function

## What is the Task?

POCOR-9591 introduces an **Account Locked** status (value = 2) separate from **Inactive**. When a user exceeds the maximum login attempts (configured in `config_items.login_attempts`, default 5), their account is automatically set to Locked instead of Inactive. Locked accounts remain visible in Directory and Institution lists, and administrators can unlock them by editing the user's status back to Active. Additionally, JWT tokens held by locked users are rejected at the API layer.

---

## Situation Before

- Exceeding login attempts set `security_users.status = 0` (Inactive), making locked accounts indistinguishable from admin-disabled accounts
- Locked users disappeared from Directory and Institution lists (filtered by `status = 1`)
- No "Locked" option in Advanced Search Status filter
- API v5 JWT middleware did not validate user status, allowing locked users with valid tokens to access the API

---

## What Was Implemented

### Overview

Three core changes:
1. **Constants**: Define `STATUS_LOCKED = 2` in both CakePHP and Laravel models
2. **Login Lockout**: Update two login failure points to set `STATUS_LOCKED` instead of 0
3. **Visibility & Filtering**: Show locked users in Directory lists, add Locked to Advanced Search Status filters
4. **API Security**: Reject JWT tokens for locked/inactive users

### Files Changed Summary

| Category | Count | Details |
|----------|-------|---------|
| Added | 1 | Migration backing up security_users |
| Modified | 8 | LoginController, App\Models\SecurityUsers, Api5\SecurityUsers, UsersController, UsersTable, DirectoriesTable, AuthenticateJwt |
| **Total** | **9** | |

### Detailed Changes

#### 1. api/app/Http/Controllers/Authentication/LoginController.php
- **Line ~70–76**: Before attempting `JWTAuth::attempt()`, check if user status is `STATUS_LOCKED` (2) or `STATUS_INACTIVE` (0) and return HTTP 403 with distinct error messages
- **Line ~89–100**: On failed login attempt, read `config_items.login_attempts` threshold; increment `failed_logins`; when count reaches threshold, set `status = STATUS_LOCKED` and return HTTP 403
- **Line ~105**: On successful login, reset `failed_logins = 0`

#### 2. api/app/Models/SecurityUsers.php (App\Models, not Api5)
- **Lines 21–24**: Added `const STATUS_ACTIVE = 1`, `const STATUS_INACTIVE = 0`, `const STATUS_LOCKED = 2`
- **Line 29**: Added `'status' => 'integer'` to `$casts` array for type-safe integer comparison

#### 3. api/app/Models/Api5/SecurityUsers.php
- **Line 29**: Added `'status' => 'integer'` to `$casts` array (constants already present)

#### 4. plugins/Security/src/Model/Table/UsersTable.php
- Added `const STATUS_LOCKED = 2` alongside existing `ACTIVE = 1` and `INACTIVE = 0`
- Updated `getCustomFilter()` to include Locked option in Advanced Search Status filter

#### 5. plugins/User/src/Controller/UsersController.php
- **Line ~690** (POCOR-8680 block): Changed `status => 0` to `status => UsersTable::STATUS_LOCKED`
- **Line ~943** (POCOR-2976 block): Changed `status => 0` to `status => UsersTable::STATUS_LOCKED`
- Pre-login check now differentiates between Inactive and Locked with separate error messages

#### 6. plugins/Directory/src/Model/Table/DirectoriesTable.php
- **Line ~1549** in `indexBeforeQuery()`: Changed `status = 1` filter to `status IN (1, 2)` so Locked users remain visible
- Added Status filter to Directory Advanced Search with Locked option

#### 7. api/app/Http/Middleware/AuthenticateJwt.php
- Added status validation check: JWT tokens for users with `status = 0` (Inactive) or `status = 2` (Locked) are rejected with HTTP 401
- Prevents locked users from accessing the API even with a valid token

#### 8. config/Migrations/20260420000000_POCOR9591.php
- Backs up `security_users` table as `z_9591_security_users` before any changes
- ALTER TABLE adds column comment: `'0=Inactive, 1=Active, 2=Locked'` to document valid status values
- Migration down() restores from backup (idempotent, safe for reruns)

### Database Migrations

| Migration | Action | Rollback |
|-----------|--------|----------|
| `20260420000000_POCOR9591` | Backup `security_users` | Restore from `z_9591_security_users` |

---

## Deployment Instructions

1. **Pull latest code**
   ```bash
   git pull origin POCOR-9591
   ```

2. **Run migration**
   ```bash
   cd /var/www/html/emis/core
   php bin/cake.php migrations migrate
   ```

3. **Clear CakePHP caches**
   ```bash
   php bin/cake.php cache clear_all
   ```

4. **Clear Laravel caches**
   ```bash
   cd api
   php artisan config:cache && php artisan route:clear && php artisan cache:clear
   ```

5. **Smoke test**
   - Log in as admin with valid credentials → expect success
   - POST /api/v4/login with locked account (status=2) → expect HTTP 403 "Account is locked."
   - POST /api/v4/login with inactive account (status=0) → expect HTTP 403 "Account is inactive."
   - Attempt web login with wrong password 5+ times → expect "Account is locked" message
   - Verify account locked (status=2) in DB: `SELECT id, username, status FROM security_users WHERE status=2 LIMIT 1`
   - Verify locked user does NOT appear in Institution Student/Staff lists (status=1 filter still applies)
   - Verify locked user DOES appear in Directory > List (status IN (1,2) now)
   - Edit locked user in Administration > Security > Users, set status back to Active
   - Attempt login again (web and API) → expect success
   - Verify failed_logins reset to 0 on successful login

---

## System Administrator Guide

### Account Lockout Rules

When a user exceeds `config_items.login_attempts` (default: 5) within a session, their account is automatically set to status = 2 (Locked). The user will see:
- **Login page message**: "Account is locked. Please contact your administrator."
- **API response**: HTTP 401 "Account is locked"

### Unlocking Locked Accounts

1. Navigate to **Administration > Security > Users**
2. Find the locked user in the list
3. Click **Edit** (pencil icon)
4. Change **Status** dropdown from "Locked" to "Active"
5. Click **Save**
6. The user can now log in again

**Note:** No automatic unlock is triggered by time passing — only manual admin action or code changes will unlock an account.

### Viewing Locked Accounts

- **Administration > Security > Users**: Use the **Status** filter in Advanced Search to filter by:
  - Active (status = 1)
  - Inactive (status = 0)
  - Locked (status = 2)
- **Directory > List**: Locked users are now visible (previously hidden). Filter by Status to isolate them.
- **Institution Student/Staff Lists**: Locked users remain hidden (they use `student_status_id`, not `security_users.status`)

### Configuring Max Login Attempts

The number of allowed failed login attempts before lockout is configured in:
- **Administration > System Setup > System Configurations**
- Find: **login_attempts**
- Default value: 5
- Change and save to take effect on next login

### API Access for Locked Users

- **Login endpoint** (`POST /api/v4/login`): Pre-login status check rejects locked (status=2) and inactive (status=0) accounts with HTTP 403 before password validation
- **Failed attempts**: LoginController increments `security_users.failed_logins`; when count ≥ `config_items.login_attempts`, status is set to 2 and HTTP 403 returned
- **Existing JWT tokens**: `AuthenticateJwt` middleware validates status on every API request; locked/inactive users are rejected with HTTP 401
- **Successful login**: `failed_logins` counter is reset to 0
- **Unlocking**: Edit user in Administration > Security > Users and change status back to Active; API access resumes on next login

### Rollback Plan

If issues arise:
1. Run migration rollback: `php bin/cake.php migrations rollback -t <previous_version>`
2. Restore `security_users` from backup table `z_9591_security_users` (migration has restore logic in `down()`)
3. Any locked accounts (status=2) will be restored to their original state

### Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| Locked user not appearing in Directory | Caches not cleared | Run `php bin/cake.php cache clear_all` |
| JWT still accepted for locked user | API not redeployed | Re-run deployment steps 3–4 |
| User sees "Inactive" instead of "Locked" | Old error message | Clear browser cache or use incognito window |
