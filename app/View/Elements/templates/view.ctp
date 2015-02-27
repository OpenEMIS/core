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

	// Back link
	$actionParams = $_triggerFrom == 'Controller' ? array('action' => 'index') : array('action' => $model);
	if (isset($params)) {
		if (isset($params['back'])) {
			if (is_array($params['back'])) {
				$actionParams = array_merge($actionParams, $params['back']);
			} else {
				$actionParams[] = $params['back'];
			}
		} else {
			$actionParams[] = 'index';
		}
		$actionParams = array_merge($actionParams, $paramValues);
	}
	echo $this->Html->link($this->Label->get('general.back'), $actionParams, array('class' => 'divider'));

	// Edit link
	$actionParams = array();
	if ($_triggerFrom == 'Controller') {
		$actionParams = array('action' => 'edit');
	} else {
		$actionParams = array('action' => $model);
		$actionParams[] = 'edit';
	}
	
	if (isset($data[$model]['id'])) {
		$actionParams[] = $data[$model]['id'];
	}
	
	if (isset($params)) {
		$actionParams = array_merge($actionParams, $paramValues);
	}
	if ($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), $actionParams, array('class' => 'divider'));
	}
	
	// Delete link
	$actionParams = array();
	if ($_triggerFrom == 'Controller') {
		$actionParams = array('action' => 'remove');
	} else {
		$actionParams = array('action' => $model);
		$actionParams[] = 'remove';
	}
	
	if (isset($params)) {
		$actionParams = array_merge($actionParams, $paramValues);
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), $actionParams, array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
