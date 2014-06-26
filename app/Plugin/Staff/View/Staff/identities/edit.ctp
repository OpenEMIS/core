<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'identitiesView', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'identitiesEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('identity_type_id', array('options'=>$identityTypeOptions));
echo $this->Form->input('number'); 
echo $this->FormUtility->datepicker('issue_date', array('id' => 'IssueDate', 'data-date' => $this->data[$model]['issue_date']));
echo $this->FormUtility->datepicker('expiry_date', array('id' => 'ExpiryDate', 'data-date' => $this->data[$model]['expiry_date']));
echo $this->Form->input('issue_location', array('label' => array('text'=> $this->Label->get('Identities.issue_location'), 'class'=>'col-md-3 control-label')));
echo $this->Form->input('comments', array('type'=>'textarea'));

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'identitiesView', $id)));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();

$this->end();
?>
