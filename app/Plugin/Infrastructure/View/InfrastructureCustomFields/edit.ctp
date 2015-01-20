<?php
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	if(isset($this->request->data[$Custom_Field]['id'])) {	//edit
		echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'view', $this->request->data[$Custom_Field]['id']), $params), array('class' => 'divider'));
	} else { //new
		echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/edit', compact('params'));
$this->end();
?>
