<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'view', $this->request->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit', $this->request->data[$model]['id']));
	echo $this->Form->create($model, $formOptions);
	echo $this->element('edit');
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'view', $this->request->data[$model]['id'])));
	echo $this->Form->end();
$this->end();
?>
