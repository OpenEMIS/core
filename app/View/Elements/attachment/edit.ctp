<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'attachmentsView', $id), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attachmentsEdit', 'plugin' => false));
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('id');
echo $this->Form->input('name');
echo $this->Form->input('description', array('type' => 'textarea'));

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attachmentsView', $id)));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}
echo $this->Form->end();

$this->end();
?>

