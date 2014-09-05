<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	$actionParams = array('action' => $model);
	if (isset($params)) {
		$actionParams[] = 'index';
		$actionParams = array_merge($actionParams, $params);
	}
	echo $this->Html->link($this->Label->get('general.back'), $actionParams, array('class' => 'divider'));
	$actionParams = array('action' => $model);
	$actionParams[] = 'edit';
	$actionParams[] = $data[$model]['id'];
	if (isset($params)) {
		$actionParams = array_merge($actionParams, $params);
	}
	if ($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), $actionParams, array('class' => 'divider'));
	}
	
	$actionParams = array('action' => $model);
	$actionParams[] = 'remove';
	if (isset($params)) {
		$actionParams = array_merge($actionParams, $params);
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), $actionParams, array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
