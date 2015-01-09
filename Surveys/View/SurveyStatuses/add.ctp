<?php
echo $this->Html->css('../js/plugins/chosen/chosen.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/chosen/chosen.jquery.min', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'add'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyStatus', $formOptions);
		$labelOptions['text'] = __('Template');
		echo $this->Form->input('survey_template_id', array('options' => $templateOptions, 'label' => $labelOptions));
		echo $this->FormUtility->datepicker('date_enabled', array('id' => 'SurveyStatusDateEnabled'));
		echo $this->FormUtility->datepicker('date_disabled', array('id' => 'SurveyStatusDateDisabled'));
		$labelOptions['text'] = __('Academic Period Type');
		//echo $this->Form->input('academic_period_type_id', array('options' => $academicPeriodTypeOptions, 'label' => $labelOptions, 'default' => $selectedAcademicPeriodType, 'url' => $this->params['controller'] . '/add', 'onchange' => 'jsForm.change(this)'));
		echo $this->Form->input('academic_period_type_id', array('options' => $academicPeriodTypeOptions, 'label' => $labelOptions, 'default' => $selectedAcademicPeriodType));
		$labelOptions['text'] = __('Academic Periods');
		echo $this->Form->input('AcademicPeriod.AcademicPeriod', array('options' => $academicPeriodOptions, 'class' => 'chosen-select', 'label' => $labelOptions, 'multiple' => true, 'data-placeholder' => __('Select academic periods')));
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
	echo $this->Form->end();

$this->end();
?>