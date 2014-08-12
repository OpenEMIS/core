<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'parent' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'add', 'parent' => $parentId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('code');
echo $this->Form->input('parent', array('value' => $pathToString, 'disabled'));
echo $this->Form->input('area_level_id', array('options' => $areaLevelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'parent' => $parentId)));
echo $this->Form->end();

$this->end();
?>
