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
3. **Per-grade allow/block matrix** — New "Student Creation Rules List" under System Configurations → Add New Student. Each education grade has an `allow_student_creation` toggle (default: allowed)
4. **Enforcement** — When the toggle is Enabled and the requesting user is not in an excluded role, all four entry points check the per-grade flag before proceeding

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
Does student_creation_rules.allow_student_creation == 1 for this grade?
  → Yes → ALLOW
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
| `config/Migrations/20260414120000_POCOR9385.php` | New migration |
| `plugins/Institution/src/Model/Traits/StudentCreationCheckTrait.php` | New trait |
| `plugins/Institution/src/Model/Table/StudentsTable.php` | Added `addBeforeSave()` enforcement |
| `plugins/Institution/src/Model/Table/ImportStudentAdmissionTable.php` | Added row-level enforcement in `onImportModelSpecificValidation` |
| `plugins/Directory/src/Model/Table/DirectoriesTable.php` | Added enforcement in `beforeSave` |
| `plugins/Directory/src/Model/Table/ImportUsersTable.php` | Added blanket + grade-aware enforcement |
| `plugins/Configuration/src/Model/Table/StudentCreationRulesTable.php` | New grade matrix table |
| `plugins/Configuration/src/Controller/ConfigurationsController.php` | Registered `StudentCreationRulesList` action |

### Database Migrations

**New table:** `student_creation_rules`

```sql
CREATE TABLE `student_creation_rules` (
  `id`                     int          NOT NULL AUTO_INCREMENT,
  `education_grade_id`     int          NOT NULL,
  `allow_student_creation` tinyint(1)   NOT NULL DEFAULT 1,
  `modified_user_id`       int          DEFAULT NULL,
  `modified`               datetime     DEFAULT NULL,
  `created_user_id`        int          NOT NULL,
  `created`                datetime     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_scr_grade` (`education_grade_id`),
  CONSTRAINT `fk_scr_grade` FOREIGN KEY (`education_grade_id`)
    REFERENCES `education_grades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Seeded with one row per `education_grades` record (`allow_student_creation = 1` — all allowed by default).

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

### Configuring Per-Grade Rules

1. Navigate to **Administration → System Configurations → Student Creation Rules List**
2. The table shows all education grades grouped by programme
3. Click **Edit** on any grade to toggle `allow_student_creation` between Yes and No
4. Set **No** for grades that should NOT allow new student creation (e.g. all except Grade 1)

### Effect on Users

When the feature is **Enabled**:
- Users in an excluded role can always create students regardless of grade rules
- All other users will see a validation error if they attempt to add or import a student for a restricted grade
- The error message identifies the blocked grade and instructs users to search for an existing student

When the feature is **Disabled** (default):
- No restrictions apply — all grades allow student creation as before
