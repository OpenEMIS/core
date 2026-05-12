# POCOR-9610 — Institution Registrations and Accreditations

---

## 1. What is the Task?

Add two new institution-profile features to OpenEMIS Core:

- **Registrations** — track validity periods during which an institution is officially registered.
- **Accreditations** — track programme-level accreditations, each linked to an `education_programme`, with their own validity periods.

Both appear as read-only tabs in the Institution left sidebar under **General**. Authoritative write access is via the new REST endpoints under `/api/v5/*`, gated by role-based permissions. Excel export is supported on both index views.

### 1.1 Why a New Feature (Naming Disambiguation)

The pre-existing **"Registrations"** module in OpenEMIS Core refers to **student registration / enrolment** — admitting students into an institution. It does **not** track whether the *institution itself* is licensed or accredited to operate.

This ticket introduces a distinct concept at the institution level:

- **Institution Registrations** — the institution's own legal/regulatory registration with a competent authority (e.g. Ministry of Education licence to operate), with a validity period.
- **Institution Accreditations** — per-programme accreditations the institution holds (e.g. "Bachelor of Education accredited 2024–2029"), each linked to an `education_programmes` row, with their own validity periods.

The two domains do not overlap. Student registration uses `institution_student_admission` / `institution_students`; this feature uses two brand-new tables (`institution_registrations`, `institution_accreditations`) and a separate sidebar entry under **Institution → General**.

---

## 2. Situation Before

- No `institution_registrations` or `institution_accreditations` tables existed.
- No CakePHP tabs, controllers, or Table classes existed for these two concepts.
- No Laravel API resources existed for these models.
- The Institution left sidebar had no Registrations / Accreditations entries.
- Roles tables (`security_functions`, `security_role_functions`) had no rows for either feature in the UI or API modules.

---

## 3. What Was Implemented

### 3.1 Schema

Two new tables are created. **The `institutions` table is NOT modified.**

**`institution_registrations`**

| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `institution_id` | INT NOT NULL | FK → `institutions.id` |
| `valid_from` | DATE NULL | NULL = valid since institution opening |
| `valid_to` | DATE NULL | NULL = no expiry (always valid) |
| `modified_user_id` | INT NULL | FK → `security_users.id` |
| `modified` | DATETIME NULL | |
| `created_user_id` | INT NOT NULL | FK → `security_users.id` |
| `created` | DATETIME NOT NULL | |

**`institution_accreditations`** — same shape plus:

| Column | Type | Notes |
|---|---|---|
| `education_programme_id` | INT NOT NULL | FK → `education_programmes.id` |

Both tables: InnoDB, `utf8mb4_unicode_ci`, indexed on every FK column.

### 3.2 Validity Semantics

The `valid_from` / `valid_to` columns are both nullable and interpreted at the application layer:

- `valid_from IS NULL` → record is valid since the institution opened (purely informational; rendered blank on the UI).
- `valid_to IS NULL` → no expiry; the record is treated as always valid.
- **Status** virtual column (shown in the UI and Excel export):
  - `Valid` when `valid_to IS NULL` or `valid_to >= CURDATE()`.
  - `Expired` when `valid_to < CURDATE()`.

There is no `beforeSave()` default for `valid_from` — NULL is a meaningful, persisted value.

### 3.3 CakePHP UI (Read-Only Tabs)

Two new tabs appear under Institution → General:

- `/Institutions/{id}/Registrations`
- `/Institutions/{id}/Accreditations`

**Registrations index columns:** Valid From | Valid To | Status

**Accreditations index columns:** Programme Code | Programme Name | Valid From | Valid To | Status

- `Programme Code` — from `education_programmes.code` via FK.
- `Programme Name` — full chain label: `"Name (Level — System — Period)"`.

**Read-only enforcement** (two layers, both required):

- Table classes: `toggle('add', false)`, `toggle('edit', false)`, `toggle('remove', false)` prevent button creation.
- `addBehavior('ControllerAction.HideButton')` suppresses any remaining instances.

**Excel export:** dates are forced to `'type' => 'string'` in `onExcelUpdateFields` so that `onExcelGet*` handlers are honoured (the default `onExcelRenderDate` returns blank in this system). The Registrations export is filtered by `institution_id` in `onExcelBeforeQuery`.

### 3.4 REST API v5

Endpoints (JWT-authenticated via `auth.jwt` middleware):

| Verb | Path |
|---|---|
| GET | `/api/v5/institution-registrations` |
| GET | `/api/v5/institution-registrations/{id}` |
| POST | `/api/v5/institution-registrations` |
| PUT | `/api/v5/institution-registrations/{id}` |
| DELETE | `/api/v5/institution-registrations/{id}` |
| GET / POST / PUT / DELETE | `/api/v5/institution-accreditations[/{id}]` |

