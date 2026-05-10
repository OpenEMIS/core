# POCOR-9636 - Rename Dashboard Visualization Labels

## 1. What is the Task?

Clean up the Institution Dashboard chart labels:

- Rename chart titles from `"Number of Students by Year"` / `"Number of Staffs by Year"` to `"Number of Students"` / `"Number of Staff"`.
- Remove the redundant `"Years"` x-axis title and the lone year tick label (e.g. `"2026"`) under the bars — these added no information because the charts only ever show the current year.

## 2. Situation Before

- The "Number of Students by Year" column chart displayed the title "Number of Students by Year" (auto-generated from the chart key via `Inflector::humanize()`).
- The "Number of Staff by Year" column chart displayed the title "Number of Staffs by Year" (incorrect pluralization from the same auto-generation).
- Titles were not explicitly set — they were derived from the chart configuration key name inside `HighChartBehavior::getHighChart()`.
- Under each year-based chart, Highcharts rendered:
  - An x-axis title `"Years"` (configured in the behavior `xAxis.title.text`).
  - A single tick label showing the current year (`"2026"`).
  - Both were visually noisy for charts that show only one year at a time.

## 3. What Was Implemented

### Core Changes

#### Issue 1 — chart titles

- Set `$params['options']['title']` explicitly in `getNumberOfStudentsByYear()` to return `"Number of Students"`.
- Set `$params['options']['title']` explicitly in `getNumberOfStaffByYear()` to return `"Number of Staff"`.
- The `HighChartBehavior` merges `$params['options']` after setting the auto-title, so the explicit title wins.

#### Issue 2 — remove `"Years"` axis title and year tick

Applied the same `xAxis` config in 3 places (Students dashboard, Staff Attendance, Number of Staff by Year):

```php
'xAxis' => ['title' => null, 'labels' => ['enabled' => false]], //POCOR-9636
```

- `title => null` removes the `"Years"` label.
- `labels.enabled => false` hides the year tick (`"2026"`) under the bar.

### Files Changed Summary

- **Added:** 0 files
- **Modified:** 2 files
- **Removed:** 0 files

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentsTable.php` | Added chart title in `getNumberOfStudentsByYear()`; cleaned `xAxis` config for `number_of_students_by_year`; line-ending normalization CRLF→LF (separate commit). |
| `plugins/Institution/src/Model/Table/StaffTable.php` | Added chart title in `getNumberOfStaffByYear()`; cleaned `xAxis` config for `staff_attendance` and `number_of_staff_by_year`. |

### Database Migrations

- **Required:** NO
- **Tables affected:** None
- **Backward compatible:** YES

## 4. Deployment Instructions (User Experience)

1. `git pull` on the target server for branch `POCOR-9636`.
2. No migrations required.
3. Clear CakePHP cache: `php bin/cake.php cache clear_all`
4. Smoke test the Institution Dashboard:
   - "Number of Students" and "Number of Staff" appear as chart titles (no "by Year" suffix).
   - No `"Years"` text under any chart.
   - No lone year tick (`"2026"`) under any chart.

## 5. System Administrator Guide

- **Log locations:** `/var/www/html/emis/core/logs/hin-error.log`
- **Configuration:** No configuration changes required.
- **Cron setup:** None.
- **Rollback:** Revert commits `ac7dc1f0c6` and `2c0a965694` on branch `POCOR-9636`.
- **Troubleshooting:** If titles still show the old value, clear the CakePHP cache and hard-refresh the browser.

## 6. Observations Out of Scope for This Ticket

These were discovered during QA but intentionally **not** fixed on POCOR-9636 — they belong to separate tickets.

### 6.1 — Empty-state inconsistency on attendance charts

When no attendance has been marked for today, the **Student Attendance** chart renders a fully blank plot, while the **Staff Attendance** chart renders an empty plot with a `0` Y-axis baseline.

Cause: `StaffTable::getNumberOfStaffByAttendanceType` pre-seeds zeros (`$dataSet['Present']['data'][$currentYear] = 0`), so Highcharts has values to compute a Y range from. `StudentsTable::getNumberOfStudentsByAttendanceType` leaves `$dataSet[*]['data']` empty, so Highcharts has no values and skips the axis.

Decision: **leave as-is**. Once any attendance/absence exists for the day, both charts render identically. Normalizing the empty state would require touching Student data assembly logic and is not visible to end-users in normal operation.

### 6.2 — Timezone bug: dashboard counters use PHP time, not OpenEMIS config

The dashboard attendance counters compute "today" via PHP's `date('Y-m-d')` and `FrozenDate::today()` — which read the **process timezone** (container PHP default = UTC), **not** the OpenEMIS `config_items.time_zone` setting.

Reproduction (2026-05-11):

| Clock | "Today" |
|---|---|
| User (Turkey, UTC+3) | 2026-05-11 |
| OpenEMIS config (`Asia/Kamchatka`, UTC+12) | 2026-05-11 |
| Container PHP / MySQL (UTC) | 2026-05-10 |

Attendance is correctly saved under `date = 2026-05-11`, but the chart filter resolves to `2026-05-10` → returns no rows → chart appears empty even after marking attendance.

Established OpenEMIS pattern (used in 8 other files, e.g. `GenerateAllReportCardsShell.php:31`):

```php
$ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
$timeZone = $ConfigItems->value("time_zone");
date_default_timezone_set($timeZone);
```

This pattern is **missing** from `getNumberOfStudentsByAttendanceType` and `getNumberOfStaffByAttendanceType` (and likely other dashboard getters). A broader audit is recommended.

Decision: **out of scope for POCOR-9636** (chart-label cleanup). Track in a separate ticket — bug affects data correctness across many features, not just the dashboard.
