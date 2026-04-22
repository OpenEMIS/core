# POCOR-9593 — Profile Age Indicator & Stale Report Banner

## 1. What is the Task?

Display a notification banner for all profile features when the client views or downloads a profile. If profiles are older than 1 month, show an alert banner. Additionally, add a visual age indicator column to all profile index tables.

## 2. Situation Before

- Profile index tables (Student, Staff, Institution, Classes) showed no indication of how old a generated profile was.
- Users had no warning when viewing or downloading a profile that may be out of date.
- The generation window "not enabled" message (POCOR-9598) showed alone with no stale-data context.
- InstitutionsProfileTable had no "not enabled" banner at all when the generation window was closed.

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

**Generation window closed + stale profile(s) ≥ 30 days** (replaces the plain "not enabled" message):
- 1 stale profile: *"This profile template generation is not enabled. Consult with system administrator to check the dates. Note: this report is X days old and may not reflect the most recent data."*
- 2+ stale profiles: *"This profile template generation is not enabled. Consult with system administrator to check the dates. Note: some reports are up to X days old and may not reflect the most recent data."*

**Generation window closed + no stale profiles (all records < 30 days old or not yet generated):**
Only the original "not enabled" message is shown: *"This profile template generation is not enabled. Consult with system administrator to check the dates."*

**Generation window open + no stale profiles:** No banner shown.

X is the number of days since the oldest stale `completed_on` in the current view.

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/Institution/src/Model/Table/StudentProfilesTable.php` | Age field (first, no label), `onGetAge()`, stale banner with singular/plural + window-open/closed logic |
| `plugins/Institution/src/Model/Table/StaffProfilesTable.php` | Same |
| `plugins/Institution/src/Model/Table/InstitutionsProfileTable.php` | Same + new `indexAfterAction` method + plain "not enabled" banner when window closed and no stale data |
| `plugins/Institution/src/Model/Table/ClassesProfilesTable.php` | Same (stale banner added inside existing `indexAfterAction`); `institution_id` fallback to `getInstitutionID()` |

### Bug Fix: `BadMethodCallException: Unknown method formatDateTime` (404 on profile pages)

All profile index pages threw a `BadMethodCallException` when rendering rows with non-empty `started_on` / `completed_on` dates, causing CakePHP to serve a 404. Root cause: `formatDateTime()` is defined in `AppTable` via POCOR-9510, which is not merged into this branch. POCOR-9639 added `!empty()` guards but left the method call intact, so rows with actual dates still failed.

Fix: replaced `$this->formatDateTime($value)` with `$value->format('Y-m-d H:i:s')` directly on the FrozenTime / Time object in **11 profile table files** across 4 plugins:

| Plugin | Files |
|--------|-------|
| `plugins/Institution/` | `StudentProfilesTable`, `StaffProfilesTable`, `InstitutionsProfileTable`, `ClassesProfilesTable` |
| `plugins/ProfileTemplate/` | `StudentProfilesTable`, `StaffProfilesTable`, `ClassesProfilesTable` |
| `plugins/Profile/` | `StudentProfilesTable`, `StaffProfilesTable` |
| `plugins/Directory/` | `StudentProfilesTable`, `StaffProfilesTable` |

### Database Migrations

None. No database changes required.

## 4. Deployment Instructions

1. Pull the `POCOR-9593` branch.
2. Clear the CakePHP cache:
   ```bash
   php bin/cake.php cache clear_all
   ```
3. No database migration required.

## 5. Known Issues (Pre-existing, Not Introduced by This Branch)

### Classes Profiles — Generate All Not Working

The **Generate All** button on the Classes Profiles index page does not function. The `institution_id` is missing from the query string when the generateAll URL is constructed, causing `beforeFilter` to reject the request with: *"You should put institution_id into query string first"*.

A partial fix was applied in this branch (fallback to `$this->getInstitutionID()` in `indexAfterAction`), but end-to-end generation via the toolbar button remains broken. Needs dedicated investigation.

### Generate Button — Background Generation Does Not Update Row Status

When a profile is already generating and the user clicks Generate on another row, the system responds with a "will be done in background" message but the row status does not visibly change to **In Progress** until a full page reload. Pre-existing behaviour; not introduced by this branch.

---

## 6. System Administrator Guide

### How to test

1. Navigate to **Institutions > Profiles > Student Profiles** (or Staff / Institution / Classes Profiles).
2. Select an Academic Period and a Profile Template.
3. The first (unlabelled) column should show coloured squares — hover to see the tooltip.
4. **Test stale banner (window open):** select a template whose `generate_start_date`/`generate_end_date` includes today, with at least one row where `completed_on` > 30 days ago → yellow warning banner appears with exact day count.
5. **Test combined banner (window closed + stale):** select a template whose generation window has expired, with at least one row where `completed_on` > 30 days ago → combined "not enabled + X days old" banner appears.
6. **Test plain not-enabled banner (window closed, no stale):** select a closed-window template with all rows generated recently (< 30 days) or not yet generated → plain "not enabled" banner appears with no day count.
7. **Test no banner (window open, all fresh):** select an open-window template with all rows generated recently → no banner.

### Age thresholds

| Threshold | Indicator |
|-----------|-----------|
| Not generated | Empty grey square |
| < 30 days | Blue square |
| 30 days – 364 days | Yellow square |
| ≥ 365 days | Red square |
| Any row ≥ 30 days + window open | Stale regenerate banner |
| Any row ≥ 30 days + window closed | Combined not-enabled + stale banner |
| No stale rows + window closed | Plain not-enabled banner |
