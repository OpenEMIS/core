<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => $_action, 'cycle' => $cycleId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action.'Add', 'cycle' => $cycleId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
echo $this->Form->input('code');
echo $this->Form->input('duration');
echo $this->Form->input('education_cycle_id', array('options' => $cycleOptions, 'disabled'));
echo $this->Form->input('education_field_of_study_id', array('options' => $fieldOfStudyOptions));
echo $this->Form->input('education_certification_id', array('options' => $certificateOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action, 'cycle' => $cycleId)));
echo $this->Form->end();

$this->end();
?>
