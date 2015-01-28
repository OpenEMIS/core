<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'parent' => $parentId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'add', 'parent' => $parentId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('code');
echo $this->Form->input('parent', array('value' => $pathToString, 'disabled'));
$labelOptions['text'] = $this->Label->get('AreaLevel.name');
echo $this->Form->input('area_level_id', array('options' => $areaLevelOptions, 'label' => $labelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'parent' => $parentId)));
echo $this->Form->end();

$this->end();
?>
