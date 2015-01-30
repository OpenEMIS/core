<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index', $levelId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'add', $levelId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->hidden('id');
echo $this->Form->input('name', array('type' => 'text'));
if (!empty($levelName)) {
	echo $this->Form->hidden('infrastructure_level_id', array('value' => $levelId));
	echo $this->Form->input('level_name', array('value' => $levelName, 'disabled' => 'disabled'));
}
echo $this->Form->input('visible', array('options' => $visibleOptions));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', $levelId)));

echo $this->Form->end();
$this->end();
?>
