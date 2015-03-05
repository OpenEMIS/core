<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array('action' => 'index', $selectedOption);
if(isset($conditionId)) {
	$params = array_merge($params, array($conditionId => $selectedSubOption));
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$editable = (array_key_exists('editable', $data[$model]))? ($data[$model]['editable'] == 1): true;

if($_edit && $editable) {
	$params = array('action' => 'edit', $selectedOption, $selectedValue);
	if(isset($conditionId)) {
		$params = array_merge($params, array($conditionId => $selectedSubOption));
	}
	echo $this->Html->link($this->Label->get('general.edit'), $params, array('class' => 'divider'));
}

if ($_delete && $allowDelete && $editable) {
	$params = array('action' => 'delete', $selectedOption, $selectedValue);
	if(isset($conditionId)) {
		$params = array_merge($params, array($conditionId => $selectedSubOption));
	}
	echo $this->Html->link($this->Label->get('general.delete'), $params, array('class' => 'divider'));
}

	
$this->end();

$this->start('contentBody');
echo $this->element('view');
$this->end();
?>
