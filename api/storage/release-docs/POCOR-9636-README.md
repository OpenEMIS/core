# POCOR-9636 - Rename Dashboard Visualization Labels

## 1. What is the Task?

Rename the chart titles on the Institution Dashboard visualizations from "Number of Students by Year" and "Number of Staffs by Year" to "Number of Students" and "Number of Staff" respectively.

## 2. Situation Before

- The "Number of Students by Year" column chart displayed the title "Number of Students by Year" (auto-generated from the chart key via `Inflector::humanize()`).
- The "Number of Staff by Year" column chart displayed the title "Number of Staffs by Year" (incorrect pluralization from the same auto-generation).
- Titles were not explicitly set — they were derived from the chart configuration key name inside `HighChartBehavior::getHighChart()`.

## 3. What Was Implemented

### Core Changes

- Set `$params['options']['title']` explicitly in `getNumberOfStudentsByYear()` to return `"Number of Students"`.
- Set `$params['options']['title']` explicitly in `getNumberOfStaffByYear()` to return `"Number of Staff"`.
- The `HighChartBehavior` merges `$params['options']` after setting the auto-title, so the explicit title wins.

### Files Changed Summary

- **Added:** 0 files
- **Modified:** 2 files
- **Removed:** 0 files

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentsTable.php` | Added explicit chart title in `getNumberOfStudentsByYear()` |
| `plugins/Institution/src/Model/Table/StaffTable.php` | Added explicit chart title in `getNumberOfStaffByYear()` |

### Database Migrations

- **Required:** NO
- **Tables affected:** None
- **Backward compatible:** YES

## 4. Deployment Instructions (User Experience)

1. `git pull` on the target server for branch `POCOR-9636`.
2. No migrations required.
3. Clear CakePHP cache: `php bin/cake.php cache clear_all`
4. Smoke test: navigate to any Institution → Dashboard and verify the two column charts show "Number of Students" and "Number of Staff".

## 5. System Administrator Guide

- **Log locations:** `/var/www/html/emis/core/logs/hin-error.log`
- **Configuration:** No configuration changes required.
- **Cron setup:** None.
- **Rollback:** Remove the two `$params['options']['title']` lines added in `StudentsTable.php` and `StaffTable.php` and redeploy.
- **Troubleshooting:** If titles still show the old value, clear the CakePHP cache and hard-refresh the browser.
