<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Section'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/tabs', array());

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'singleGradeAdd', $selectedAcademicPeriod, $selectedGradeId));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));

$labelOptions['text'] = $this->Label->get('general.academic_period');
echo $this->Form->input('academic_period_id', array(
	'options' => $academicPeriodOptions, 
	'url' => $this->params['controller'] . '/' . $model . '/singleGradeAdd',
	'default' => $selectedAcademicPeriod,
	'onchange' => 'jsForm.change(this)',
	'label' => $labelOptions
));
echo $this->Form->input('education_grade_id', array(
	'options' => $gradeOptions, 
	'default' => $selectedGradeId,
	'url' => $this->params['controller'] . '/' . $model . '/singleGradeAdd/' . $selectedAcademicPeriod,
	'onchange' => 'jsForm.change(this)'
));
//echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteSection.institution_site_shift_id');
echo $this->Form->input('institution_site_shift_id', array('options' => $shiftOptions, 'label' => $labelOptions));

echo $this->Form->input('number_of_sections', array(
	'options' => $numberOfSectionsOptions, 
	'value' => $numberOfSections,
	'onchange' => "$('#reload').click()"
));

echo $this->element('../InstitutionSites/InstitutionSiteSection/single_grade_sections');

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model, 'index', $selectedAcademicPeriod)));
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->Form->end();

$this->end(); 
?>
