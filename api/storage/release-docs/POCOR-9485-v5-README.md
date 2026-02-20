# POCOR-9485 — Next Programme Options for Education Programmes

## 1. What is the Task?

This feature adds a per-programme configuration option (`next_programme_option_id`) that controls which grade entries appear in the "To Grade" dropdown when promoting or graduating a student who is at the last grade of their education cycle. Two options are provided: **Show All Programmes** (0) lists every grade of every linked next programme, enabling direct cross-cycle promotion without a manual re-enrolment step; **Show One Programme** (1) retains the existing behaviour of showing only the first grade of each linked next programme. Additionally, the Education Programmes edit form now shows a read-only **Academic Period** field derived from the programme's education system, and a bug was fixed where the "Add Next Programme" dropdown was populated from the wrong academic period.

---

## 2. Situation Before

- The Promote/Graduate/Repeat page always showed only the **first grade of each linked next programme** regardless of configuration — cross-cycle direct promotion was impossible.
- An administrator who wanted to graduate a student into any grade of the next cycle was forced to: (a) graduate the student, then (b) manually re-enrol them in the correct grade of the new cycle — a two-step workflow with no single-step alternative.
- `getCycleAndLevelInfo()` called `AcademicPeriods->getCurrent()` to determine which period's programmes to show in the "Add Next Programme" dropdown; on systems where the system-wide current period differed from the programme's own period, the wrong programmes appeared.
- Deleting a Next Programme row on the edit form did not immediately return that programme to the "Add Next Programme" dropdown because the page reloaded from the database (which still held the old data) instead of from the POST payload.
- `getNextProgrammeFirstGradeList()` returned all grades per next programme instead of just the first one — only the first was *used*, but the query fetched all unnecessarily.
- `onUpdateFieldEducationGradeId()` in `StudentPromotionTable` contained deeply nested conditionals with a raw SQL string (SQL-injection risk via variable interpolation) for the same-grade-promotion path.

---

## 3. What Was Implemented

### Core changes

| Layer | Change |
|---|---|
| Database | `next_programme_option_id TINYINT(1) DEFAULT 1` added to `education_programmes` |
| EducationProgrammesNextProgrammesTable | `getNextProgrammeFirstGradeList()` adds `->limit(1)` — exactly one grade per next programme returned; `getNextGradeList()` adds `->find('order')` for ordered results; whitespace normalised |
| EducationProgrammesTable | Exposes `next_programme_option_id` as a select field ("Next Programme Options"); adds read-only "Academic Period" field derived from the programme's education system; `getCycleAndLevelInfo()` fixed to read `academic_period_id` from `EducationSystems` instead of calling `getCurrent()`; `collectSelectedNextProgrammes()` skips DB reload on POST/PUT so deleted rows stay deleted; delete button triggers `#reload` so removed items reappear in the add-dropdown immediately; various `Event` → `EventInterface` type fixes |
| StudentPromotionTable | Full clean-code refactor of `onUpdateFieldEducationGradeId()`: early returns replace nested conditionals; grade/programme info fetched once via `contain(['EducationProgrammes'])`; `next_programme_option_id` respected for all GRADUATED+isLastGrade paths; two private helpers extracted — `getGradesForGraduation()` (with temporary debug logging) and `findMatchingGradeInPeriod()`; raw SQL with variable interpolation (SQL-injection risk) replaced by equivalent CakePHP ORM query in `findMatchingGradeInPeriod()` |
| next_programmes.php | Whitespace normalised (tabs → spaces) |

### Files Changed Summary

| Action | File |
|---|---|
| NEW | `config/Migrations/20260220120000_POCOR9485.php` |
| MODIFIED | `plugins/Education/src/Model/Table/EducationProgrammesTable.php` |
| MODIFIED | `plugins/Education/src/Model/Table/EducationProgrammesNextProgrammesTable.php` |
| MODIFIED | `plugins/Education/templates/Element/next_programmes.php` |
| MODIFIED | `plugins/Institution/src/Model/Table/StudentPromotionTable.php` |
| NEW | `api/storage/release-docs/POCOR-9485-v5-README.md` |

**Files added:** 2 | **Files modified:** 4 | **Files removed:** 0

### Database Migrations

