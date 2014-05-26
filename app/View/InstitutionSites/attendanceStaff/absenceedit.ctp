<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - '. __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'attendanceStaffAbsenceView', $absenceId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attendanceStaffAbsenceEdit', $absenceId));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['type'] = 'file';
$formOptions['deleteUrl']=$this->params['controller']."/attendanceStaffAttachmentDelete/";
echo $this->Form->create('InstitutionSiteStaffAbsence', $formOptions);

echo $this->Form->hidden('id', array('value' => $absenceId));
//pr($obj);
$objAbsence = $obj['InstitutionSiteStaffAbsence'];

if(isset($obj['InstitutionSiteStaffAbsence']['hidden_staff_id'])){
	$staffIdName = $obj['InstitutionSiteStaffAbsence']['staff_id'];
	$objAbsence['staff_id'] = $obj['InstitutionSiteStaffAbsence']['hidden_staff_id'];
}else{
	$objStaff = $obj['Staff'];
	$staffIdName = sprintf('%s - %s %s %s %s', $objStaff['identification_no'], $objStaff['first_name'], $objStaff['middle_name'], $objStaff['last_name'], $objStaff['preferred_name']);
}

?>

<div id="staffAbsenceAdd" class="">
	<?php 
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.id_name');
	echo $this->Form->hidden('hidden_staff_id', array('label' => false, 'div' => false, 'id' => 'hiddenStaffId', 'value' => $objAbsence['staff_id']));
	echo $this->Form->input('staff_id', array('type' => 'text', 'label' => $labelOptions, 'id' => 'staffNameAutoComplete', 'value' => $staffIdName));
	
	echo $this->FormUtility->datepicker('first_date_absent', array('id' => 'firstDateAbsent', 'data-date' => $objAbsence['first_date_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.full_day_absent');
	echo $this->Form->input('full_day_absent', array('options' => $fullDayAbsentOptions, 'label' => $labelOptions, 'value' => $objAbsence['full_day_absent'], 'id' => 'fullDayAbsent'));

	echo $this->FormUtility->datepicker('last_date_absent', array('id' => 'lastDateAbsent', 'data-date' => $objAbsence['last_date_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.start_time_absent');
	echo $this->Form->input('start_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['start_time_absent'], 'id' => 'startTimeAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.end_time_absent');
	echo $this->Form->input('end_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['end_time_absent'], 'id' => 'endTimeAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.reason');
	echo $this->Form->input('staff_absence_reason_id', array('empty' => __('--Select--'), 'options' => $absenceReasonOptions, 'label' => $labelOptions, 'value' => $objAbsence['staff_absence_reason_id']));
	
	$labelOptions['text'] = $this->Label->get('general.type');
	echo $this->Form->input('absence_type', array('empty' => __('--Select--'), 'options' => $absenceTypeOptions, 'label' => $labelOptions, 'value' => $objAbsence['absence_type']));
	
	echo $this->Form->input('comment', array(
			'onkeyup' => 'utility.charLimit(this)',
			'type' => 'textarea',
			'value' => $objAbsence['comment']
	));
	
	$multiple = array('multipleURL' => $this->params['controller']."/attendanceStaffAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
	echo $this->element('templates/file_upload', compact('multiple'));
	
	$tableHeaders = array(__('File(s)'), '&nbsp;');
	$tableData = array();
	foreach ($attachments as $obj) {
		$row = array();
		$row[] = array($obj['InstitutionSiteStaffAbsenceAttachment']['file_name'], array('file-id' =>$obj['InstitutionSiteStaffAbsenceAttachment']['id']));
		$row[] = '<span class="icon_delete" title="'. $this->Label->get('general.delete').'" onClick="jsForm.deleteFile('.$obj['InstitutionSiteStaffAbsenceAttachment']['id'].')"></span>';
		$tableData[] = $row;
	}
	echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attendanceStaffAbsenceView', $absenceId)));
	?>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>