<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("InstitutionSiteSurveyNew.title"));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyNew', 'view', $academicPeriodId, $surveyStatusId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'add'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyNew', $formOptions);
		echo $this->Form->hidden('academic_period_id', array('value' => $academicPeriodId));
		echo $this->Form->hidden('survey_status_id', array('value' => $surveyStatusId));
		echo $this->Form->hidden('survey_template_id', array('value' => $templateData['id']));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $templateData['name']));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		echo $this->FormUtility->getFormButtons(array(
			'submitURL' => array('action' => 'InstitutionSiteSurveyNew', 'view', $academicPeriodId, $surveyStatusId),
			'cancelURL' => array('action' => 'InstitutionSiteSurveyNew', 'view', $academicPeriodId, $surveyStatusId)
		));
	echo $this->Form->end();

$this->end();
?>