<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => $_action), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action.'Add'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('code');
echo $this->Form->input('name');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action)));
echo $this->Form->end();

$this->end();
?>
