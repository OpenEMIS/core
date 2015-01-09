<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'view', $data['SurveyTemplate']['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'edit'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyTemplate', $formOptions);
		$obj = $data['SurveyTemplate'];
		echo $this->Form->input('id', array('type' => 'hidden', 'value' => $obj['id']));
		echo $this->Form->input('name', array('value' => $obj['name']));
		$labelOptions['text'] = __('Module');
		echo $this->Form->input('survey_module_id', array('options' => $moduleOptions, 'label' => $labelOptions, 'default' => $obj['survey_module_id']));
		echo $this->Form->input('description', array('type' => 'textarea', 'value' => $obj['description']));
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
	echo $this->Form->end();

$this->end();
?>