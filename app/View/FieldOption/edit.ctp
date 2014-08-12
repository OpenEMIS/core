<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

$params = array('action' => 'view', $selectedOption, $selectedValue);
if(isset($conditionId)) {
	$params = array_merge($params, array($conditionId => $selectedSubOption));
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end(); // end contentActions

$this->start('contentBody');

$formURL = array_merge($params, array('action' => 'edit'));
$formOptions = $this->FormUtility->getFormOptions($formURL);
echo $this->Form->create($fields['model'], $formOptions);
echo $this->element('layout/edit', array('fields' => $fields));
echo $this->FormUtility->getFormButtons(array('cancelURL' => $params));
echo $this->Form->end();

$this->end();
?>
