# POCOR-9039 — phpspreadsheet 3.x Upgrade & Image Transparency Fix

## 1. What is the Task?

Upgrade `phpoffice/phpspreadsheet` from 2.x to 3.x, fix report card image transparency so it is baked into the PNG pixel data (rather than relying on Excel's own opacity rendering), and remove the deprecated `maatwebsite/excel` package across all affected Laravel files and CakePHP behaviors.

---

## 2. Situation Before

- `phpoffice/phpspreadsheet` was pinned to `^2.0`; the `getOpacity()` API (needed to read per-drawing opacity from xlsx) was only introduced in 3.4.0
- Report card xlsx templates with semi-transparent images appeared correctly in Excel because Excel applied its stored `<a:solidFill>` opacity at render time — but when PhpSpreadsheet loaded and re-saved the file, the opacity was ignored and images appeared fully opaque
- `Cake\Filesystem\File` and `Cake\Filesystem\Folder` are deprecated in CakePHP 5; all five `*ExcelReportBehavior` files still used them
- `maatwebsite/excel` was still referenced in imports/exports, controllers, repositories and `config/app.php` even though the package had been removed

---

## 3. What Was Implemented

### Core Changes

- **phpspreadsheet 2.4.3 → 3.10.3** (`composer.json` constraint changed to `^3.4`, PHP constraint raised to `^8.1`)
- **`ProcessTransparencyCommand`** (`api/app/Console/Commands/ReportCards/ProcessTransparencyCommand.php`):
  - Uses `getOpacity()` (scale 0–100000; 100000 = fully opaque) instead of the removed `getAlpha()`
  - Reads embedded images via `file_get_contents()` which handles `zip://` stream URIs produced by PhpSpreadsheet for in-xlsx images (`file_exists()` always fails on these)
  - Uses `imagecreatefromstring()` — auto-detects JPEG/PNG/GIF/WebP, no MIME switch needed
  - Applies opacity pixel-by-pixel with `imagecolorallocatealpha()` — `imagecopymerge()` only blends RGB and ignores alpha channels
  - Calls `$drawing->setOpacity(100000)` after baking so Excel does not double-apply the effect
  - Saves output in-place (overwrites original file)
- **5 CakePHP `*ExcelReportBehavior` files** (`plugins/CustomExcel/src/Model/Behavior/`):
  - Inject `exec(PHP_BINARY . ' artisan reportcards:fix-transparency ' . $filepath)` immediately after writing the xlsx template to a temp file, so transparency is baked before PhpSpreadsheet loads the spreadsheet
  - Replace deprecated `Cake\Filesystem\File` → `file_put_contents()`, `file_get_contents()`, `unlink()`
  - Replace deprecated `Cake\Filesystem\Folder` → `@mkdir($path, 0777, true)`
- **Maatwebsite removal** — removed all `Maatwebsite\Excel` imports and usages across Exports, Imports, Controllers, Repositories and `config/app.php`; replaced with raw `PhpOffice\PhpSpreadsheet` equivalents

### Files Changed Summary

- **Modified:** 23 files
- **Added:** 1 file (`ProcessTransparencyCommand.php`)
- **Removed:** 0 files (maatwebsite references deleted inline)

### Database Migrations

- **Required:** NO
- **Tables affected:** none
- **Backward compatible:** YES

---

## 4. Deployment Instructions

1. **Pull the branch:**
   ```bash
   git checkout POCOR-9039-v5
   git pull origin POCOR-9039-v5
   ```

2. **Install updated dependencies (inside container or in `api/` folder):**
   ```bash
   cd /var/www/html/emis/core/api
   composer install --no-dev --optimize-autoloader
   ```
   This installs `phpoffice/phpspreadsheet` 3.10.3 and removes any lingering maatwebsite packages.

3. **Clear caches:**
   ```bash
   php artisan config:cache
   php artisan cache:clear
   ```

4. **Smoke test — verify transparency command works:**
   ```bash
   # Place any report card xlsx template at /tmp/test.xlsx, then:
   php artisan reportcards:fix-transparency /tmp/test.xlsx
   # Check /tmp/test.xlsx — drawings with opacity < 100% should now have alpha baked into their PNG data
   ```

5. **Verify existing exports still work** — open the attendance or meal export from the UI and confirm the downloaded xlsx is valid.

---

## 5. System Administrator Guide

### Log locations
- Laravel: `api/storage/logs/laravel.log`
- Transparency command errors are written to stderr (captured in log when called from behaviors via `exec(...2>&1)`)

### How transparency is applied
The `reportcards:fix-transparency` artisan command is called synchronously from CakePHP's `*ExcelReportBehavior::loadExcelTemplate()` every time a report card template is loaded for generation. It modifies the temp file in-place before PhpSpreadsheet opens it.

### Rollback
If issues arise with xlsx generation:
1. Revert the two commits on this branch (`git revert f0861cf107 f0768b1b92 ...`)
2. Run `composer install` to restore phpspreadsheet 2.x
3. No data or migrations to roll back

### Troubleshooting

| Symptom | Check |
|---------|-------|
| Images appear fully opaque in generated report cards | Verify `getOpacity()` returns < 100000; check `api/storage/logs/laravel.log` for transparency command errors |
| `Class 'Maatwebsite\Excel\...' not found` | Some file was missed in the maatwebsite removal — grep for remaining `Maatwebsite` references |
| `Call to undefined method ... getOpacity()` | phpspreadsheet version is < 3.4.0 — run `composer show phpoffice/phpspreadsheet` to verify 3.x is installed |
| Excel export downloads empty or corrupt file | Check that `PhpOffice\PhpSpreadsheet\IOFactory` is imported in the affected Export/Import class |