- **Required:** YES
- **Tables affected:** `education_programmes` (column `next_programme_option_id` added)
- **Backward compatible:** YES — default value `1` keeps existing programmes on the old "Show One Programme" behaviour

---

## 4. Deployment Instructions (User Experience)

1. `git pull origin POCOR-9485-v5` on the target server.
2. Shell into the container: `docker exec poe-application /bin/sh`
3. Run the migration: `cd /var/www/html/emis/core && ./bin/cake migrations migrate`
4. Confirm the column exists:
   ```sql
   DESCRIBE education_programmes;
   -- expect: next_programme_option_id column present
   ```
5. Clear CakePHP cache if applicable: `./bin/cake cache clear_all`
6. **Optional — fix academic_periods.order field** (required when `getNextAcademicPeriodId` returns the wrong year in the "Add Next Programme" dropdown):
   ```sql
   UPDATE academic_periods ap
   JOIN (
       SELECT id,
              ROW_NUMBER() OVER (
                  PARTITION BY academic_period_level_id
                  ORDER BY start_date DESC
              ) AS new_order
       FROM academic_periods
   ) ranked ON ap.id = ranked.id
   SET ap.`order` = ranked.new_order;
   ```
   After this fix, the newest year has the lowest `order` value, so `ORDER BY order DESC LIMIT 1` on future periods returns the immediately next year rather than the furthest future year.
7. **Smoke test — Config field:**
   - Navigate to Administration > Education Structure > Education Programmes > Edit any programme.
   - Confirm "Academic Period" read-only field appears showing the programme's academic period.
   - Confirm "Next Programme Options" select appears with options "Show One Programme" / "Show All Programmes".
8. **Smoke test — Delete row reappears:**
   - On the same edit page, add two or more Next Programmes.
   - Delete one row. Confirm the deleted item immediately reappears in the "Add Next Programme" dropdown without a page refresh.
9. **Smoke test — Show One Programme (option=1):**
   - Set a programme to "Show One Programme", link two next programmes.
   - Go to Institutions > Students > Promote/Graduate for a student in the last grade of that programme.
   - Confirm "To Grade" shows only the first grade of each linked next programme.
10. **Smoke test — Show All Programmes (option=0):**
    - Change same programme to "Show All Programmes". Repeat step 9.
    - Confirm "To Grade" shows all grades of all linked next programmes.

---

## 5. System Administrator Guide

### Monitoring / Logs

- CakePHP logs: `logs/error.log` and `logs/debug.log` inside the container at `/var/www/html/emis/core/`.
- Temporary debug lines tagged `[POCOR-9485]` are present in `StudentPromotionTable::getGradesForGraduation()` — these will be removed once graduation behaviour is confirmed stable in production.
- No new cron jobs or background processes introduced by this change.

### Configuration

- `next_programme_option_id` is set per Education Programme in the admin UI (Administration > Education Structure > Education Programmes > Edit).
- Changing the value takes effect immediately on the next Promote/Graduate page load — no cache clear required.

### Rollback Procedure

```bash
# Inside the container:
cd /var/www/html/emis/core
./bin/cake migrations rollback
# This restores education_programmes from its z_9485_education_programmes backup table.
```

### Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| "Next Programme Options" select not visible | Migration not run | Run `./bin/cake migrations migrate` |
| "Academic Period" field not showing | `getCycleAndLevelInfo()` returns null | Check that `education_systems.academic_period_id` is set for the programme's cycle |
| "Add Next Programme" dropdown shows wrong period's programmes | `academic_periods.order` field non-chronological | Run the `academic_periods` ORDER fix SQL from step 6 of the deployment instructions |
| Deleted Next Programme row reappears on reload | Old code — DB was re-queried on POST | Confirm `collectSelectedNextProgrammes()` has the `!$this->request->is(['post', 'put'])` guard |
| Graduation dropdown still shows only first grade despite option=0 | Cache stale or migration not run | Clear CakePHP cache; confirm migration ran and `next_programme_option_id` is saved as 0 in DB |
| Graduation dropdown is empty | Grade-to-institution mapping missing or period mismatch | Check `institution_grades` for the target institution/period; verify `academic_periods.order` fix was applied |
| PROMOTED same-grade-promotion shows wrong grade | Grade name not found in target period | Check `education_grades.name` uniqueness across periods; confirm `findMatchingGradeInPeriod()` ORM helper is present |
