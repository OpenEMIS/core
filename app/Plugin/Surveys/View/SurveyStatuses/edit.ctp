<?php
echo $this->Html->css('../js/plugins/chosen/chosen.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/chosen/chosen.jquery.min', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	if(isset($this->request->data['SurveyStatus']['id'])) {	//edit
		echo $this->Html->link(__('Back'), array_merge(array('action' => 'view', $this->request->data['SurveyStatus']['id']), $params), array('class' => 'divider'));
	} else {
		echo $this->Html->link(__('Back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'add'));
	if(isset($this->request->data['SurveyStatus']['id'])) {	//edit
		$formOptions['url']['action'] = 'edit';
	}
	$formOptions['url'] = array_merge($formOptions['url'], $params);

	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyStatus', $formOptions);
		$obj = $this->request->data['SurveyStatus'];
		if(isset($this->request->data['SurveyStatus']['id'])) {	//edit
			echo $this->Form->hidden('id');
		}

		$labelOptions['text'] = __('Template');
		echo $this->Form->input('survey_template_id', array('options' => $templateOptions, 'label' => $labelOptions));
		echo $this->FormUtility->datepicker('date_enabled', array('id' => 'SurveyStatusDateEnabled', 'data-date' => $obj['date_enabled']));
		echo $this->FormUtility->datepicker('date_disabled', array('id' => 'SurveyStatusDateDisabled', 'data-date' => $obj['date_disabled']));
		$labelOptions['text'] = __('Academic Period Level');
		echo $this->Form->input('academic_period_level_id', array('options' => $academicPeriodLevelOptions, 'label' => $labelOptions, 'onchange' => '$("#reload").click();'));
		$labelOptions['text'] = __('Academic Periods');
		echo $this->Form->input('AcademicPeriod.AcademicPeriod', array('options' => $academicPeriodOptions, 'class' => 'chosen-select', 'label' => $labelOptions, 'multiple' => true, 'data-placeholder' => __('Select academic periods')));
		if(isset($this->request->data['SurveyStatus']['id'])) {	//edit
			echo $this->FormUtility->getFormButtons(array('cancelURL' => array_merge(array('action' => 'view', $this->request->data['SurveyStatus']['id']), $params)));
		} else {
			echo $this->FormUtility->getFormButtons(array('cancelURL' => array_merge(array('action' => 'index'), $params)));
		}
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->Form->end();

$this->end();
?>
