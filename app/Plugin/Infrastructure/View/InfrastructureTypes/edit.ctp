<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));

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
	echo $this->Form->hidden('infrastructure_category_id', array('value' => $category['InfrastructureCategory']['id']));
	echo $this->Form->input('category_name', array('value' => $category['InfrastructureCategory']['name'], 'disabled' => 'disabled'));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));

echo $this->Form->end();
$this->end();
?>
