<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'positionsView', $this->data[$model]['id']);
}
else{
	$redirectAction = array('action' => 'positions');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action));
$labelDefault = $formOptions;
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('position_no', array('label'=>array('text'=> $this->Label->get('Position.number'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('staff_position_title_id', array('options' => $positionTitleOptions, 'label'=>array('text'=> $this->Label->get('general.title'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('staff_position_grade_id', array('options' => $positionGradeOptions, 'label'=>array('text'=> $this->Label->get('general.grade'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('type', array('options' => $yesnoOptions, 'label'=>array('text'=> $this->Label->get('Position.teaching'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('status', array('options' => $enableOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
