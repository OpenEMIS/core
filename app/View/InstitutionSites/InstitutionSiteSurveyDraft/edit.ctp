<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("InstitutionSiteSurveyDraft.title"));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyDraft', 'view', $id), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'edit', $id));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyDraft', $formOptions);
		echo $this->Form->hidden('id', array('value' => $id));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('InstitutionSiteSurvey.survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $templateData['name']));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		echo $this->FormUtility->getFormButtons(array(
			'submitURL' => array('action' => 'InstitutionSiteSurveyDraft', 'view', $id),
			'cancelURL' => array('action' => 'InstitutionSiteSurveyDraft', 'view', $id)
		));
	echo $this->Form->end();

$this->end(); 
?>