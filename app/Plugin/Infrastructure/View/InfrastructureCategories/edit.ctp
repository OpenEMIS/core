<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Categories'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'view', $id), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'edit', $id));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
echo $this->Form->input('name', array('type' => 'text'));
if (!empty($category)) {
	$labelOptions['text'] = $this->Label->get('Infrastructure.parent_category');
	echo $this->Form->input('parent_category', array('value' => $category['InfrastructureCategory']['name'], 'disabled' => 'disabled', 'label' => $labelOptions));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));

echo $this->Form->end();
$this->end();
?>
