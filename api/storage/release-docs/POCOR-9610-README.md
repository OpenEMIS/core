# POCOR-9610 — Core Registrations and Accreditations API Foundation

---

## 1. What is the Task?

POCOR-9610 is the **OpenEMIS Core-side integration slice** for the future external OpenEMIS Accreditations application. Core must be able to store institution registration state, retain registration history, retain programme accreditation records, and expose those records through the existing `/api/v5/*` Laravel CRUD layer.

This implementation does **not** build the external Accreditations app and does **not** yet add the CakePHP institution-profile tabs. It delivers the schema and v5 API foundation required for that integration.

---

## 2. Situation Before

- `institutions` had no accreditation-sync key like `external_id`.
- `institutions` had no dedicated registration status or expiry fields for the external service.
- Core had no `institution_registrations` history table.
- Core had no `institution_accreditations` table for programme-level accreditation records.
- `/api/v5/*` had no `institution-registrations` or `institution-accreditations` resources.
- `/api/v5/institutions/{id}` could not persist the new external integration fields.

---

## 3. What Was Implemented

### Schema

Added migration:
- [20260409090000_POCOR9610.php](/Users/khindol/PhpstormProjects/emis/emis/core/config/Migrations/20260409090000_POCOR9610.php)

This migration:
- adds `external_id`, `registration_status`, `registration_valid_until` to `institutions`
- creates `institution_registrations`
- creates `institution_accreditations`
- adds index `idx_institutions_external_id`
- adds foreign keys to `institutions` and `security_users`

### API v5 Resources

Added new `Api5` models:
- [InstitutionRegistrations.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/app/Models/Api5/InstitutionRegistrations.php)
- [InstitutionAccreditations.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/app/Models/Api5/InstitutionAccreditations.php)

Updated existing model:
- [Institutions.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/app/Models/Api5/Institutions.php)

Added generic v5 route registration entries:
- [CrudApiController.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/app/Http/Controllers/BaseApi/CrudApiController.php)

This enables these endpoints through the existing catch-all `/api/v5/{resource}` CRUD pipeline:
- `GET/POST/PUT/DELETE /api/v5/institution-registrations`
- `GET/POST/PUT/DELETE /api/v5/institution-accreditations`
- updated `POST/PUT /api/v5/institutions`

### Swagger Documentation

Swagger-style model annotations were added for:
- `InstitutionRegistrations`
- `InstitutionAccreditations`
- new fields on `Institutions`

### Factories

Added:
- [InstitutionRegistrationsFactory.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/database/factories/InstitutionRegistrationsFactory.php)
- [InstitutionAccreditationsFactory.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/database/factories/InstitutionAccreditationsFactory.php)

Updated:
- [InstitutionsFactory.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/database/factories/InstitutionsFactory.php)

Note:
- explicit `newFactory()` methods were added to the affected `Api5` models because this repo resolves `App\Models\Api5\...` factories differently from flat `Database\Factories\...` naming.
- a stale `fax` field was removed from `InstitutionsFactory` because the current `institutions` table no longer has that column, and it was blocking Docker test execution.

### Feature Tests

Added:
- [InstitutionRegistrationsApiTest.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/tests/Feature/InstitutionRegistrationsApiTest.php)
- [InstitutionAccreditationsApiTest.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/tests/Feature/InstitutionAccreditationsApiTest.php)

Updated:
- [InstitutionsApiTest.php](/Users/khindol/PhpstormProjects/emis/emis/core/api/tests/Feature/InstitutionsApiTest.php)

The updated institutions test verifies:
- `external_id`
- `registration_status`
- `registration_valid_until`

### Files Changed Summary

