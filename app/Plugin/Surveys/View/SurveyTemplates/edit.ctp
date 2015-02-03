<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	if(isset($this->request->data['SurveyTemplate']['id'])) {	//edit
		echo $this->Html->link(__('Back'), array('action' => 'view', $this->request->data['SurveyTemplate']['id']), array('class' => 'divider'));
	} else {
		echo $this->Html->link(__('Back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'add'));
	if(isset($this->request->data['SurveyTemplate']['id'])) {	//edit
		$formOptions['url']['action'] = 'edit';
	}
	$formOptions['url'] = array_merge($formOptions['url'], $params);

	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyTemplate', $formOptions);
		if(isset($this->request->data['SurveyTemplate']['id'])) {	//edit
			echo $this->Form->hidden('id');
		}
		echo $this->Form->input('name');
		$labelOptions['text'] = __('Module');
		echo $this->Form->hidden('survey_module_id');
		echo $this->Form->input('survey_module_id', array('options' => $moduleOptions, 'disabled' => 'disabled', 'label' => $labelOptions));
		echo $this->Form->input('description', array('type' => 'textarea'));
		if(isset($this->request->data['SurveyTemplate']['id'])) {	//edit
			echo $this->FormUtility->getFormButtons(array('cancelURL' => array_merge(array('action' => 'view', $this->request->data['SurveyTemplate']['id']), $params)));
		} else {
			echo $this->FormUtility->getFormButtons(array('cancelURL' => array_merge(array('action' => 'index'), $params)));
		}
	echo $this->Form->end();

$this->end();
?>