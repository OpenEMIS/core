<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index', 'category_id' => $categoryId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'add', 'category_id' => $categoryId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
echo $this->Form->input('name', array('type' => 'text'));
if (!empty($categoryName)) {
	echo $this->Form->hidden('infrastructure_category_id', array('value' => $categoryId));
	echo $this->Form->input('category_name', array('value' => $categoryName, 'disabled' => 'disabled'));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', 'category_id' => $categoryId)));

echo $this->Form->end();
$this->end();
?>
