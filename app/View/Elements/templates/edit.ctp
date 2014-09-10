<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	$paramValues = array();
	
	if (isset($params)) {
		foreach ($params as $key => $value) {
			if (is_int($key)) {
				$paramValues[] = $value;
			}
		}
	}
	
	$actionParams = array('action' => $model);
	if ($action == 'add') {
		if (isset($params)) {
			if (isset($params['back'])) {
				$actionParams[] = $params['back'];
			} else {
				$actionParams[] = 'index';
			}
			$actionParams = array_merge($actionParams, $paramValues);
		}
	} else if ($action == 'edit') {
		$actionParams[] = 'view';
		$actionParams[] = $this->request->data[$model]['id'];
		if (isset($params)) {
			$actionParams = array_merge($actionParams, $paramValues);
		}
	}
	echo $this->Html->link($this->Label->get('general.back'), $actionParams, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	$formAction = array('action' => $model, $action);
	if ($action == 'edit') {
		$formAction[] = $this->request->data[$model]['id'];
	} 
	if (isset($params)) {
		$formAction = array_merge($formAction, $paramValues);
	}
	$formOptions = $this->FormUtility->getFormOptions($formAction);
	echo $this->Form->create($model, $formOptions);
	echo $this->element('edit');
	echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $actionParams));
	echo $this->Form->end();
$this->end();
?>
