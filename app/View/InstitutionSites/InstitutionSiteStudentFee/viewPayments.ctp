<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	$actionParams = array('action' => $model);
	if (isset($params)) {
		$actionParams[] = 'index';
		$actionParams = array_merge($actionParams, $params);
	}
	echo $this->Html->link($this->Label->get('general.back'), $actionParams, array('class' => 'divider'));
	
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', $data[$model]['student_id'], $data[$model]['institution_site_fee_id']), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
