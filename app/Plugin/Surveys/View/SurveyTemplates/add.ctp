<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'add'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyTemplate', $formOptions);
		echo $this->Form->input('name');
		$labelOptions['text'] = __('Module');
		echo $this->Form->input('survey_module_id', array('options' => $moduleOptions, 'label' => $labelOptions));
		echo $this->Form->input('description', array('type' => 'textarea'));
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
	echo $this->Form->end();

$this->end(); 
?>