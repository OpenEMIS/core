<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Categories'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index', 'parent_id' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'add'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
echo $this->Form->input('name', array('type' => 'text'));
if (!empty($parentName)) {
	echo $this->Form->hidden('parent_id', array('value' => $parentId));
	echo $this->Form->input('parent_name', array('value' => $parentName, 'disabled' => 'disabled'));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', 'parent_id' => $parentId)));

echo $this->Form->end();
$this->end();
?>
