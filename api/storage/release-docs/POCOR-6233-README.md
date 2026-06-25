# POCOR-6233 — Mandatory Checkbox Custom Field: Inline Error Message

## 1. What is the Task?

When a custom field of type **CHECKBOX** is marked as mandatory and the user attempts to save without selecting any option, the system should:
1. Block the save with a validation error (existing behaviour)
2. Show the error message **inline**, directly below the checkbox group — so users can see exactly which field needs attention without relying solely on the generic top-level Alert

## 2. Situation Before

- Validation existed: a mandatory unchecked checkbox prevented save and showed a top-level Alert flash message.
- **No inline error**: the error message appeared only in the top Alert banner. The checkbox field itself showed no red highlight or message, making it hard for users to identify which checkbox field was the problem.
- The generic validation message was `"This field cannot be empty"` — not specific to checkbox behaviour.

## 3. What Was Implemented

### 3.1 Inline error rendering in checkbox template

**`plugins/CustomField/templates/Element/Render/checkbox.php`**

- Added CSS class `error` to the wrapper `<div class="input">` when there is a validation error on this field.
- Rendered a `<div class="error-message">` below the checkbox group — consistent with how text inputs render inline errors via CakePHP's `$form->input()`.

### 3.2 Error detection in RenderCheckboxBehavior

**`plugins/CustomField/src/Model/Behavior/RenderCheckboxBehavior.php`**

- After building the checkbox HTML, reads `$entity->getErrors()` to check for a validation error on `custom_field_values[$seq]['number_value']`.
- Sets `$attr['error']` to the first error message string (or `null` if no error), which the template then uses to decide whether to render the inline error block.

### 3.3 Improved validation message

**`plugins/CustomField/src/Model/Table/CustomFieldValuesTable.php`**

- Updated the `ruleCheckboxMandatory` error message from the generic `"This field cannot be empty"` to the more descriptive: `"Please select at least one option for this required field"`.

### Files Changed Summary

| File | Change |
|------|--------|
| `plugins/CustomField/templates/Element/Render/checkbox.php` | Added `error` CSS class + inline `<div class="error-message">` |
| `plugins/CustomField/src/Model/Behavior/RenderCheckboxBehavior.php` | Extract error from entity and pass to template via `$attr['error']` |
| `plugins/CustomField/src/Model/Table/CustomFieldValuesTable.php` | Improved validation message text |

### Database Migrations

None — no schema changes required.

## 4. Deployment Instructions

1. Pull branch `POCOR-6233` to the target environment.
2. No migrations to run.
3. Clear CakePHP cache:
   ```bash
   ./bin/cake cache clear_all
   ```

## 5. System Administrator Guide

This fix applies to all custom fields of type **CHECKBOX** that are configured with **Mandatory = Yes** in any module that uses the Custom Field plugin (Institution custom fields, Student custom fields, etc.).

**Behaviour after fix:**
- If a user tries to save a form with an unchecked mandatory checkbox custom field, the checkbox group will be highlighted with a red border and the message **"Please select at least one option for this required field"** will appear directly below it.
- The top-level Alert flash message is also still shown.
- No configuration change is needed — the inline error is automatic for all mandatory checkbox custom fields.

**Demo video:** `pocor-6233-checkbox-validation_02.mp4` (recorded on dmo-dev, 20 seconds) — attach to Jira ticket POCOR-6233.
