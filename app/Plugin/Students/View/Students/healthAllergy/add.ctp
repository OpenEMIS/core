<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'healthAllergyView', $this->data[$model]['id']);
}
else{
	$redirectAction = array('action' => 'healthAllergy');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('health_allergy_type_id', array('options' => $healthAllergiesOptions, 'label'=>array('text'=> $this->Label->get('general.type'),'class'=>'col-md-3 control-label'))); 
echo $this->Form->input('description');
echo $this->Form->input('severe', array('options' => $yesnoOptions));
echo $this->Form->input('comment', array('type'=>'textarea'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