| Change | File |
|--------|------|
| Added | `config/Migrations/20260409090000_POCOR9610.php` |
| Added | `api/app/Models/Api5/InstitutionRegistrations.php` |
| Added | `api/app/Models/Api5/InstitutionAccreditations.php` |
| Modified | `api/app/Models/Api5/Institutions.php` |
| Modified | `api/app/Http/Controllers/BaseApi/CrudApiController.php` |
| Added | `api/database/factories/InstitutionRegistrationsFactory.php` |
| Added | `api/database/factories/InstitutionAccreditationsFactory.php` |
| Modified | `api/database/factories/InstitutionsFactory.php` |
| Added | `api/tests/Feature/InstitutionRegistrationsApiTest.php` |
| Added | `api/tests/Feature/InstitutionAccreditationsApiTest.php` |
| Modified | `api/tests/Feature/InstitutionsApiTest.php` |
| Added | `api/storage/release-docs/POCOR-9610-README.md` |

**Files Added:** 7  |  **Files Modified:** 4  |  **Files Removed:** 0

---

## 4. Deployment Instructions

### Apply schema

Run inside Docker:

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core && ./bin/cake migrations migrate'
```

### Run API tests inside Docker

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core/api && php artisan test tests/Feature/InstitutionRegistrationsApiTest.php'
docker exec poe-application sh -c 'cd /var/www/html/emis/core/api && php artisan test tests/Feature/InstitutionAccreditationsApiTest.php'
docker exec poe-application sh -c 'cd /var/www/html/emis/core/api && php artisan test tests/Feature/InstitutionsApiTest.php'
```

### Smoke test the new endpoints

1. Authenticate and obtain a JWT token from `/core/api/v5/login`.
2. Create a registration row:
   ```json
   POST /api/v5/institution-registrations
   {
     "institution_id": 6,
     "external_id": "ACC-INS-000123",
     "status": "active",
     "approved_date": "2024-01-01",
     "valid_from": "2024-01-01",
     "valid_to": "2027-01-01",
     "decision_reference": "DEC-2024-001"
   }
   ```
3. Create an accreditation row:
   ```json
   POST /api/v5/institution-accreditations
   {
     "institution_id": 6,
     "programme_name": "Diploma Electrical",
     "programme_code": "DE-101",
     "qualification_level": "Diploma",
     "status": "active",
     "valid_from": "2024-01-01",
     "valid_to": "2027-01-01",
     "external_id": "ACC-PROG-000123"
   }
   ```
4. Update the institution:
   ```json
   PUT /api/v5/institutions/{id}
   {
     "external_id": "ACC-INS-000123",
     "registration_status": "active",
     "registration_valid_until": "2027-01-01"
   }
   ```
5. Verify via:
   - `GET /api/v5/institution-registrations?institution_id=6`
   - `GET /api/v5/institution-accreditations?institution_id=6`
   - `GET /api/v5/institutions/6`

---

## 5. System Administrator Guide

### Log Locations

- CakePHP logs: `/var/www/html/emis/core/logs/`
- Laravel logs: `/var/www/html/emis/core/api/storage/logs/laravel.log`

### Configuration

No new configuration toggles were introduced.

### Rollback Procedure

Schema rollback:

```bash
docker exec poe-application sh -c 'cd /var/www/html/emis/core && ./bin/cake migrations rollback -t 20260312062543'
```

Code rollback:

```bash
git revert <commit-hash>
```

### Troubleshooting

| Symptom | Check |
|---------|-------|
| `Invalid resource` on `/api/v5/institution-registrations` | Confirm the resource key was added to `BaseApi/CrudApiController::$allowedResources` |
| Factory-related test failure for `App\Models\Api5\...` | Confirm the model defines explicit `newFactory()` returning the flat `Database\Factories\...Factory` |
| Migration applies but tests fail on old schema fields | Check that local factories still match the live DB schema |
| Institutions update succeeds but new fields are not saved | Confirm the fields are present in `Api5\Institutions::$fillable` |
| Endpoint returns `403 Forbidden` | Verify the authenticated user has permission through the existing v5 permission model, or use a super admin account for smoke testing |

---

## 6. Out of Scope / Still Pending

This release document covers the API foundation only.

Still pending for full ticket completion:
- CakePHP institution profile tabs for `Registrations` and `Accreditations`
- read-only tab rendering in the Institution plugin
- any `custom_modules` / `security_functions` UI permission wiring for those tabs
- end-to-end browser verification of those new tabs

