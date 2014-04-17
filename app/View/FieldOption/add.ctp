<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

$params = array('action' => 'index', $selectedOption);
if(isset($conditionId)) {
	$params = array_merge($params, array($conditionId => $selectedSubOption));
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end(); // end contentActions

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'add', $selectedOption));
echo $this->Form->create($fields['model'], $formOptions);
echo $this->Form->hidden('order', array('value' => 0));
echo $this->element('layout/edit', array('fields' => $fields));
echo $this->FormUtility->getFormButtons(array('cancelURL' => $params));
echo $this->Form->end();

$this->end();
?>
