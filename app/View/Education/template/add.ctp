<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => $model, $_condition => $conditionId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $model, 'add', $_condition => $conditionId));
echo $this->Form->create($model, $formOptions);
echo $this->element('edit');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, $_condition => $conditionId)));
echo $this->Form->end();

$this->end();
?>
