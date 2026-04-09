# POCOR-9610 — Institution Registrations and Accreditations Tabs

---

## 1. What is the Task?

Add two new institution-profile tabs in OpenEMIS Core:

- **Registrations** — track institution registration validity periods (`valid_from`, `valid_to`)
- **Accreditations** — track programme-level accreditations, each linked to an education programme (`education_programme_id`), with validity dates

Both tabs appear in the Institution left sidebar under **General** as **read-only views** (data is pushed from OpenEMIS Accreditations via API). Excel export is supported. The `/api/v5/*` endpoints expose read and write access with role-based permissions.

---

## 2. Situation Before

- No `institution_registrations` or `institution_accreditations` tables existed.
- No CakePHP tabs or controllers existed for these two concepts.
- No Laravel API resources existed for these models.
- The Institution left sidebar had no entries for Registrations or Accreditations.

---

## 3. What Was Implemented

### Schema

Migration file: `config/Migrations/20260409090000_POCOR9610.php`

**`institution_registrations`**
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `institution_id` | INT NOT NULL FK→institutions | |
| `valid_from` | DATE NOT NULL | Defaults to institution `date_opened` if left empty on entry |
| `valid_to` | DATE NULL | Optional end date |
| `modified_user_id` | INT NULL FK→security_users | |
| `modified` | DATETIME NULL | |
| `created_user_id` | INT NOT NULL FK→security_users | |
| `created` | DATETIME NOT NULL | |

**`institution_accreditations`**
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `institution_id` | INT NOT NULL FK→institutions | |
| `education_programme_id` | INT NOT NULL FK→education_programmes | Programme being accredited |
| `valid_from` | DATE NOT NULL | Defaults to institution `date_opened` if left empty on entry |
| `valid_to` | DATE NULL | Optional end date |
| `modified_user_id` | INT NULL FK→security_users | |
| `modified` | DATETIME NULL | |
| `created_user_id` | INT NOT NULL FK→security_users | |
| `created` | DATETIME NOT NULL | |

The migration also inserts `security_functions` rows for both tabs (order 52 and 53, parent `General`, module/controller `Institutions`) with the following permission model:

**CakePHP UI:** Read-only for all roles — Add/Edit/Delete buttons are disabled in the table class (`toggle('add/edit/remove', false)`).

**API v5:** Controlled via `PermissionService::checkPermission()` which reads `security_role_functions` joined against `security_functions` action strings. The `security_functions._view/_add/_edit/_delete` columns contain both CakePHP action names (e.g. `Registrations.index`) and API model names (e.g. `InstitutionRegistrations.view`) so both systems resolve permissions from the same rows.

| Role | Order | UI | API view | API write |
|------|-------|----|----------|-----------|
| Superrole | 1 | view only | ✓ (super_admin bypass) | ✓ (super_admin bypass) |
| Administrator | 2 | view only | ✓ | ✓ |
| Group Administrator | 3 | view only | ✓ | ✓ |
| District Officer | 4 | view only | ✓ | ✓ |
| Principal | 5 | view only | ✓ | ✗ |
| Teacher / Staff / etc. | 7+ | view only | ✓ (where _view=1) | ✗ |

#### valid_from default logic

`valid_from` is `NOT NULL` in the database. If a user leaves it blank on the add/edit form, the system automatically uses the institution's `date_opened` as the start date — this is handled in `beforeSave()` in both CakePHP Table classes, and the factories also derive it from `institution.date_opened`.

### CakePHP UI

**New Table classes:**
- `plugins/Institution/src/Model/Table/InstitutionRegistrationsTable.php`
- `plugins/Institution/src/Model/Table/InstitutionAccreditationsTable.php`

Both use `ControllerActionTable`, `Excel` behavior (index export), and `Institution.InstitutionTab` behavior.

- **Registrations index** shows: Valid From | Valid To | Actions
- **Accreditations index** shows: Programme Code | Programme Name | Valid From | Valid To | Actions (programme data from FK via `onGetProgrammeCode` / `onGetProgrammeName`)
- **Accreditations add/edit** shows a dropdown of visible education programmes

**Controller:**
- `plugins/Institution/src/Controller/InstitutionsController.php` — added `Registrations()` and `Accreditations()` action methods

