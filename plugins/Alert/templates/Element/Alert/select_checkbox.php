<?php
/**
 * Checkbox column for index tables
 * Used for multiselect functionality with tableCheckable plugin.
 */
if (empty($entity)) {
    // Header row: render master checkbox with same structure as rows
    echo '<div class="selection-wrapper"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/><label></label></div>';
} else {
    $value = $entity->id ?? '';
    // Output raw HTML checkbox to avoid FormHelper issues
    echo '<div class="selection-wrapper"><input type="checkbox" name="selected_ids[]" value="' . h($value) . '" class="no-selection-label" kd-checkbox-radio><label></label></div>';
}
?>
