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
echo $this->Html->link(__('Back'), array('action' => 'attendanceStaffAbsence'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'attendanceStaffAbsenceAdd'));
$formOptions['type'] = 'file';
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('InstitutionSiteStaffAbsence', $formOptions);
?>

<div id="staffAbsenceAdd" class="">
	<?php 
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.id_name');
	echo $this->Form->hidden('hidden_staff_id', array('label' => false, 'div' => false, 'id' => 'hiddenStaffId'));
	echo $this->Form->input('staff_id', array('type' => 'text', 'label' => $labelOptions, 'id' => 'staffNameAutoComplete'));
	
	echo $this->FormUtility->datepicker('first_date_absent', array('id' => 'firstDateAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.full_day_absent');
	echo $this->Form->input('full_day_absent', array('options' => $fullDayAbsentOptions, 'label' => $labelOptions, 'id' => 'fullDayAbsent'));

	echo $this->FormUtility->datepicker('last_date_absent', array('id' => 'lastDateAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.start_time_absent');
	echo $this->Form->input('start_time_absent', array('type' => 'text', 'label' => $labelOptions, 'id' => 'startTimeAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.end_time_absent');
	echo $this->Form->input('end_time_absent', array('type' => 'text', 'label' => $labelOptions, 'id' => 'endTimeAbsent'));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSiteStaffAbsence.reason');
	echo $this->Form->input('staff_absence_reason_id', array('empty' => __('--Select--'), 'options' => $absenceReasonOptions, 'label' => $labelOptions));
	
	$labelOptions['text'] = $this->Label->get('general.type');
	echo $this->Form->input('absence_type', array('empty' => __('--Select--'), 'options' => $absenceTypeOptions, 'label' => $labelOptions));
	
	echo $this->Form->input('comment', array(
			'onkeyup' => 'utility.charLimit(this)',
			'type' => 'textarea'
	));
	
	$multiple = array('multipleURL' => $this->params['controller']."/attendanceStaffAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
	echo $this->element('templates/file_upload', compact('multiple'));
	
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'attendanceStaffAbsence')));
	?>
</div>
<?php 
echo $this->Form->end();
$this->end(); 
?>