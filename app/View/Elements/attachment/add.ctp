<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Attachment'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attachmentsAdd'));
echo $this->Form->create($_model, $formOptions);

echo $this->Form->input('name');
echo $this->Form->input('description', array('type' => 'textarea'));
echo $this->element('templates/file_upload');
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attachments')));
echo $this->Form->end();

$this->end();
?>
