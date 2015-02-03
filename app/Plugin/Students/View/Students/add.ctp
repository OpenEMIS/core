<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('institution_site_students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Student.add_existing'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => $this->action));
//$formOptions['autocompleteURL'] = $this->params['controller'] . "/$model/studentsAjaxFind/";
//$formOptions['id'] = "AddStudentForm";
//$formOptions['inputDefaults']['autocomplete'] =  'off';

$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('student_id', array('id' => 'StudentId'));

$labelOptions['text'] = $this->Label->get('general.openemisId');
echo $this->Form->input('search', array('label' => $labelOptions, 'id' => 'studentNameAutoComplete', 'placeholder' => __('OpenEMIS ID, First Name or Last Name'),));

$labelOptions['text'] = $this->Label->get('general.academic_period');
echo $this->Form->input('academic_period_id', array(
	'class' => 'form-control',
	'options' => $academicPeriodOptions,
	'url' => 'InstitutionSites/programmesOptions',
	'academicPeriodUrl' => 'InstitutionSites/studentsAjaxStartDate',
	'onchange' => 'InstitutionSiteStudents.getProgrammeOptions(this)',
	'label' => $labelOptions
));

$labelOptions['text'] = $this->Label->get('InstitutionSite.programme');
echo $this->Form->input('institution_site_programme_id', array('id' => 'InstitutionSiteProgrammeId', 'class' => 'form-control', 'options' => $programmeOptions, 'label' => $labelOptions));

// status set to 1(Current Student) by default on student add page, refer to PHPOE-870 
//$labelOptions['text'] = $this->Label->get('general.status');
//echo $this->Form->input('student_status_id', array('class' => 'form-control', 'options' => $statusOptions, 'label' => $labelOptions));

echo $this->FormUtility->datepicker('start_date', array('id' => 'startDate', 'data-date' => $academicPeriodData['AcademicPeriod']['start_date'],'startDate' => $academicPeriodData['AcademicPeriod']['start_date'], 'endDate' => $academicPeriodData['AcademicPeriod']['end_date']));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'students')));
echo $this->Form->end();
$this->end();
?>
