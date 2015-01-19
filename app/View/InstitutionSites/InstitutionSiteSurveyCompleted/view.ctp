<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("InstitutionSiteSurveyCompleted.title"));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteSurveyCompleted', 'index'), array('class' => 'divider'));
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'InstitutionSiteSurveyCompleted', 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'edit', $id));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteSurveyCompleted', $formOptions);
		echo $this->Form->hidden('id', array('value' => $id));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('InstitutionSiteSurvey.survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $template['name']));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelRow', 'modelColumn', 'action'));
	echo $this->Form->end();

$this->end(); 
?>