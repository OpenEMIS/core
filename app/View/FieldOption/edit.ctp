<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

$params = array_merge(array('action' => 'view', $selectedOption, $selectedValue));
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end(); // end contentActions

$this->start('contentBody');

echo $this->Form->create($fields['model'], array(
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
)); 
echo $this->element('layout/edit', array('fields' => $fields));
?>

<div class="controls view_controls">
	<input type="submit" value="<?php echo $this->Label->get('general.save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'view', $selectedOption, $selectedValue), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php $this->end(); ?>

