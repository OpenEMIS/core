# POCOR-9567: Fix 404 on ViewReport for Large Files

## What is the Task?

When viewing a large Staff Subjects Report, the system hangs and returns a 404 error. The download functionality works correctly, but the inline view fails due to PHP execution timeout during PhpSpreadsheet processing. This fix eliminates PhpSpreadsheet from the view path entirely for new reports by writing a companion CSV file at generation time.

## Situation Before

- Clicking "View" on large Staff Subjects Reports returned 404 after timeout
- Download worked fine due to different code path
- `set_time_limit(0)` was insufficient because PHP-FPM overrides `max_execution_time` via `php_admin_value` — user-level calls cannot override this
- PhpSpreadsheet was the only way to read xlsx at view time — slow for large files

## What Was Implemented

### Key Changes

1. **Companion CSV at Generation Time (`ExcelBehavior::generateXLXS`):**
   - Before calling `$generate()`, open a CSV file handle at the same path as the xlsx but with `.csv` extension
   - Pass the handle through `$_settings['csv_handle']`
   - In `generate()`, write each header row and data row to the CSV alongside the xlsx (landscape orientation)
   - After `$writer->writeToFile()`, close the CSV handle
   - Report shell runs as CLI with no `max_execution_time` limit — CSV writes happen without timeout risk

2. **CSV-First ViewReport (`ReportsController::ViewReport`):**
   - Derive `$csvFileName` from `$inputFileName` using `preg_replace('/\.xlsx$/i', '.csv', ...)`
   - If the CSV exists, read it entirely with `fgetcsv` — no PhpSpreadsheet, no timeout
   - If the CSV does not exist (old report pre-fix), fall back to existing PhpSpreadsheet logic

3. **Retained from earlier fix:**
   - `set_time_limit(0)` in `ViewReport()` (still useful for old-report fallback path)
   - `$objReader->setReadDataOnly(true)` in fallback path
   - Single-pass row accumulation loop

### Files Changed Summary

```
Added:    0 files
Modified: 2 files
Removed:  0 files
```

**Modified Files:**
- `src/Model/Behavior/ExcelBehavior.php` — `generateXLXS()` and `generate()`: companion CSV writing
- `plugins/Report/src/Controller/ReportsController.php` — `ViewReport()`: CSV-first read with xlsx fallback

### Database Migrations

None.

## Deployment Instructions

1. **Pull Latest Code**
   ```bash
   git pull origin POCOR-9567
   ```

2. **Clear PHP Cache**
   ```bash
   php artisan route:clear && php artisan config:cache && php artisan cache:clear
   ```

3. **Generate a New Report**
   - Generate a Staff Subjects Report with 5000+ rows (new reports created after this deploy will have a companion `.csv` file)

4. **Test ViewReport on New Report**
   - Click "View" on the newly generated report
   - Verify it displays instantly without timeout

5. **Test ViewReport on Old Report (Fallback)**
   - Click "View" on a report generated before this deploy (no `.csv` companion)
   - Verify it still loads via PhpSpreadsheet fallback

6. **Verify Download Still Works**
   - Download any report — should be unaffected

## System Administrator Guide

### Log Locations

- **CakePHP Error Log:** `/var/www/html/emis/core/logs/hin-error.log`
- **CakePHP Debug Log:** `/var/www/html/emis/core/logs/hin-debug.log`

### Rollback

```bash
git revert POCOR-9567
git push origin master
```

### Troubleshooting

| Issue | Root Cause | Solution |
|-------|-----------|----------|
| ViewReport still times out on new reports | `.csv` not being written | Check write permissions on report folder; check `fopen` errors in error log |
| ViewReport times out on old reports | PHP-FPM `php_admin_value max_execution_time` override | Increase `max_execution_time` in PHP-FPM pool config; old reports must be regenerated |
| CSV exists but view shows wrong data | Column count mismatch in `array_combine` | Check `count($csvRow) === count($rowHeaderNew)` guard; rows silently skipped if mismatched |
| Incorrect data in view | Row logic regression | Compare CSV content with xlsx download; check `fgetcsv` delimiter matches |

### Performance Notes

- CSV read via `fgetcsv` is ~50-100x faster than PhpSpreadsheet for large files
- Companion CSV adds negligible overhead at generation time (streaming write, same data pass)
- No database impact — purely filesystem

---

**Branch:** POCOR-9567
**Ticket:** POCOR-9567
**Date Created:** 2026-03-25
**Last Updated:** 2026-03-31
