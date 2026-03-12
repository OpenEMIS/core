Generate or update the release documentation for the current branch.

1. Determine the current branch name with `git branch --show-current`.
2. Identify the ticket number (e.g. POCOR-9039) from the branch name.
3. Create or update `api/storage/release-docs/[BRANCH-NAME]-README.md` following the 5-section structure below. If the file already exists, update only the sections affected by recent changes.

## Required structure

```
# [TICKET] - [Short Feature Title]

## 1. What is the Task?
One paragraph describing the feature or fix.

## 2. Situation Before
Bullet list of problems / state that existed before this change.

## 3. What Was Implemented
### Core Changes
Bullet list of what was built.

### Files Changed Summary
- **Added:** N files
- **Modified:** N files
- **Removed:** N files

### Database Migrations
- **Required:** YES / NO
- **Tables affected:** list
- **Backward compatible:** YES / NO

## 4. Deployment Instructions (User Experience)
Numbered steps: git pull, migrations, cache clears, smoke tests.

## 5. System Administrator Guide
- Log locations
- Configuration options
- Cron setup (if any)
- Rollback procedure
- Troubleshooting steps
```

Use `git log --oneline [branch] ^master` and `git show --stat` on relevant commits to identify what changed. Be concise and factual — no padding.
