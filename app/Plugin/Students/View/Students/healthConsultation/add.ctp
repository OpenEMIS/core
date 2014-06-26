<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'healthConsultationView', $this->data[$model]['id']);
	$setDate = array('data-date' => $this->data[$model]['date']);
}
else{
	$redirectAction = array('action' => 'healthConsultation');
	$setDate = null;
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('date', $setDate);
echo $this->Form->input('health_consultation_type_id', array('options' => $healthConsultationsOptions, 'label'=>array('text'=> $this->Label->get('general.type'),'class'=>'col-md-3 control-label'))); 
echo $this->Form->input('description');
echo $this->Form->input('treatment');

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
