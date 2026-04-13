# POCOR-9593 — Profile Age Indicator & Stale Report Banner

## 1. What is the Task?

Display a notification banner for all profile features when the client views or downloads a profile. If profiles are older than 1 month, show an alert banner. Additionally, add a visual age indicator column to all profile index tables.

## 2. Situation Before

- Profile index tables (Student Profiles, Staff Profiles, Institution Profiles) showed no indication of how old a generated profile was.
- Users had no warning when viewing or downloading a profile that may be out of date.

## 3. What Was Implemented

### Age Indicator Column

A new **Age** column was added to all three profile index tables. Each row displays a small coloured square indicating how recently the profile was generated:

| Square | Colour | Meaning |
|--------|--------|---------|
| ☐ | Grey (empty) | Not yet generated (New / In Progress / Failed) |
| ■ | Blue (#2196F3) | Generated less than 30 days ago |
| ■ | Yellow (#FFC107) | Generated 30 days to less than 1 year ago |
| ■ | Red (#F44336) | Generated 1 year or more ago |

Hovering the square shows a tooltip: *"Generated X days ago"*.

### Stale Report Warning Banner

When the index page is loaded and any row has a `completed_on` date older than 30 days, a warning banner is displayed above the table:

> *"This report was generated X days ago. To ensure this report reflects the most recent data updates, please regenerate the report before viewing or downloading."*

Where X is the number of days since the oldest stale profile in the current view.

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentProfilesTable.php` | Added `age` field, `onGetAge()` handler, stale banner in `indexAfterAction` |
| `plugins/Institution/src/Model/Table/StaffProfilesTable.php` | Added `age` field, `onGetAge()` handler, stale banner in `indexAfterAction` |
| `plugins/Institution/src/Model/Table/InstitutionsProfileTable.php` | Added `age` field, `onGetAge()` handler, new `indexAfterAction` with stale banner |

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

1. Navigate to **Institutions > Profiles > Student Profiles** (or Staff Profiles / Institution Profiles).
2. Select an Academic Period and a Profile Template.
3. The **Age** column should appear in the table.
4. Rows with a generated profile show a coloured square — hover to see the tooltip.
5. If any profile was generated more than 30 days ago, a yellow warning banner appears above the table prompting regeneration.

### Age thresholds

| Threshold | Indicator |
|-----------|-----------|
| Not generated | Empty grey square |
| < 30 days | Blue square |
| 30 days – 364 days | Yellow square |
| ≥ 365 days | Red square |
| Any row ≥ 30 days old | Warning banner shown |