**Sidebar navigation:**
- `src/Controller/Component/NavigationComponent.php` — added `Institutions.Registrations.index` and `Institutions.Accreditations.index` entries under `Institution.General` parent

### Laravel API v5

**Models:**
- `api/app/Models/Api5/InstitutionRegistrations.php`
- `api/app/Models/Api5/InstitutionAccreditations.php`

Both registered in `CrudApiController::$allowedResources` as `institution-registrations` and `institution-accreditations`.

**Factories:**
- `api/database/factories/InstitutionRegistrationsFactory.php` — `valid_from` derived from institution `date_opened`
- `api/database/factories/InstitutionAccreditationsFactory.php` — `valid_from` derived from institution `date_opened`

**Feature tests (all passing 5/5 each):**
- `api/tests/Feature/InstitutionRegistrationsApiTest.php`
- `api/tests/Feature/InstitutionAccreditationsApiTest.php`

### Files Changed Summary

| Change | File |
|--------|------|
| Added | `config/Migrations/20260409090000_POCOR9610.php` |
| Added | `plugins/Institution/src/Model/Table/InstitutionRegistrationsTable.php` |
| Added | `plugins/Institution/src/Model/Table/InstitutionAccreditationsTable.php` |
| Modified | `plugins/Institution/src/Controller/InstitutionsController.php` |
| Modified | `src/Controller/Component/NavigationComponent.php` |
| Added | `api/app/Models/Api5/InstitutionRegistrations.php` |
| Added | `api/app/Models/Api5/InstitutionAccreditations.php` |
| Added | `api/database/factories/InstitutionRegistrationsFactory.php` |
| Added | `api/database/factories/InstitutionAccreditationsFactory.php` |
| Added | `api/tests/Feature/InstitutionRegistrationsApiTest.php` |
| Added | `api/tests/Feature/InstitutionAccreditationsApiTest.php` |

**Files Added:** 9  |  **Files Modified:** 2

---

## 4. Deployment Instructions

### Apply schema

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core && ./bin/cake migrations migrate'
```

### Clear CakePHP cache

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core && ./bin/cake cache clear_all'
```

### Run API tests

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core/api && php artisan test tests/Feature/InstitutionRegistrationsApiTest.php tests/Feature/InstitutionAccreditationsApiTest.php'
```

### Smoke test via curl

Get a JWT token first, then:

```bash
# List registrations
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8082/api/v5/institution-registrations | jq .

# Create a registration (valid_from will auto-fill from institution date_opened if omitted)
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"institution_id":6,"valid_from":"2024-01-01","valid_to":"2026-12-31"}' \
  http://localhost:8082/api/v5/institution-registrations | jq .

# Create an accreditation
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"institution_id":6,"education_programme_id":1,"valid_from":"2024-01-01"}' \
  http://localhost:8082/api/v5/institution-accreditations | jq .
```

---

## 5. System Administrator Guide

### Log Locations

- CakePHP logs: `/var/www/html/emis/core/logs/hin-error.log`
- Laravel logs: `/var/www/html/emis/core/api/storage/logs/laravel.log`

### valid_from Default Behaviour

`valid_from` is stored as `DATE NOT NULL`. If a user submits a blank `valid_from` on the CakePHP add/edit form, the system automatically looks up the institution's `date_opened` and uses it as the start date. This prevents accreditation/registration records from appearing to start from the beginning of time.

The same logic applies in the Laravel API — if the factory creates a record, it uses `institution.date_opened` as the default.

### Rollback Procedure

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core && ./bin/cake migrations rollback -t <previous_migration_version>'
```

The `down()` method removes both tables, removes the two `security_functions` rows (and their `security_role_functions`), and restores the `institutions` table from the `z_9610_institutions` backup.

### Troubleshooting

| Symptom | Check |
|---------|-------|
| Tabs not visible in sidebar | Confirm `NavigationComponent.php` has the entries; confirm `security_functions` rows exist in DB |
| 404 after saving a record | Confirm `InstitutionTabBehavior` is attached; check that `institution_id` is in the queryString |
| `valid_from` saves as NULL | Should not happen — `beforeSave` sets it from `date_opened`; verify institution has `date_opened` set |
| API `Invalid resource` | Confirm the resource key is in `CrudApiController::$allowedResources` |
| Programme dropdown empty on add/edit | Confirm `education_programmes` table has rows with `visible=1` |
