<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	echo $this->element('alert');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'add'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyQuestion', $formOptions);
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $templateData['SurveyTemplate']['name']));
		echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		echo $this->Form->end();
$this->end();
?>