<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('Staff.leaves', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

	$redirectAction = array('action' => 'leaves');
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('staff_leave_type_id',array('options' => $typeOptions));
echo $this->Form->input('leave_status_id', array('options' => $statusOptions));
echo $this->FormUtility->datepicker('date_from', array('id' => 'StaffLeaveDateFromDay'));
echo $this->FormUtility->datepicker('date_to', array('id' => 'StaffLeaveDateToDay','data-date' => date('d-m-Y', time() + 86400)));
echo $this->Form->input('number_of_days', array('type'=>'number', 'class' => 'form-control', 'id' => 'StaffLeaveNumberOfDays'));
echo $this->Form->input('comments');

$multiple = array('multipleURL' => $this->params['controller']."/leavesAjaxAddField/");
echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
echo $this->element('templates/file_upload', compact('multiple'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
