<?php
/**
 * Checkbox column for index tables
 * Used for multiselect functionality with tableCheckable plugin.
 */
if (empty($entity)) {
    // Header row: JS will inject master checkbox
    echo '';
} else {
    $value = $entity->id ?? '';
    // Output raw HTML checkbox to avoid FormHelper issues
    echo '<input type="checkbox" name="selected_ids[]" value="' . h($value) . '" class="no-selection-label" kd-checkbox-radio>';
}
?>
