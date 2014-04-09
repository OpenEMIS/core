<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

$params = array_merge(array('action' => 'index', $selectedOption));
echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));

$this->end(); // end contentActions

$this->start('contentBody');

echo $this->Form->create($fields['model'], array(
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));
echo $this->Form->hidden('order', array('value' => 0));
echo $this->element('layout/edit', array('fields' => $fields));
?>

<div class="controls view_controls">
	<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'index', $selectedOption), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php $this->end(); ?>

