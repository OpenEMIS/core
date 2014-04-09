<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array_merge(array('action' => 'index', $selectedOption));
echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
if($_edit) {
	$params = array_merge(array('action' => 'edit', $selectedOption, $selectedValue));//, $parameters);
	echo $this->Html->link(__('Edit'), $params, array('class' => 'divider'));
}
$this->end(); // end contentActions

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
