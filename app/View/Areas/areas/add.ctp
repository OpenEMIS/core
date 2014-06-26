<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'areas', 'parent' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'areasAdd', 'parent' => $parentId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('code');
echo $this->Form->input('parent', array('value' => $pathToString, 'disabled'));
echo $this->Form->input('area_level_id', array('options' => $areaLevelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'areas', 'parent' => $parentId)));
echo $this->Form->end();

$this->end();
?>
