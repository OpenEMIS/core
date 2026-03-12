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
    echo $this->Form->checkbox('selected_ids[]', [
        'value' => $value,
        'class' => 'no-selection-label',
        'kd-checkbox-radio' => ''
    ]);
}
?>
