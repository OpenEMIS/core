# POCOR-6233-v5 — Mandatory Checkbox Custom Field: Tri-State Fix

---

## 1. What is the Task?

Custom checkbox fields marked as mandatory (`is_mandatory = 1`) could be bypassed without any validation error. Because CakePHP's form helper always emits a hidden `value="0"` sibling for each checkbox input, the POST body always contained a non-empty `number_value` array — meaning `allowEmpty` never fired and the record saved even if the user never touched the field. This ticket adds a **tri-state** checkbox experience (silent → unchecked → checked) that distinguishes "never answered" from "deliberately chose No", and enforces a server-side validation error for mandatory fields left in the silent state.

---

## 2. Situation Before

- A mandatory CHECKBOX custom field accepted a save with no checkboxes touched, because the POST always carried `number_value[option_id] = 0`.
- `allowEmpty('number_value', …)` in `CustomFieldValuesTable` never triggered for checkbox fields.
- No visual indicator differentiated "untouched" from "deliberately unchecked".
- No mandatory asterisk (`*`) was shown next to the checkbox group label.

---

## 3. What Was Implemented

### Core Changes

**Tri-state tracking via `has_interacted` hidden field**
- On new records (no saved values): `has_interacted = 0` — field is in "silent" state.
- On edit records with existing values: `has_interacted = 1` — user previously answered.
- On first checkbox click, JavaScript sets `has_interacted = 1` and exits the silent state.

**Visual silent state**
- A wrapper `<div id="cf-group-{fieldId}" class="cf-checkbox-silent">` is added when mandatory and not yet interacted.
- JS sets `el.indeterminate = true` on all checkboxes in the group — produces the same gray appearance as the disabled `kd-checkbox-radio` style used on the Security → Permissions screen.
- On first interaction, `indeterminate` is cleared, the class is removed, and the hidden field is updated.

**Server-side validation rule (`ruleCheckboxMandatory`)**
- Added to `number_value` in `CustomFieldValuesTable::validationDefault()`.
- Rule only fires when `field_type === 'CHECKBOX'` and `mandatory === 1`.
- Passes when `has_interacted == 1`; fails with "This field cannot be empty" otherwise.

**Mandatory label indicator**
- `checkbox.php` template now shows a red `*` next to the field label when `required === 'required'`.

### Files Changed Summary

| Change | File |
|--------|------|
| Modified | `plugins/CustomField/src/Model/Table/CustomFieldValuesTable.php` |
| Modified | `plugins/CustomField/src/Model/Behavior/RenderCheckboxBehavior.php` |
| Modified | `plugins/CustomField/templates/Element/Render/checkbox.php` |
| Added | `tmp/POCOR-6233-v5/STATUS.md` |
| Added | `api/storage/release-docs/POCOR-6233-v5-README.md` |

**Files Added:** 2  |  **Files Modified:** 3  |  **Files Removed:** 0

### Database Migrations

**Required:** NO
Tables affected: none
Backward compatible: YES — no schema changes; existing saved checkbox values are unaffected.

---

## 4. Deployment Instructions (User Experience)

1. `git pull` the branch `POCOR-6233-v5`.
2. No migrations required — skip `./bin/cake migrations migrate`.
3. Clear CakePHP caches if applicable:
   ```bash
   docker exec -it poe-application /bin/sh -c "cd /var/www/html/emis/core && rm -rf tmp/cache/*"
   ```
4. **Smoke test — mandatory checkbox validation:**
   - Admin → Custom Fields → Institutions → Fields → add a CHECKBOX field with Mandatory = Yes and 2 options; link it to a Page.
   - Open Institutions → Add. Confirm the checkbox group shows gray (indeterminate) options and a red `*` beside the label.
   - Click Save without touching any checkbox → expect "This field cannot be empty" error.
   - Click one checkbox (then uncheck it) → gray state exits → Save → succeeds.
   - Check one option → Save → succeeds.
5. **Regression test — non-mandatory checkbox:**
   - Add a CHECKBOX field with Mandatory = No. Open a record, leave all unchecked, Save → succeeds with no error.
6. **Edit existing record smoke test:**
   - Open a record with previously saved checkbox values → checkboxes load in normal (non-gray) state, `has_interacted = 1`.
   - Open a record with no saved checkbox values and a mandatory checkbox → gray state shown; save without touching → error.

---

## 5. System Administrator Guide

### Log Locations
- CakePHP logs: `/var/www/html/emis/core/logs/`
- Laravel logs: `/var/www/html/emis/core/api/storage/logs/laravel.log`

### Configuration
No new configuration options. The tri-state behaviour activates automatically for all CHECKBOX custom fields where `is_mandatory = 1`.

### Rollback Procedure
```bash
git revert <commit-hash>
# No migration rollback needed (no DB changes)
docker exec -it poe-application /bin/sh -c "cd /var/www/html/emis/core && rm -rf tmp/cache/*"
```

### Troubleshooting
| Symptom | Check |
|---------|-------|
| Checkboxes not showing gray on new mandatory field | Confirm `$attr['attr']['required'] === 'required'` is set in RecordBehavior; inspect rendered HTML for `cf-checkbox-silent` class |
| Validation error fires even after checking a box | Confirm `has_interacted` hidden input is being submitted; check browser DevTools → Network → Form Data |
| Indeterminate state not visible | Browser support for `indeterminate` is universal in modern browsers; confirm JS is not throwing errors in console |
| Non-mandatory checkbox validation fires unexpectedly | Confirm `mandatory` key in `$context['data']` is `0` or absent for non-mandatory fields |
