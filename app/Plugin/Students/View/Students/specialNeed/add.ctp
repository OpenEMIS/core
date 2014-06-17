<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

if (!$WizardMode) {
	if (!empty($this->data[$model]['id'])) {
		$redirectAction = array('action' => 'specialNeedView', $this->data[$model]['id']);
	} else {
		$redirectAction = array('action' => 'specialNeed');
	}
	echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('id', array('type' => 'hidden'));
echo $this->FormUtility->datepicker('special_need_date', array('label'=>$this->Label->get('general.date')));
echo $this->Form->input('special_need_type_id', array('options' => $specialNeedTypeOptions,'label'=>array('text'=> $this->Label->get('general.type'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('comment');

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();

$this->end();
?>
