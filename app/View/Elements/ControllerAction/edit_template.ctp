<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), $_buttons['back']['url'], array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	$formAction = $_triggerFrom == 'Controller' ? array('action' => $action) : array('action' => $model, $action);
	if ($this->action == 'edit') {
		$formAction[] = $this->request->data[$model]['id'];
	} 
	if (isset($params)) {
		$formAction = array_merge($formAction, $paramValues);
	}
	$formOptions = $this->FormUtility->getFormOptions($formAction);
	echo $this->Form->create($model, $formOptions);
	echo $this->element('ControllerAction/edit');
	echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $_buttons['back']['url']));
	echo $this->Form->end();
$this->end();
?>
