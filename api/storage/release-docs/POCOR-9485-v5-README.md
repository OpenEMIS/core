# POCOR-9485 — Next Programme Options for Education Programmes

## 1. What is the Task?

This feature adds a per-programme configuration option (`next_programme_option_id`) that controls which grade entries appear in the "To Grade" dropdown when promoting or graduating a student who is at the last grade of their education cycle. Two options are provided: **Show All Programmes** (0) lists every grade of every linked next programme, enabling direct cross-cycle promotion without a manual re-enrolment step; **Show One Programme** (1) retains the existing behaviour of showing only the first grade of each linked next programme. Additionally, a new `order` column on the next-programmes junction table lets administrators control the display order of linked next programmes through up/down reorder buttons in the Education Programmes edit form.

---

## 2. Situation Before

- The Promote/Graduate/Repeat page always showed only the **first grade of each linked next programme** regardless of configuration — cross-cycle direct promotion was impossible.
- An administrator who wanted to graduate a student into any grade of the next cycle was forced to: (a) graduate the student, then (b) manually re-enrol them in the correct grade of the new cycle — a two-step workflow with no single-step alternative.
- The `education_programmes_next_programmes` junction table had no `order` column, so the display order of linked next programmes was non-deterministic (insertion-order only).
- `getNextProgrammeList()` issued no `ORDER BY`, making the "first grade" selection for option-1 unpredictable.

---

## 3. What Was Implemented

### Core changes

| Layer | Change |
|---|---|
| Database | `next_programme_option_id TINYINT(1) DEFAULT 1` added to `education_programmes`; `order INT(11) DEFAULT 0` added to `education_programmes_next_programmes` |
| EducationProgrammesNextProgrammesTable | `getNextProgrammeList()` now includes `ORDER BY order ASC` |
| EducationProgrammesTable | Exposes `next_programme_option_id` as a select field with label "Next Programme Options"; tracks `order` in `collectSelectedNextProgrammes()`; handles `moveUp_*` / `moveDown_*` reload signals in `buildEditAddTable()`; renders Order column and up/down buttons in `buildProgrammeTableRows()`; `buildViewTable()` shows Order column sorted by `order` |
| StudentPromotionTable | Reads `next_programme_option_id` from the grade's programme and sets `$firstGradeOnly` accordingly before calling `getNextAvailableEducationGrades()` |

### Files Changed Summary

| Action | File |
|---|---|
| NEW | `config/Migrations/20260220120000_POCOR9485.php` |
| MODIFIED | `plugins/Education/src/Model/Table/EducationProgrammesTable.php` |
| MODIFIED | `plugins/Education/src/Model/Table/EducationProgrammesNextProgrammesTable.php` |
| MODIFIED | `plugins/Institution/src/Model/Table/StudentPromotionTable.php` |
| NEW | `tmp/POCOR-9485/STATUS.md` |
| NEW | `api/storage/release-docs/POCOR-9485-v5-README.md` |

**Files added:** 3 | **Files modified:** 3 | **Files removed:** 0

### Database Migrations

- **Required:** YES
- **Tables affected:** `education_programmes` (column added), `education_programmes_next_programmes` (column added, data backfilled)
- **Backward compatible:** YES — default value `1` keeps existing programmes on the old "Show One Programme" behaviour

---

## 4. Deployment Instructions (User Experience)

1. `git pull origin POCOR-9485-v5` on the target server.
2. Shell into the container: `docker exec -it poe-application /bin/sh`
3. Run the migration: `cd /var/www/html/emis/core && ./bin/cake migrations migrate`
4. Confirm columns exist:
   ```sql
   DESCRIBE education_programmes;
   -- expect: next_programme_option_id column present
   DESCRIBE education_programmes_next_programmes;
   -- expect: order column present
   ```
5. Clear CakePHP cache if applicable: `./bin/cake cache clear_all`
6. **Smoke test — Config field:**
   - Navigate to Administration > Education Structure > Education Programmes > Edit any programme.
   - Confirm "Next Programme Options" select appears with options "Show All Programmes" / "Show One Programme".
7. **Smoke test — Ordering:**
   - On the same edit page, add two or more Next Programmes.
   - Use ▲ / ▼ buttons to reorder. Save. Reload and confirm order persists.
8. **Smoke test — Show One Programme (option=1):**
   - Set a programme to "Show One Programme", link two next programmes.
   - Go to Institutions > Students > Promote/Graduate for a student in the last grade.
   - Confirm "To Grade" shows only the first grade of the first (order=1) linked programme.
9. **Smoke test — Show All Programmes (option=0):**
   - Change the same programme to "Show All Programmes". Repeat step 8.
   - Confirm "To Grade" shows all grades of all linked next programmes.

---

## 5. System Administrator Guide

### Monitoring / Logs

- CakePHP logs: `logs/error.log` and `logs/debug.log` inside the container at `/var/www/html/emis/core/`.
- No new cron jobs or background processes introduced by this change.

### Configuration

- `next_programme_option_id` is set per Education Programme in the admin UI.
- Changing the value takes effect immediately on the next Promote/Graduate page load — no cache clear required.

### Rollback Procedure

```bash
# Inside the container:
cd /var/www/html/emis/core
./bin/cake migrations rollback
# This restores both education_programmes and education_programmes_next_programmes
# from their z_9485_* backup tables.
```

### Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| "Next Programme Options" select not visible | Migration not run | Run `./bin/cake migrations migrate` |
| Up/down buttons not responding | JavaScript error (jQuery not loaded) | Check browser console; ensure jQuery is available |
| Order not persisting after save | `order` field not in `_joinData` hidden field | Verify `buildProgrammeTableRows()` includes `{$joinPrefix}.order` hidden field |
| Graduation dropdown still shows only first grade despite option=0 | Cache stale | Clear CakePHP cache; confirm migration ran |
