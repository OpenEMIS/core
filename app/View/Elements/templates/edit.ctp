<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	$params = array('action' => $model);
	if ($action == 'edit') {
		$params[] = 'view';
		$params[] = $this->request->data[$model]['id'];
	}
	echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	$formAction = array('action' => $model, $action);
	if ($action == 'edit') {
		$formAction[] = $this->request->data[$model]['id'];
	}
	$formOptions = $this->FormUtility->getFormOptions($formAction);
	echo $this->Form->create($model, $formOptions);
	echo $this->element('edit');
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $params));
	echo $this->Form->end();
$this->end();
?>
