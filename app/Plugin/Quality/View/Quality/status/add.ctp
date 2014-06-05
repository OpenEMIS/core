<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'status'), array('class' => 'divider', 'id' => 'back'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action), 'file');
echo $this->Form->create($model, $formOptions);
if (!empty($this->data[$model]['id'])) {
	echo $this->Form->input('id', array('type' => 'hidden'));
}
$disabled = false;
$dateEnabled = isset($this->data[$model]['date_enabled'])? $this->data[$model]['date_enabled']:date('d-m-Y');
$dateDisabled = isset($this->data[$model]['date_disabled'])? $this->data[$model]['date_disabled']:date('d-m-Y', time() + 86400);

if ($displayType != 'add') {
	$disabled = 'disabled';
}

echo $this->Form->input('rubric_template_id', array('disabled' => $disabled, 'options' => $rubricOptions));
echo $this->Form->input('year', array('disabled' => $disabled, 'options' => $yearOptions));
echo $this->FormUtility->datepicker('date_enabled', array('id' => 'DateEnabled', 'data-date' => $dateEnabled));
echo $this->FormUtility->datepicker('date_disabled', array('id' => 'DateDisabled', 'data-date' => $dateDisabled));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'status')));
echo $this->Form->end();
$this->end();
?>  
