<?= $this->Form->input($attr['field'].".number_value", $attr['options']); ?>
<?= $this->Form->hidden($attr['field'].".custom_field_id", ['value' => $attr['fieldKey']]); ?>
