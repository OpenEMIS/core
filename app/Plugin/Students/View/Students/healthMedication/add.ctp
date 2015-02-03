<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

$redirectAction = array('action' => 'healthMedication');
$startDate = array('id' => 'startDate', 'label' => $this->Label->get('HealthMedication.start_date'));
$endDate = array('id' => 'endDate', 'label' => $this->Label->get('HealthMedication.end_date'));
if(!empty($this->data[$model]['id'])){
    $redirectAction = array('action' => 'healthMedicationView', $this->data[$model]['id']);
    $startDate['data-date'] = $this->data[$model]['start_date'];
    $endDate['data-date'] = $this->data[$model]['end_date'];
} else if(isset($this->data[$model])) {
    $startDate['data-date'] = $this->data[$model]['start_date'];
    $endDate['data-date'] = $this->data[$model]['end_date'];
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('dosage');
echo $this->FormUtility->datepicker('start_date', $startDate);
echo $this->FormUtility->datepicker('end_date', $endDate);
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>