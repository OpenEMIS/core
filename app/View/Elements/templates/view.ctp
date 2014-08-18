<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model), array('class' => 'divider'));
	if($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
