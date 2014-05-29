<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Back'), array('action' => $_action.'View', $this->data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action.'Edit'));
echo $this->Form->create($model, $formOptions);
echo $this->element('layout/edit', array('fields' => $fields, 'data' => $this->data));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action.'View', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end();
?>
