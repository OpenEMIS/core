<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array('action' => 'roles');
if (!empty($selectedGroup) && $selectedGroup != -1) {
	$params[] = $selectedGroup;
	$params['action'] = 'rolesUserDefined';
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'rolesEdit', $data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
