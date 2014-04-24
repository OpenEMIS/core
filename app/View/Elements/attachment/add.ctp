<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attachmentsAdd', 'plugin' => false));
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('name');
echo $this->Form->input('description', array('type' => 'textarea'));
echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
echo $this->element('templates/file_upload');
//echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attachments')));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'attachments'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));
echo $this->Form->end();

$this->end();
?>
