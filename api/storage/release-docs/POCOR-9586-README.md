# POCOR-9586: Add UNIQUE Constraint to Summary Area Institution Grade Attendances

## What is the Task?

Add a UNIQUE KEY constraint on the `summary_area_institution_grade_attendances` table across the composite columns (academic_period_id, institution_id, education_grade_id, attendance_date) to prevent duplicate summary records and ensure data integrity. The migration includes duplicate cleanup logic for small/test databases.

## Situation Before

- The `summary_area_institution_grade_attendances` table had no unique constraint on its key identifying columns
- Summary records could be duplicated, leading to potential data integrity issues
- No enforcement mechanism existed to prevent duplicate summary entries for the same academic period, institution, grade, and attendance date combination

## What Was Implemented

### Core Changes

1. **CakePHP Migration** (`config/Migrations/20260225120000_POCOR9586.php`)
    - Backs up the table before any modifications for safe rollback
    - Cleans up any duplicate rows using a temp table with INSERT IGNORE — one row per unique key group is kept (first encountered by storage order)
    - Adds UNIQUE KEY constraint: `uq_sai_ap_inst_grade_date(academic_period_id, institution_id, education_grade_id, attendance_date)`
    - Provides complete rollback support via `down()` method

2. **Duplicate Cleanup Logic**
    - Creates a temporary table with the UNIQUE KEY already defined
    - Copies all rows using INSERT IGNORE — duplicate key conflicts are silently skipped
    - Drops the original table and renames the temp table in its place
    - The UNIQUE KEY is already present on the renamed table — no separate ALTER TABLE needed

### Files Changed Summary

| Type | Count |
|------|-------|
| Added | 1 |
| Modified | 0 |
| Removed | 0 |

**Files:**
- `config/Migrations/20260225120000_POCOR9586.php` (added)

### Database Migrations

| Aspect | Details |
|--------|---------|
| **Required** | YES |
| **Tables Affected** | `summary_area_institution_grade_attendances` |
| **Backward Compatible** | YES (fully reversible via `down()`) |
| **Backup Created** | `z_9586_summary_area_institution_grade_attendances` (automatically dropped on rollback) |

## Deployment Instructions (User Experience)

1. **Pull the latest code**
   ```bash
   git pull origin POCOR-9586
