<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	if (!empty($this->data[$model]['id'])) {
		$redirectAction = array('action' => 'healthFamilyView', $this->data[$model]['id']);
	} else {
		$redirectAction = array('action' => 'healthFamily');
	}
	echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('health_relationship_id', array('options' => $healthRelationshipsOptions, 'label' => array('text' => $this->Label->get('HealthRelationships.name'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('health_condition_id', array('options' => $healthConditionsOptions, 'label' => array('text' => $this->Label->get('HealthCondition.name'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('current', array('options' => $yesnoOptions));
echo $this->Form->input('comment');

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
