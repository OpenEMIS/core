<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	if (!empty($this->data[$model]['id'])) {
		$redirectAction = array('action' => 'employmentsView', $this->data[$model]['id']);
	} else {
		$redirectAction = array('action' => 'employments');
	}
	echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('employment_type_id', array('options' => $employmentTypeOptions));
echo $this->FormUtility->datepicker('employment_date');
echo $this->Form->input('comment');
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
