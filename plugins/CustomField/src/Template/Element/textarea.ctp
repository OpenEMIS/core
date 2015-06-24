<?= $this->Form->input($attr['field'].".textarea_value", $attr['options']); ?>
<?= $this->Form->hidden($attr['field'].".custom_field_id", ['value' => $attr['fieldKey']]); ?>
