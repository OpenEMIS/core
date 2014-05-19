<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_site_classes', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - '. __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'attendanceStudentAbsenceView', $absenceId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attendanceStudentAbsenceEdit', $absenceId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('InstitutionSiteStudentAttendance', $formOptions);

echo $this->Form->hidden('id', array('value' => $absenceId));
//pr($obj);
$objAbsence = $obj['InstitutionSiteStudentAttendance'];
$objStudent = $obj['Student'];
//$objReason = $obj['StudentAbsenceReason'];
$studentIdName = sprintf('%s - %s %s %s %s', $objStudent['identification_no'], $objStudent['first_name'], $objStudent['middle_name'], $objStudent['last_name'], $objStudent['preferred_name'])

?>

<div id="studentAbsenceAdd" class="">
	<?php 
	
	$labelOptions['text'] = $this->Label->get('general.class');
	echo $this->Form->input('institution_site_class_id', array('empty' => __('--Select--'), 'options' => $classOptions, 'label' => $labelOptions, 'id' => 'classId', 'value' => $objAbsence['institution_site_class_id']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.id_name');
	echo $this->Form->hidden('hidden_student_id', array('label' => false, 'div' => false, 'id' => 'hiddenStudentId', 'value' => $objAbsence['student_id']));
	echo $this->Form->input('student_id', array('type' => 'text', 'label' => $labelOptions, 'id' => 'studentNameAutoComplete', 'value' => $studentIdName));
	
	echo $this->FormUtility->datepicker('first_date_absent', array('id' => 'firstDateAbsent', 'data-date' => $objAbsence['first_date_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAttendance.full_day_absent');
	echo $this->Form->input('full_day_absent', array('options' => $fullDayAbsentOptions, 'label' => $labelOptions, 'value' => $objAbsence['full_day_absent']));

	echo $this->FormUtility->datepicker('last_date_absent', array('id' => 'lastDateAbsent', 'data-date' => $objAbsence['last_date_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAttendance.start_time_absent');
	echo $this->Form->input('start_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['start_time_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAttendance.end_time_absent');
	echo $this->Form->input('end_time_absent', array('type' => 'text', 'label' => $labelOptions, 'value' => $objAbsence['end_time_absent']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStudentAttendance.reason');
	echo $this->Form->input('student_absence_reason_id', array('empty' => __('--Select--'), 'options' => $absenceReasonOptions, 'label' => $labelOptions, 'value' => $objAbsence['student_absence_reason_id']));
	
	$labelOptions['text'] = $this->Label->get('general.type');
	echo $this->Form->input('absence_type', array('empty' => __('--Select--'), 'options' => $absenceTypeOptions, 'label' => $labelOptions, 'value' => $objAbsence['absence_type']));
	
	echo $this->Form->input('comment', array(
			'onkeyup' => 'utility.charLimit(this)',
			'type' => 'textarea',
			'value' => $objAbsence['comment']
	));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attendanceStudentAbsenceView', $absenceId)));
	?>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>