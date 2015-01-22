<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Levels'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index', 'parent_id' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'add', 'parent_id' => $parentId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
echo $this->Form->input('name', array('type' => 'text'));
if (!empty($parentName)) {
	echo $this->Form->hidden('parent_id', array('value' => $parentId));
	$labelOptions['text'] = $this->Label->get('Infrastructure.parent_level');
	echo $this->Form->input('parent_level', array('value' => $parentName, 'disabled' => 'disabled', 'label' => $labelOptions));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', 'parent_id' => $parentId)));

echo $this->Form->end();
$this->end();
?>
