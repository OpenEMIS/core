# POCOR-8898: Report Card Archiving — Bug Fixes & Enhancements

## What is the Task?

Fix three bugs in the Report Card Archiving system and add enhancements:

1. **Bug 1**: Academic period filter shows only periods with active records for the current school; always includes current editable period even if empty
2. **Bug 2**: Archive button visible for any role with proper permission (not just superrole)
3. **Bug 3**: Archive filters replaced raw SQL with safe CakePHP ORM queries
4. **Enhancement**: Year becomes read-only when ALL 4 archive types are completed (Student Report Cards, Student Assessments, Student Attendances, Staff Attendances)
5. **Enhancement**: Archive commands warn operators when archiving will make a year non-editable (3/4 already archived — next one locks it)

## Situation Before

- Archive view displayed all academic periods regardless of active records, confusing operators
- Archive button missing from toolbar due to wrong `_view` route in `security_functions`
- Archive filters used raw SQL — brittle and unsafe
- Year could become read-only after only 3 of 4 archive operations, inconsistently
- No warning when archiving would lock a year

## What Was Implemented

### Files Changed Summary

| File | Changes |
|------|---------|
| `config/Migrations/20260331160000_POCOR8898.php` | Backs up `institution_students_report_cards` + `security_functions`; fixes `_view` route |
| `plugins/Institution/src/Model/Table/ReportCardStatusesTable.php` | Smart academic period filter — active records for this school + always include current editable period |
| `plugins/Institution/src/Model/Table/StudentsReportCardsArchivesTable.php` | Replaced raw SQL with ORM: `getAcademicPeriods()`, `getReportCardIds()`, `getInstitutionClassIds()` |
| `src/Controller/Component/AccessControlComponent.php` | Removed debug logging added during development |
| `src/Service/ArchiveService.php` | New `checkArchiveCompletionStatus()` — checks 4/4 archive completion, returns warning at 3/4 |
| `src/Command/ArchiveCommandBase.php` | Calls `checkArchiveCompletionStatus()` after archiving; logs alert to TransferLogs when year will become read-only |
| `plugins/Archive/src/Model/Table/TransferLogsTable.php` | New `logArchiveCompletionAlert()` — appends alert to transfer log notes |
| `plugins/Institution/src/Controller/InstitutionsController.php` | CakePHP 5 port |
| `plugins/Institution/src/Model/Table/InstitutionStudentsReportCardsArchivedTable.php` | New archive table model |
| `src/Command/Archive*Command.php` (×4) | CakePHP 5 commands replacing Shells |
| `src/Shell/ArchiveStudentReportCardsShell.php` | Legacy shell retained for backward compatibility |
| `src/Controller/Component/NavigationComponent.php` | Navigation entries for archive feature |

### Database Migrations

**Migration File**: `config/Migrations/20260331160000_POCOR8898.php`

**Tables backed up by migration:**
- `institution_students_report_cards` → `z_8898_institution_students_report_cards`
- `security_functions` → `z_8898_security_functions`

**Tables modified:**
- `security_functions` — one row updated: `_view` corrected from `'ReportCardArchives.index'` to `'ReportCardStatuses.index'`

**Migration is idempotent** — safe for up → down → up testing via `hasTable()` guards.

### ⚠️ Migration Limitations — Manual Restoration Required

The following tables are **NOT included in the migration backup** and are **NOT restored on rollback**:

#### `transfer_logs`
- Archive operations write entries to `transfer_logs` during execution
- Rolling back the migration does **not** remove these log entries
- If rollback is needed, a sysadmin must manually clean up:
  ```sql
  -- Review entries first
  SELECT * FROM transfer_logs WHERE academic_period_id = <period_id> ORDER BY generated_on DESC;

  -- Then delete if appropriate
  DELETE FROM transfer_logs WHERE academic_period_id = <period_id>;
  ```

#### `academic_periods` (`editable` field)
- When ALL 4 archive types are completed for a period, that year becomes read-only (`editable = 0`) in `academic_periods`
- This change is made by the archive commands at runtime — **not by the migration**
- Rolling back the migration does **not** restore `editable = 1`
- If a year was locked and rollback is needed, a sysadmin must manually restore:
  ```sql
  -- Check current state
  SELECT id, name, editable FROM academic_periods WHERE id = <period_id>;

  -- Restore editable if needed
  UPDATE academic_periods SET editable = 1 WHERE id = <period_id>;
  ```

