# POCOR-9385 — Student Creation Restriction by Grade

## 1. What is the Task?

Implement a system configuration that restricts student creation to specific programme+grade entry points. When enabled, only grades marked as "allowed" may create new students. Higher grades must search for and enrol existing students rather than creating new ones.

The restriction applies to four entry points:
- Institution Students → Add
- Institution Students → Import
- Directory → Add (when creating a student account)
- Directory → Import (when importing a row as a student)

A "Excluded Security Roles" bypass allows designated roles (e.g. administrators) to override the restriction regardless of grade rules.

---

## 2. Situation Before

There was no mechanism to restrict student creation by grade. Any user with access to the Add Student or Import Student screens could create new student records for any grade, regardless of whether that grade was an authorised entry point.

---

## 3. What Was Implemented

### Feature Overview

1. **Global toggle** (`restrict_student_creation`) — Enabled/Disabled dropdown under System Configurations → Add New Student
2. **Excluded Security Roles** (`student_creation_excluded_roles`) — Multi-select of security roles that bypass the restriction entirely, under the same page
3. **Calculated entry grade** — A grade is considered an authorised entry point when `education_grades.order = 1` (the first grade within its programme). No separate configuration table is needed; the rule is derived automatically.
4. **Enforcement** — When the toggle is Enabled and the requesting user is not in an excluded role, all four entry points check whether the target grade has `order = 1` before proceeding

> **Important limitation:** The entry-grade calculation relies on `education_grades.order` being set correctly. If a programme's grade records are manually reordered and no grade has `order = 1`, the restriction will block student creation for all grades in that programme. Administrators should ensure that every education programme has exactly one grade with `order = 1`.

### Logic Flow

```
Request to create student
  ↓
Is restrict_student_creation == 1 (Enabled)?
  → No  → ALLOW (feature off)
  ↓ Yes
Is user in an excluded security role?
  → Yes → ALLOW (role bypass)
  ↓ No
Is a grade ID available?
  → No  → BLOCK (no grade context — blanket block)
  ↓ Yes
Does education_grades.order == 1 for this grade?
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
| `plugins/Institution/src/Model/Traits/StudentCreationCheckTrait.php` | New shared enforcement trait |
| `plugins/Institution/src/Model/Table/StudentsTable.php` | Added `addBeforeSave()` enforcement |
| `plugins/Institution/src/Model/Table/ImportStudentAdmissionTable.php` | Added row-level enforcement in `onImportModelSpecificValidation` |
| `plugins/Directory/src/Model/Table/DirectoriesTable.php` | Added enforcement in `beforeSave` |
| `plugins/Directory/src/Model/Table/ImportUsersTable.php` | Added blanket + grade-aware enforcement |
| `plugins/Configuration/src/Controller/ConfigurationsController.php` | No changes — config items appear automatically on existing Add New Student page |

### Database Migrations

No new tables. The entry-grade rule is calculated from the existing `education_grades.order` column (`order = 1` = authorised entry grade).

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
3. Optionally set **"Excluded Security Roles for Student Creation"** — select any roles whose users should bypass the restriction (e.g. Super Admins)
4. Save

### Entry Grade Determination

No manual configuration of individual grades is required. A grade is automatically treated as an authorised entry point when its `order` field equals `1` in the `education_grades` table (i.e. the first grade in its programme).

> **Warning:** If a programme's grade `order` values are manually edited and no grade has `order = 1`, student creation will be blocked for all grades in that programme. Always ensure each programme has exactly one grade with `order = 1`.

### Effect on Users

When the feature is **Enabled**:
- Users in an excluded role can always create students regardless of grade rules
- All other users will see a validation error if they attempt to add or import a student for a restricted grade
- The error message identifies the blocked grade and instructs users to search for an existing student

When the feature is **Disabled** (default):
- No restrictions apply — all grades allow student creation as before
