<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'view', $data['SurveyQuestion']['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'edit'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('SurveyQuestion', $formOptions);
		echo $this->Form->hidden('id', array('value' => $data['SurveyQuestion']['id']));
		echo $this->Form->hidden('survey_template_id', array('value' => $data['SurveyQuestion']['survey_template_id']));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input('survey_template_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $templateData['SurveyTemplate']['name']));
		$labelOptions['text'] = __('Field Name');
		echo $this->Form->input('name', array('label' => $labelOptions));
		$labelOptions['text'] = __('Field Type');
		echo $this->Form->input('type', array('options' => $fieldTypeOptions, 'label' => $labelOptions, 'default' => $data['SurveyQuestion']['type'], 'onchange' => '$("#reload").click();'));
		$labelOptions['text'] = __('Mandatory');
		echo $this->Form->input('is_mandatory', array('options' => $mandatoryOptions, 'label' => $labelOptions, 'default' => $data['SurveyQuestion']['is_mandatory'], 'disabled' => $mandatoryDisabled));
		$labelOptions['text'] = __('Unique');
		echo $this->Form->input('is_unique', array('options' => $uniqueOptions, 'label' => $labelOptions, 'default' => $data['SurveyQuestion']['is_unique'], 'disabled' => $uniqueDisabled));
		$labelOptions['text'] = __('Visible');
		echo $this->Form->input('visible', array('options' => $visibleOptions, 'label' => $labelOptions, 'default' => $data['SurveyQuestion']['visible']));
		if($data['SurveyQuestion']['type'] == 3 || $data['SurveyQuestion']['type'] == 4) {
			echo $this->element('Surveys.question_choices');
		} else if($data['SurveyQuestion']['type'] == 7) {
			echo $this->element('Surveys.question_tables');
		}
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->Form->end();

$this->end();
?>