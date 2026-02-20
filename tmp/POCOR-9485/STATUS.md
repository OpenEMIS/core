# POCOR-9485 — Next Programme Options for Education Programmes

## Task
Add a configurable `next_programme_option_id` field to Education Programmes that controls which grades appear on the Promote/Graduate/Repeat page when a student reaches the last grade of a cycle. Also add an `order` column to `education_programmes_next_programmes` and up/down reorder buttons in the admin UI.

## Key Findings
- `StudentPromotionTable::onUpdateFieldEducationGradeId()` hard-codes `$firstGradeOnly = true` for GRADUATED+isLastGrade, calling `getNextAvailableEducationGrades($id, true, true)`.
- `getNextAvailableEducationGrades()` already has a 3rd `$firstGradeOnly` parameter routing to `getNextProgrammeFirstGradeList` (true) or `getNextGradeList` (false).
- `getNextProgrammeList()` had no ORDER BY — downstream first-grade selection was non-deterministic.
- `buildProgrammeTableRows()` and `collectSelectedNextProgrammes()` needed to track `order` and support reorder requests via reload signals.

## Key Decisions
- Default `next_programme_option_id = 1` (Show One Programme) preserves existing behaviour.
- `order` column populated via `ROW_NUMBER() OVER (PARTITION BY education_programme_id ORDER BY id)` for existing rows.
- Reorder buttons use the existing `#reload` hidden-input pattern (same as add-next-programme dropdown).

## What Is Done
- [x] Migration: `20260220120000_POCOR9485.php` — adds columns, populates order, backup/restore
- [x] `EducationProgrammesNextProgrammesTable`: `getNextProgrammeList()` orders by `order ASC`
- [x] `EducationProgrammesTable`:
  - `_fieldOrder`, `beforeAction`, `addEditBeforeAction` updated
  - `onUpdateFieldNextProgrammeOptionId()` event handler added
  - `onGetFieldLabel()` updated with "Next Programme Options" label
  - `buildViewTable()` shows Order column, sorted by order
  - `collectSelectedNextProgrammes()` loads/stores order from DB and POST
  - `buildEditAddTable()` handles `moveUp_` / `moveDown_` reload signals
  - `reorderProgrammes()` helper method added
  - `buildProgrammeTableRows()` renders Order column + up/down buttons + order hidden field
- [x] `StudentPromotionTable`: reads `next_programme_option_id`, sets `$firstGradeOnly` accordingly
- [x] Release docs written at `api/storage/release-docs/POCOR-9485-v5-README.md`

## What Remains
- Run migration inside Docker: `./bin/cake migrations migrate`
- Manual smoke-test (see release doc for steps)
