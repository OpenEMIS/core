<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

if (!empty($this->data[$model]['id'])) {
	$redirectAction = array('action' => 'healthImmunizationView', $this->data[$model]['id']);
} else {
	$redirectAction = array('action' => 'healthImmunization');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
$setDate = isset($this->data[$model]['date']) ? $this->data[$model]['date'] : null;
echo $this->FormUtility->datepicker('date', array('data-date' => $setDate));
echo $this->Form->input('health_immunization_id', array('options' => $healthImmunizationsOptions, 'label' => array('text' => $this->Label->get('HealthImmunization.name'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('dosage');
echo $this->Form->input('comment');

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
