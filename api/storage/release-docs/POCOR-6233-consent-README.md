# POCOR-6233-consent — Mandatory Checkbox Custom Field: Consent-Style Fix

> **Alternate implementation** — see also `POCOR-6233-v5` (tri-state / silent approach).
> Management to decide which variant to ship.

---

## 1. What is the Task?

Custom checkbox fields marked as mandatory (`is_mandatory = 1`) could be bypassed without any validation error. This variant enforces a **consent-style** rule: a mandatory checkbox group is only valid when **at least one option is checked**. Unchecked (or never touched) equals invalid. A red `*` is shown next to the field label to signal the requirement.

---

## 2. Situation Before

- A mandatory CHECKBOX custom field accepted a save with no checkboxes checked, because CakePHP's hidden sibling always sent `number_value[option_id] = 0`, and `allowEmpty` never triggered.
- No visual indicator distinguished mandatory from optional checkbox groups.

---

## 3. What Was Implemented

### Core Changes

**Consent-style validation rule (`ruleCheckboxMandatory`)**
- Added to `number_value` in `CustomFieldValuesTable::validationDefault()`.
- Only fires when `field_type === 'CHECKBOX'` and `mandatory === 1`.
- Iterates the submitted `number_value` array; passes if at least one value is truthy (checked).
- Returns "This field cannot be empty" if nothing is checked.

**Mandatory label indicator**
- `checkbox.php` template shows a red `*` next to the field label when `required === 'required'`.
- No tri-state / indeterminate / silent-state CSS — checkboxes behave normally at all times.

### Difference vs POCOR-6233-v5 (tri-state)

| Aspect | v5 (tri-state) | consent (this branch) |
|--------|---------------|----------------------|
| "Never touched" | Gray/indeterminate, blocks save | Blocks save (same as unchecked) |
| "Explicitly unchecked" | White, **allows** save | Blocks save |
| "At least one checked" | Allows save | Allows save |
| Extra JS | Yes (indeterminate + event handler) | None |
| Extra hidden field | `has_interacted` | None |
| Visual complexity | Higher (3 states) | Lower (2 states) |

### Files Changed Summary

| Change | File |
|--------|------|
| Modified | `plugins/CustomField/src/Model/Table/CustomFieldValuesTable.php` |
| Modified | `plugins/CustomField/src/Model/Behavior/RenderCheckboxBehavior.php` |
| Modified | `plugins/CustomField/templates/Element/Render/checkbox.php` |
| Added | `api/storage/release-docs/POCOR-6233-consent-README.md` |

**Files Added:** 1  |  **Files Modified:** 3  |  **Files Removed:** 0

### Database Migrations

**Required:** NO
Tables affected: none
Backward compatible: YES

---

## 4. Deployment Instructions (User Experience)

1. `git pull` the branch `POCOR-6233-consent`.
2. No migrations required.
3. Clear CakePHP caches if applicable:
   ```bash
   docker exec -it poe-application /bin/sh -c "cd /var/www/html/emis/core && rm -rf tmp/cache/*"
   ```
4. **Smoke test — mandatory checkbox validation:**
   - Admin → Custom Fields → Institutions → Fields → add a CHECKBOX field, Mandatory = Yes, 2 options; link to a Page.
   - Open Institutions → Add. Confirm a red `*` appears next to the checkbox group label.
   - Click Save without checking anything → expect "This field cannot be empty" error.
   - Check one option → Save → succeeds.
5. **Unchecked = blocked test:**
   - Open a record with a previously checked mandatory checkbox → uncheck all → Save → expect error.
6. **Regression — non-mandatory checkbox:**
   - Non-mandatory CHECKBOX, nothing checked → Save → succeeds.

---

## 5. System Administrator Guide

### Log Locations
- CakePHP logs: `/var/www/html/emis/core/logs/`
- Laravel logs: `/var/www/html/emis/core/api/storage/logs/laravel.log`

### Configuration
No new configuration. Rule activates automatically for CHECKBOX fields where `is_mandatory = 1`.

### Rollback Procedure
```bash
git revert <commit-hash>
# No migration rollback needed
docker exec -it poe-application /bin/sh -c "cd /var/www/html/emis/core && rm -rf tmp/cache/*"
```

### Troubleshooting
| Symptom | Check |
|---------|-------|
| Validation fires for non-mandatory checkbox | Confirm `mandatory` key in `$context['data']` is `0` or absent |
| Validation does not fire for mandatory unchecked checkbox | Confirm `field_type` is set to `CHECKBOX` in context data (set by RecordBehavior) |
| Red `*` not appearing | Confirm `$attr['attr']['required'] === 'required'` is set by RecordBehavior for mandatory fields |