**When this matters**: If archiving was run (locking a year or writing transfer logs) and then the migration is rolled back, the system will be in an inconsistent state. The sysadmin must decide whether to restore these values based on the operational situation.

### Read-Only Year Logic

A year becomes **read-only** (`academic_periods.editable = 0`) only when ALL 4 of these archive types are completed:

| # | Archive Type | Table |
|---|-------------|-------|
| 1 | Student Report Cards | `institution_students_report_cards` |
| 2 | Student Assessments | `assessment_item_results` |
| 3 | Student Attendances | `student_attendance_marked_records` |
| 4 | Staff Attendances | `institution_staff_attendances` |

At **3/4 completed**, operators see a warning in the archive command output and in `transfer_logs.notes`:
> ⚠️ WARNING: Archiving this will make year YYYY READ-ONLY (all 4 archive types will be completed)

### Academic Period Filter Logic

The Report Card Statuses index filter now shows:
- Academic periods that have **at least 1 active record** in the current school
- **Always includes the current academic period** if it is editable (`editable = 1`), even if no records exist yet
- Periods where ALL 4 archives are complete do **not** appear (no active records remain)

## Deployment Instructions

### Step 1: Run Migration

```bash
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations migrate"

# Verify
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations status 2>&1 | tail -5"
```

### Step 2: Verify Security Function

```sql
SELECT id, title, _view
FROM security_functions
WHERE title LIKE '%Student Report Card Archive%';
```

Expected: `_view = 'ReportCardStatuses.index'`

### Step 3: Assign Permissions (if needed)

1. Log in as sysadmin
2. Navigate to **Settings > Security > Roles**
3. Select the role that needs archive access
4. Enable: **Student Report Card Archive**
5. Save

### Step 4: Clear User Cache

Users must log out and back in for permissions to rebuild in session.

## System Administrator Guide

### Troubleshooting: Archive Button Not Visible

1. Check `security_functions._view`:
   ```sql
   SELECT id, title, _view FROM security_functions
   WHERE title LIKE '%Student Report Card Archive%';
   ```
   If `_view = 'ReportCardArchives.index'` — migration has not run. Run it.

2. Check role has permission:
   ```sql
   SELECT sf.title, r.name
   FROM security_functions sf
   JOIN security_permissions sp ON sf.id = sp.security_function_id
   JOIN security_roles r ON sp.security_role_id = r.id
   WHERE sf.title LIKE '%Student Report Card Archive%';
   ```
   If no rows — assign permission via **Settings > Security > Roles**.

### Troubleshooting: Year Unexpectedly Read-Only

Check how many archive types are completed:
```sql
SELECT 'report_cards' AS type, COUNT(*) FROM institution_students_report_cards_archived WHERE academic_period_id = <id>
UNION ALL
SELECT 'assessments', COUNT(*) FROM assessment_item_results_archived WHERE academic_period_id = <id>
UNION ALL
SELECT 'student_attendance', COUNT(*) FROM student_attendance_marked_records_archived WHERE academic_period_id = <id>
UNION ALL
SELECT 'staff_attendance', COUNT(*) FROM institution_staff_attendances_archived WHERE academic_period_id = <id>;
```

If all 4 have records, the year is correctly locked. To unlock manually:
```sql
UPDATE academic_periods SET editable = 1 WHERE id = <period_id>;
```

### Rollback Instructions

```bash
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations rollback -t 20260330000000"
```

**What the rollback restores:**
- `institution_students_report_cards` — from backup `z_8898_institution_students_report_cards`
- `security_functions` — from backup `z_8898_security_functions`

**What the rollback does NOT restore (manual action required):**
- `transfer_logs` entries written during archive operations — delete manually if needed
- `academic_periods.editable = 0` set when year was locked — restore manually if needed (see SQL above)

---

**Released**: 2026-04-02
**Branch**: POCOR-8898
**Related Issues**: POCOR-8898 Bugs 1, 2, 3 + Enhancements
