<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
	echo $this->Html->link(__('Back'), array('action' => 'languages'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'languagesAdd'));
echo $this->Form->create($model, $formOptions);

echo $this->FormUtility->datepicker('evaluation_date', array('id' => 'IssueDate'));
echo $this->Form->input('language_id', array('options'=>$languageOptions));
echo $this->Form->input('listening', array('options'=>$gradeOptions));
echo $this->Form->input('speaking', array('options'=>$gradeOptions));
echo $this->Form->input('reading', array('options'=>$gradeOptions));
echo $this->Form->input('writing', array('options'=>$gradeOptions));

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'languages')));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
