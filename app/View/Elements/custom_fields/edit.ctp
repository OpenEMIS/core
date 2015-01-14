<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => $this->params->plugin, 'controller' => $this->params->controller, 'action' => 'add'));
	$formOptions['url'] = array_merge($formOptions['url'], $this->params->named);
	
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create($Custom_Field, $formOptions);
		echo $this->Form->hidden(Inflector::underscore($Custom_Parent).'_id', array('value' => $Custom_ParentId));
		$labelOptions['text'] = __('Name');
		echo $this->Form->input($Custom_Parent . '_name', array('disabled' => 'disabled', 'label' => $labelOptions, 'value' => $parentName));
		$labelOptions['text'] = __('Field Name');
		echo $this->Form->input('name', array('label' => $labelOptions, 'onkeyup' => '$("#question_table_name").html(this.value);'));
		$labelOptions['text'] = __('Field Type');
		echo $this->Form->input('type', array('options' => $fieldTypeOptions, 'label' => $labelOptions, 'default' => $selectedFieldType, 'onchange' => '$("#reload").click();'));
		$labelOptions['text'] = __('Mandatory');
		echo $this->Form->input('is_mandatory', array('options' => $mandatoryOptions, 'label' => $labelOptions, 'default' => $selectedMandatory, 'disabled' => $mandatoryDisabled));
		$labelOptions['text'] = __('Unique');
		echo $this->Form->input('is_unique', array('options' => $uniqueOptions, 'label' => $labelOptions, 'default' => $selectedUnique, 'disabled' => $uniqueDisabled));
		$labelOptions['text'] = __('Visible');
		echo $this->Form->input('visible', array('options' => $visibleOptions, 'label' => $labelOptions, 'default' => $selectedVisible));
		if($selectedFieldType == 3 || $selectedFieldType == 4) {
			echo $this->element('/custom_fields/options');
		} else if($selectedFieldType == 7) {
			echo $this->element('Surveys.question_tables');
		}
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->Form->end();

$this->end();
?>
