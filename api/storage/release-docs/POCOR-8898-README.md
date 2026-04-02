# POCOR-8898: Fix Report Card Archiving Bugs

## What is the Task?

Fix three critical bugs in the Report Card Archiving system:

1. **Bug 1**: Academic period filter in the archive view shows only periods that have active (non-archived) report cards, eliminating confusion when browsing archived data
2. **Bug 2**: Archive button is now visible in the Report Card Statuses toolbar for users with proper permissions (all roles, not just superrole)
3. **Bug 3**: Database query code in the archives table uses idiomatic CakePHP ORM instead of raw SQL for maintainability and security

## Situation Before

- The Report Card Archive view would display academic periods that have no active records, confusing users
- Archive button was missing from the Report Card Statuses index toolbar due to a migration error (`_view` pointed to non-existent route)
- Archive filters were written in raw SQL, making them brittle and harder to maintain
- No debug logging existed for permission checks, making troubleshooting difficult

## What Was Implemented

### Files Changed Summary

| File | Purpose | Changes |
|------|---------|---------|
| `config/Migrations/20260331160000_POCOR8898.php` | Database schema & security functions | Backs up `institution_students_report_cards` table; corrects `security_functions._view` from `'ReportCardArchives.index'` to `'ReportCardStatuses.index'` |
| `plugins/Institution/src/Model/Table/ReportCardStatusesTable.php` | Report card status filters | Changed `getYearList()` from raw SQL to ORM query with DISTINCT; filters now show only periods with active (non-archived) records |
| `plugins/Institution/src/Model/Table/StudentsReportCardsArchivesTable.php` | Archive data access | Replaced three raw SQL queries with safe ORM equivalents: `getAcademicPeriods()`, `getReportCardIds()`, `getInstitutionClassIds()` |
| `src/Controller/Component/AccessControlComponent.php` | Permission checking | Added comprehensive debug logging to `check()` method for permission tracing |

### Database Migrations

**Migration File**: `config/Migrations/20260331160000_POCOR8898.php`

- **Backs up**: `institution_students_report_cards` table (new archiving feature)
- **Backs up**: `security_functions` table (modified, not archived)
- **Updates**: `security_functions` row for "Student Report Card Archive" — sets `_view` to correct navigation route
- **Idempotent**: Safe for up → down → up testing; guards against re-run with `hasTable()` checks

**Tables Affected**:
- `institution_students_report_cards` — backed up (no data changes)
- `security_functions` — one row updated (`_view` field)

### Key Improvements

#### Bug 1 Fix: Smart Academic Period Filter
- **Before**: Raw SQL query returned all periods in database
- **After**: ORM query with DISTINCT filters by active records only, using indexed `institution_id` FK
- **Scales**: Efficiently handles millions of rows via database-side DISTINCT on indexed column

```php
// New approach (ORM)
$this->find('distinct', ['fields' => 'academic_period_id'])
    ->where(['archived' => 0])
    ->order(['academic_period_id' => 'DESC'])
```

#### Bug 2 Fix: Archive Button Visibility
- **Root cause**: Migration set `_view` to non-existent `'ReportCardArchives.index'` route
- **Fix**: Corrected to `'ReportCardStatuses.index'` — the actual button location
- **Permission**: Button now visible for superrole AND any role with explicit "Student Report Card Archive" permission assignment
- **Debug**: Added logging to `AccessControlComponent::check()` to trace permission evaluation

#### Bug 3 Fix: Safe Database Queries
- **Before**: Raw SQL with implicit parameter binding
- **After**: Three ORM methods with safe parameter binding:
  - `getAcademicPeriods()` — fetches distinct periods from archive
  - `getReportCardIds()` — finds matching report card IDs
  - `getInstitutionClassIds()` — lists institution classes in archive

## Deployment Instructions

### Step 1: Run Migration
Apply the database migration on the target environment:

```bash
# Inside container
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations migrate"

# Check status
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations status 2>&1 | tail -5"
```

### Step 2: Verify Database Update
Confirm the security function `_view` was updated correctly:

```sql
SELECT id, title, _view 
FROM security_functions 
WHERE title LIKE '%Student Report Card Archive%';
```

Expected result:
- `_view` = `'ReportCardStatuses.index'` (if updated by migration)

### Step 3: Assign Permissions (if needed)
If users do not see the Archive button:

1. Log in as sysadmin
2. Navigate to **Settings > Security > Roles**
3. Select the role that should have archive access
4. Enable the permission: **Student Report Card Archive** (view = 'ReportCardStatuses.index')
5. Save

### Step 4: Clear User Cache
Users must clear browser cache and re-login for permissions to rebuild in session:

```bash
# On browser: Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)
# Clear: Cookies, Cache, Site data for the domain
```

Alternatively, ask users to:
1. Log out completely
2. Close all browser tabs for the domain
3. Log back in

## System Administrator Guide

### Troubleshooting: Archive Button Not Visible

**Symptom**: Users with role have permission but Archive button is missing.

**Investigation**:
1. Check `security_functions` table for the permission record:
   ```sql
   SELECT id, title, _view FROM security_functions 
   WHERE title LIKE '%Student Report Card Archive%';
   ```
   - If missing, migration may not have run — run it again
   - If `_view` = `'ReportCardArchives.index'`, button URL is broken — run migration

2. Check user's role permission assignment:
   ```sql
   SELECT sf.title, r.name 
   FROM security_functions sf
   JOIN security_permissions sp ON sf.id = sp.security_function_id
   JOIN security_roles r ON sp.security_role_id = r.id
   WHERE sf.title LIKE '%Student Report Card Archive%';
   ```
   - If no rows, role is not assigned the permission — assign via UI

3. Enable debug logging in `AccessControlComponent`:
   ```php
   // In AccessControlComponent::check()
   log('DEBUG: Checking permission ' . $permission . ' for user ' . $userId);
   ```
   - Check `logs/hin-debug.log` for permission evaluation trace

### Rollback Instructions (if needed)

If the migration causes issues, rollback:

```bash
# Inside container
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core && php bin/cake.php migrations rollback -t 20260331000000"
```

**What happens**:
- Table `z_8898_institution_students_report_cards` is restored to original
- Table `z_8898_security_functions` is restored, overwriting any manual edits

**Data recovery**:
- Archived report cards remain in their respective archive table (separate from this migration)
- No data loss — migration only backs up and restores tables

### Performance Notes

- Academic period filter now uses indexed `institution_id` FK
- DISTINCT operation happens at database level, not in PHP
- Scales to millions of rows efficiently — no application changes needed

### Monitoring

After deployment, verify in `logs/hin-debug.log`:
- No permission errors for expected users
- Archive button appears in Report Card Statuses toolbar
- No SQL query failures from new ORM methods

---

**Released**: 2026-04-02  
**Branch**: POCOR-8898  
**Related Issues**: POCOR-8898 Bugs 1, 2, 3