No new controller — the resources are exposed via `CrudApiController::$allowedResources`. The standard `Services → Repositories → Models` pipeline is reused unchanged.

**Note:** An "idempotent upsert by `external_id`" pattern was discussed during scoping (see PROMPT.md history). It is **not** implemented — the final design is plain CRUD with database-assigned `id` and no business external key.

### 3.5 Permissions Matrix

**UI** — `security_functions` rows (`module=Institutions`, `controller=Institutions`, `category=General`, `parent_id=8`):

| Name | _view | _add | _edit | _delete | Roles granted view |
|---|---|---|---|---|---|
| `Registrations` | `Registrations.index\|Registrations.view` | NULL | NULL | NULL | 1, 2, 3, 4, 10 |
| `Accreditations` | `Accreditations.index\|Accreditations.view` | NULL | NULL | NULL | 1, 2, 3, 4, 10 |

NULLs in `_add/_edit/_delete` keep the UI strictly read-only at the permission layer too.

**API** — `security_functions` rows (`module=API`, `parent_id=10000`):

| Name | _view | _add | _edit | _delete |
|---|---|---|---|---|
| `Institution Registrations` | `InstitutionRegistrations.view\|InstitutionRegistrations.list` | `InstitutionRegistrations.add` | `InstitutionRegistrations.edit` | `InstitutionRegistrations.delete` |
| `Institution Accreditations` | `InstitutionAccreditations.view\|InstitutionAccreditations.list` | `InstitutionAccreditations.add` | `InstitutionAccreditations.edit` | `InstitutionAccreditations.delete` |

`security_role_functions` rows are inserted for roles `IN (1, 2, 3, 4, 5, 6, 7, 9, 10)`:

| Role order | Roles | _view | _add / _edit / _delete |
|---|---|---|---|
| `< 5` | Group Administrator (1), Administrator (2), District Officer (3), Principal (4) | 1 | 1 |
| `>= 5` | Homeroom Teacher (5), Teacher (6), Staff (7), Guardian (9), Superrole (10) | 1 | 0 |

Superrole/super_admin JWTs bypass `PermissionService::checkPermission()` entirely, so the row above is informational only for that role.

### 3.6 Files Changed Summary

| Change | File | Purpose |
|---|---|---|
| Added | `config/Migrations/20260511120000_POCOR9610.php` | Schema + permission rows |
| Added | `plugins/Institution/src/Model/Table/InstitutionRegistrationsTable.php` | CakePHP Table (read-only) |
| Added | `plugins/Institution/src/Model/Table/InstitutionAccreditationsTable.php` | CakePHP Table (read-only) |
| Modified | `plugins/Institution/src/Controller/InstitutionsController.php` | `Registrations()` / `Accreditations()` actions |
| Modified | `src/Controller/Component/NavigationComponent.php` | Two new sidebar entries under General |
| Added | `api/app/Models/Api5/InstitutionRegistrations.php` | Eloquent model |
| Added | `api/app/Models/Api5/InstitutionAccreditations.php` | Eloquent model |
| Added | `api/database/factories/InstitutionRegistrationsFactory.php` | Test factory |
| Added | `api/database/factories/InstitutionAccreditationsFactory.php` | Test factory |
| Added | `api/tests/Feature/InstitutionRegistrationsApiTest.php` | Feature test — 5/5 pass |
| Added | `api/tests/Feature/InstitutionAccreditationsApiTest.php` | Feature test — 5/5 pass |

### 3.7 Database Migration

`config/Migrations/20260511120000_POCOR9610.php` — class `POCOR9610`.

**Backup tables created** (idempotent, only if not already present):
- `z_9610_security_functions`
- `z_9610_security_role_functions`
- `z_9610_institution_registrations` *(only on the dead-branch path where the table already exists at migrate time)*
- `z_9610_institution_accreditations` *(same)*

**Rows created in `up()`:**
- `institution_registrations` table.
- `institution_accreditations` table.
- 2 `security_functions` rows for the UI tabs.
- 2 `security_functions` rows for the API endpoints.
- 10 `security_role_functions` rows for UI tab access (5 roles × 2 tabs).
- 18 `security_role_functions` rows for API access (9 roles × 2 endpoints).

The `down()` method restores `security_functions` and `security_role_functions` from their backups, and drops both new tables.

---

## 4. Deployment Instructions

1. **Pull the branch.**

    ```bash
    git fetch origin POCOR-9610
    git checkout POCOR-9610
    ```

2. **Run the migration.**

    ```bash
    cd /var/www/html/emis/core
    php bin/cake.php migrations migrate
    ```

3. **Install API dependencies** *(no new packages, safe to re-run)*.

    ```bash
    cd /var/www/html/emis/core/api
    composer install
    ```

