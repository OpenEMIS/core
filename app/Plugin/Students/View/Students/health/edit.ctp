<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'healthView'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'healthEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('blood_type', array('options' => $bloodTypeOptions)); 
echo $this->Form->input('doctor_name');
echo $this->Form->input('doctor_contact');
echo $this->Form->input('medical_facility');
echo $this->Form->input('health_insurance', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'healthView')));

echo $this->Form->end();

$this->end();
?>
