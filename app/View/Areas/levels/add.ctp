<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'levels'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'levelsAdd'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'levels')));
echo $this->Form->end();

$this->end();
?>
