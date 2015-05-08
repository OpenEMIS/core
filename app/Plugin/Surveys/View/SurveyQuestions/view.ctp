<?php
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	if ($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), array_merge(array('action' => 'edit', $data[$Custom_Field]['id']), $params), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array_merge(array('action' => 'delete'), $params), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/view');
$this->end();
?>