4. **Clear caches.**

    ```bash
    cd /var/www/html/emis/core
    php bin/cake.php cache clear_all

    cd /var/www/html/emis/core/api
    php artisan config:cache
    php artisan cache:clear
    php artisan route:clear
    ```

5. **Verify UI.** Open any institution profile and confirm that under **General** the **Registrations** and **Accreditations** tabs are visible and render read-only (no Add / Edit / Delete buttons).

6. **Smoke-test the API** with curl or the Postman collection:

    ```bash
    # 1. Login (api_key is environment-specific; the seeded test value is 'apikeytest')
    TOKEN=$(curl -ks -X POST https://<host>/core/api/v4/login \
      -H "Content-Type: application/json" \
      -d '{"username":"admin","password":"<pwd>","api_key":"<key>"}' \
      | jq -r .data.token)

    # 2. List
    curl -ks -H "Authorization: Bearer $TOKEN" https://<host>/core/api/v5/institution-registrations  | jq '.data | length'
    curl -ks -H "Authorization: Bearer $TOKEN" https://<host>/core/api/v5/institution-accreditations | jq '.data | length'

    # 3. Create (registration)
    curl -ks -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
      -d '{"institution_id":1,"valid_from":"2026-01-01","valid_to":null}' \
      https://<host>/core/api/v5/institution-registrations | jq
    ```

    > **Optional — developer-only interactive seed page.** A standalone HTML page that drives the same endpoints from a browser form (login + list/create/delete for both resources) is attached to the Jira ticket as `pocor-9610-seed.html`. Save it locally and open the file directly in your browser (`file:///...`); fill in the API base URL + `api_key` at the top, then login. **Do not deploy this page to any institution-facing server** — it is a developer convenience only, not part of the shipped product.

> **Rollback** is intentionally not provided here. `security_*` migrations are not rolled back as a matter of policy on shared environments; if a rollback is genuinely required, follow the per-branch security-users cleanup procedure in `.claude/rules/migration-rollback.md` first.

---

## 5. System Administrator Guide

### 5.1 Log Locations

- CakePHP error log: `/var/www/html/emis/core/logs/hin-error.log`
- CakePHP debug log: `/var/www/html/emis/core/logs/hin-debug.log`
- Laravel log: `/var/www/html/emis/core/api/storage/logs/laravel.log`

### 5.2 Granting UI Access to a New Role

To allow a role to **see** the Registrations / Accreditations tabs:

1. Log in as Administrator and navigate to **Security → Roles → \<role\>**.
2. Open the **Institutions** module.
3. Under **General**, tick **View** for `Registrations` and/or `Accreditations`.
4. Save. The tabs will appear on the next page load for users of that role.

The UI is read-only by design — there are no Add / Edit / Delete checkboxes for these two rows.

### 5.3 Granting API Access to a New Role

To allow a role to **call** the `/api/v5/institution-registrations` or `/api/v5/institution-accreditations` endpoints:

1. Navigate to **Security → Roles → \<role\>**.
2. Open the **API** module.
3. Locate `Institution Registrations` and `Institution Accreditations`.
4. Tick **View** for read access, and any of **Add / Edit / Delete** for write access.
5. Save.

Roles with `order < 5` (Group Administrator, Administrator, District Officer, Principal) already have full write access by default. Lower-order roles get view-only and must be elevated explicitly.

### 5.4 Status Field

The **Status** column on both tabs is computed (not stored):

- `Valid` — `valid_to IS NULL` or `valid_to >= today`.
- `Expired` — `valid_to < today`.

This logic also drives the Excel export, so no further configuration is required.

### 5.5 Troubleshooting

| Symptom | Check |
|---|---|
| Tabs not visible | Confirm `NavigationComponent.php` entries are present; confirm `security_functions` and `security_role_functions` rows exist for the role |
| Tab visible but page 404 | Confirm the controller has `Registrations()` / `Accreditations()` methods and the Table classes are present under `plugins/Institution/src/Model/Table/` |
| API returns `Invalid resource` | Confirm the resource keys `institution-registrations` and `institution-accreditations` are listed in `CrudApiController::$allowedResources` |
| API returns 403 | Confirm the calling user's role has the matching `security_role_functions` row with `_view` (and `_add`/`_edit`/`_delete` for write) |
| Programme Code / Name blank in Accreditations index | `indexBeforeQuery` must use closure-style `contain()` with explicit `->select()`; otherwise `ControllerActionTable::beforeFind` strips non-default FK fields |
| Excel dates blank | `onExcelUpdateFields` must force `'type' => 'string'` on date fields |
| Add / Edit / Delete buttons still visible | Both `toggle('add/edit/remove', false)` AND `HideButtonBehavior` must be present in the Table class |
