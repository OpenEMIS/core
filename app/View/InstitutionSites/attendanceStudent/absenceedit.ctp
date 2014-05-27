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

echo $this->Html->css('../js/plugins/timepicker/bootstrap-timepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/timepicker/bootstrap-timepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - '. __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'attendanceStudentAbsenceView', $absenceId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attendanceStudentAbsenceEdit', $absenceId));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['type'] = 'file';
$formOptions['deleteUrl']=$this->params['controller']."/attendanceStudentAttachmentDelete/";
echo $this->Form->create('InstitutionSiteStudentAbsence', $formOptions);

echo $this->Form->hidden('id', array('value' => $absenceId));
//pr($obj);
$objAbsence = $obj['InstitutionSiteStudentAbsence'];

if(isset($obj['InstitutionSiteStudentAbsence']['hidden_student_id'])){
	$studentIdName = $obj['InstitutionSiteStudentAbsence']['student_id'];
	$objAbsence['student_id'] = $obj['InstitutionSiteStudentAbsence']['hidden_student_id'];
}else{
	$objStudent = $obj['Student'];
	$studentIdName = sprintf('%s - %s %s %s %s', $objStudent['identification_no'], $objStudent['first_name'], $objStudent['middle_name'], $objStudent['last_name'], $objStudent['preferred_name']);
}

?>

<div id="studentAbsenceAdd" class="">
	<?php 
	
	$labelOptions['text'] = $this->Label->get('general.class');
	echo $this->Form->input('institution_site_class_id', array('empty' => __('--Select--'), 'options' => $classOptions, 'label' => $labelOptions, 'id' => 'classId', 'value' => $objAbsence['institution_site_class_id']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.id_name');
	echo $this->Form->hidden('hidden_student_id', array('label' => false, 'div' => false, 'id' => 'hiddenStudentId', 'value' => $objAbsence['student_id']));
	echo $this->Form->input('student_id', array('type' => 'text', 'label' => $labelOptions, 'id' => 'studentNameAutoComplete', 'value' => $studentIdName));
	
	echo $this->FormUtility->datepicker('first_date_absent', array('id' => 'firstDateAbsent', 'data-date' => $objAbsence['first_date_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAbsence.full_day_absent');
	echo $this->Form->input('full_day_absent', array('options' => $fullDayAbsentOptions, 'label' => $labelOptions, 'value' => $objAbsence['full_day_absent'], 'id' => 'fullDayAbsent'));

	echo $this->FormUtility->datepicker('last_date_absent', array('id' => 'lastDateAbsent', 'data-date' => $objAbsence['last_date_absent']));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAbsence.start_time_absent');
	//echo $this->Form->input('start_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['start_time_absent'], 'id' => 'startTimeAbsent'));
	echo $this->FormUtility->timepicker('start_time_absent', array('id' => 'startTimeAbsent'));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAbsence.end_time_absent');
	//echo $this->Form->input('end_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['end_time_absent'], 'id' => 'endTimeAbsent'));
	echo $this->FormUtility->timepicker('end_time_absent', array('id' => 'endTimeAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAbsence.reason');
	echo $this->Form->input('student_absence_reason_id', array('empty' => __('--Select--'), 'options' => $absenceReasonOptions, 'label' => $labelOptions, 'value' => $objAbsence['student_absence_reason_id']));
	
	$labelOptions['text'] = $this->Label->get('general.type');
	echo $this->Form->input('absence_type', array('empty' => __('--Select--'), 'options' => $absenceTypeOptions, 'label' => $labelOptions, 'value' => $objAbsence['absence_type']));
	
	echo $this->Form->input('comment', array(
			'onkeyup' => 'utility.charLimit(this)',
			'type' => 'textarea',
			'value' => $objAbsence['comment']
	));
	
	$multiple = array('multipleURL' => $this->params['controller']."/attendanceStudentAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
	echo $this->element('templates/file_upload', compact('multiple'));
	
	$tableHeaders = array(__('File(s)'), '&nbsp;');
	$tableData = array();
	foreach ($attachments as $obj) {
		$row = array();
		$row[] = array($obj['InstitutionSiteStudentAbsenceAttachment']['file_name'], array('file-id' =>$obj['InstitutionSiteStudentAbsenceAttachment']['id']));
		$row[] = '<span class="icon_delete" title="'. $this->Label->get('general.delete').'" onClick="jsForm.deleteFile('.$obj['InstitutionSiteStudentAbsenceAttachment']['id'].')"></span>';
		$tableData[] = $row;
	}
	echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attendanceStudentAbsenceView', $absenceId)));
	?>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>