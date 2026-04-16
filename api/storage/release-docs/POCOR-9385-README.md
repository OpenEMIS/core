# POCOR-9385 — Student Creation Restriction by Grade

## 1. What is the Task?

Implement a system configuration that restricts student creation to specific programme+grade entry points. When enabled, only the lowest-order grade of each programme at the institution may create new students. Higher grades must search for and enrol existing students rather than creating new ones.

The restriction applies to four entry points:
- Institution Students → Add (Angular form)
- Institution Students → Import
- Directory → Add (when creating a student account)
- Directory → Import (when importing a row as a student)

A "Excluded Security Roles" bypass allows designated roles (e.g. administrators) to override the restriction regardless of grade rules. Super admins are always exempt.

---

## 2. Situation Before

There was no mechanism to restrict student creation by grade. Any user with access to the Add Student or Import Student screens could create new student records for any grade, regardless of whether that grade was an authorised entry point.

Additionally, a long-standing bug (POCOR-5672) caused AJAX endpoints in `InstitutionsController` to execute their full logic ~85 times per HTTP request, because controller action methods were incorrectly registered as `Controller.SecurityAuthorize.isActionIgnored` event handlers. This event fires once per row in `security_functions` (~85 rows).

---

## 3. What Was Implemented

### Feature Overview

1. **Global toggle** (`restrict_student_creation`) — Enabled/Disabled dropdown under System Configurations → Add New Student
2. **Excluded Security Roles** (`student_creation_excluded_roles`) — Multi-select of security roles that bypass the restriction entirely, under the same page
3. **Institution-aware entry grade** — A grade is considered an authorised entry point when it has the lowest `order` value among all grades of the same programme that the institution runs for the selected academic period. This is institution- and period-specific, not a global order=1 rule.
4. **Enforcement** — When the toggle is Enabled, and the requesting user is not a super admin and not in an excluded role, all four entry points check whether the target grade is the entry grade before proceeding
5. **Grade dropdown filtering** — The `getEducationGrade` AJAX endpoint filters the grade dropdown to show only entry grades when the restriction is active, so non-entry grades are never presented to restricted users
6. **Super admin bypass** — Users with `super_admin = 1` in `security_users` are always exempt from the restriction, regardless of config settings
7. **POCOR-5672 fix** — Removed 23 broken if-blocks from `InstitutionsController::implementedEvents()` that registered action methods as security event handlers. Consolidated into a single `isActionIgnored()` allowlist. This fixes ~85× over-execution of every AJAX endpoint on this controller.

### Logic Flow

```
Request to create student (or fetch grade dropdown)
  ↓
Is restrict_student_creation == 1 (Enabled)?
  → No  → ALLOW / show all grades (feature off)
  ↓ Yes
Is user a super admin (security_users.super_admin = 1)?
  → Yes → ALLOW / show all grades (super admin exempt)
  ↓ No
Is user in an excluded security role?
  → Yes → ALLOW / show all grades (role bypass)
  ↓ No
Is a grade ID available?
  → No  → BLOCK (no grade context — blanket block)
  ↓ Yes
Is grade.order == MIN(order) for this programme at this institution+period?
  → Yes → ALLOW (entry grade)
  → No  → BLOCK with message
```

### Block Messages

**With grade context:**
> Student creation is not permitted for {Grade Name}. Only authorised entry grades may create new students. Please search for an existing student instead.

**Without grade context (Directory):**
> Student creation is currently restricted. Only authorised entry grades may create new students. Please search for an existing student instead.

### Files Changed

| File | Change |
|------|--------|
| `config/Migrations/20260414120000_POCOR9385.php` | New migration — two `config_items` rows + options, no new tables |
| `plugins/Institution/src/Model/Traits/StudentCreationCheckTrait.php` | New shared enforcement trait with super_admin bypass, institution-aware entry-grade check |
| `plugins/Institution/src/Model/Table/StudentsTable.php` | Added `addBeforeSave()` enforcement via trait |
| `plugins/Institution/src/Model/Table/ImportStudentAdmissionTable.php` | Added row-level enforcement in `onImportModelSpecificValidation` |
| `plugins/Directory/src/Model/Table/DirectoriesTable.php` | Added enforcement in `beforeSave` |
| `plugins/Directory/src/Model/Table/ImportUsersTable.php` | Added blanket + grade-aware enforcement |
| `plugins/Institution/src/Controller/InstitutionsController.php` | Grade dropdown filtering in `getEducationGrade`; super_admin bypass in `saveStudentData`; POCOR-5672 `implementedEvents` fix |
| `plugins/Configuration/src/Controller/ConfigurationsController.php` | No changes — config items appear automatically on existing Add New Student page |

### Database Migrations

No new tables. The entry-grade rule is calculated from the existing `education_grades.order` column — the lowest-order grade per programme at the institution for the academic period.

**New `config_items` rows:**

| id | code | name | type | field_type | default_value |
|----|------|------|------|-----------|---------------|
| 1357 | `restrict_student_creation` | Limit student addition to first grade only | Add New Student | Dropdown | `0` |
| 1358 | `student_creation_excluded_roles` | Excluded Security Roles for Student Creation | Add New Student | chosenSelect | `` |

**New `config_item_options` rows** (option_type = `student_creation_toggle`):

| option | value | order |
|--------|-------|-------|
| Disabled | 0 | 1 |
| Enabled | 1 | 2 |

---

## 4. Deployment Instructions

1. Pull the `POCOR-9385` branch
2. Run the migration:
   ```bash
   cd /var/www/html/emis/core
   php bin/cake.php migrations migrate
   ```
3. Clear application cache:
   ```bash
   php bin/cake.php cache clear_all
   ```
4. Verify migration status:
   ```bash
   php bin/cake.php migrations status
   ```
   The migration `20260414120000_POCOR9385` should show status `up`.

---

## 5. System Administrator Guide

### Enabling the Feature

1. Navigate to **Administration → System Configurations → Add New Student**
2. Find **"Limit student addition to first grade only"** and set to **Enabled**
3. Optionally set **"Excluded Security Roles for Student Creation"** — select any roles whose users should bypass the restriction (e.g. Principal, Head Teacher)
4. Save

> **Note:** Super admins (`super_admin = 1`) are always exempt and do not need to be added to the excluded roles list.

### Entry Grade Determination

No manual configuration of individual grades is required. The system automatically determines the entry grade as the grade with the lowest `order` value among all grades of the same education programme that the institution runs for the selected academic period.

If an institution runs Primary 1–6 (orders 1–6) for a given year, Primary 1 is the entry grade. If a separate institution only runs Primary 4–6 for that year, Primary 4 is the entry grade for that institution.

### Effect on Users

When the feature is **Enabled**:
- Super admins always see all grades and can create students at any grade
- Users in an excluded role always see all grades and can create students at any grade
- All other users see only the entry grade(s) in the grade dropdown on the Add Student form
- Attempts to save a non-entry grade (e.g. via import) are blocked with a validation error identifying the grade
- The error message instructs users to search for an existing student instead

When the feature is **Disabled** (default):
- No restrictions apply — all grades allow student creation as before
