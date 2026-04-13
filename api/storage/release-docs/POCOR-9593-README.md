# POCOR-9593 — Profile Age Indicator & Stale Report Banner

## 1. What is the Task?

Display a notification banner for all profile features when the client views or downloads a profile. If profiles are older than 1 month, show an alert banner. Additionally, add a visual age indicator column to all profile index tables.

## 2. Situation Before

- Profile index tables (Student, Staff, Institution, Classes) showed no indication of how old a generated profile was.
- Users had no warning when viewing or downloading a profile that may be out of date.
- The generation window "not enabled" message (POCOR-9598) showed alone with no stale-data context.

## 3. What Was Implemented

### Age Indicator Column

A new unlabelled **Age** column (first column, no header text) was added to all four profile index tables. Each row displays a small coloured square:

| Square | Colour | Meaning |
|--------|--------|---------|
| ☐ | Grey (empty border) | Not yet generated (New / In Progress / Failed) |
| ■ | Blue (#2196F3) | Generated less than 30 days ago |
| ■ | Yellow (#FFC107) | Generated 30 days to less than 1 year ago |
| ■ | Red (#F44336) | Generated 1 year or more ago |

Hovering the square shows a tooltip: *"Generated X days ago"*.

### Stale Report Warning Banner

The banner behaviour depends on whether the generation window is open or closed:

**Generation window open + stale profile(s) ≥ 30 days:**
- 1 stale profile: *"This report was generated X days ago. To ensure this report reflects the most recent data updates, please regenerate the report before viewing or downloading."*
- 2+ stale profiles: *"There are reports generated up to X days ago. To ensure these reports reflect the most recent data updates, please regenerate the reports before viewing or downloading."*

**Generation window closed + stale profile(s) ≥ 30 days** (combined with the existing "not enabled" message):
- 1 stale profile: *"This profile template generation is not enabled. Consult with system administrator to check the dates. Note: this report is X days old and may not reflect the most recent data."*
- 2+ stale profiles: *"This profile template generation is not enabled. Consult with system administrator to check the dates. Note: some reports are up to X days old and may not reflect the most recent data."*

**Generation window closed + no stale profiles:** Only the original "not enabled" message is shown — unchanged.

X is the number of days since the oldest stale `completed_on` in the current view.

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentProfilesTable.php` | Age field (first, no label), `onGetAge()`, stale banner with singular/plural + window-open/closed logic |
| `plugins/Institution/src/Model/Table/StaffProfilesTable.php` | Same |
| `plugins/Institution/src/Model/Table/InstitutionsProfileTable.php` | Same + new `indexAfterAction` method |
| `plugins/Institution/src/Model/Table/ClassesProfilesTable.php` | Same (stale banner added inside existing `indexAfterAction`) |

### Database Migrations

None. No database changes required.

## 4. Deployment Instructions

1. Pull the `POCOR-9593` branch.
2. Clear the CakePHP cache:
   ```bash
   php bin/cake.php cache clear_all
   ```
3. No database migration required.

## 5. System Administrator Guide

### How to test

1. Navigate to **Institutions > Profiles > Student Profiles** (or Staff / Institution / Classes Profiles).
2. Select an Academic Period and a Profile Template.
3. The first (unlabelled) column should show coloured squares — hover to see the tooltip.
4. **Test stale banner (window open):** select a template whose `generate_start_date`/`generate_end_date` includes today, with at least one row where `completed_on` > 30 days ago → yellow warning banner appears.
5. **Test combined banner (window closed):** select a template whose generation window has expired, with at least one stale row → combined "not enabled + stale data" banner appears.
6. **Test no banner (window closed, all fresh):** select a closed-window template with all rows generated recently → only the original "not enabled" banner appears.

### Age thresholds

| Threshold | Indicator |
|-----------|-----------|
| Not generated | Empty grey square |
| < 30 days | Blue square |
| 30 days – 364 days | Yellow square |
| ≥ 365 days | Red square |
| Any row ≥ 30 days + window open | Stale regenerate banner |
| Any row ≥ 30 days + window closed | Combined not-enabled + stale banner |
