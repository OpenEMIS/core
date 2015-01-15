<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	if(isset($this->request->data[$Custom_Field]['id'])) {	//edit
		echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'view', $this->request->data[$Custom_Field]['id']), $params), array('class' => 'divider'));
	} else { //new
		echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');

	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => $this->params->plugin, 'controller' => $this->params->controller, 'action' => 'add'));
	if(isset($this->request->data[$Custom_Field]['id'])) {	//edit
		$formOptions['url']['action'] = 'edit';
	}
	$formOptions['url'] = array_merge($formOptions['url'], $params);

	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create($Custom_Field, $formOptions);
		if(isset($this->request->data[$Custom_Field]['id'])) {	//edit
			echo $this->Form->hidden('id');
			echo $this->Form->hidden('type');
		}
		echo $this->Form->hidden(Inflector::underscore($Custom_Parent).'_id');
		$labelOptions['text'] = __('Name');
		echo $this->Form->input($Custom_Parent . '.name', array('readonly' => 'readonly', 'label' => $labelOptions));
		$labelOptions['text'] = __('Field Name');
		echo $this->Form->input('name', array('label' => $labelOptions, 'onkeyup' => '$("#custom_table_name").html(this.value);'));
		$labelOptions['text'] = __('Field Type');
		echo $this->Form->input('type', array('options' => $fieldTypeOptions, 'label' => $labelOptions, 'disabled' => $fieldTypeDisabled,'onchange' => '$("#reload").click();'));
		$labelOptions['text'] = __('Mandatory');
		echo $this->Form->input('is_mandatory', array('options' => $mandatoryOptions, 'label' => $labelOptions, 'disabled' => $mandatoryDisabled));
		$labelOptions['text'] = __('Unique');
		echo $this->Form->input('is_unique', array('options' => $uniqueOptions, 'label' => $labelOptions, 'disabled' => $uniqueDisabled));
		$labelOptions['text'] = __('Visible');
		echo $this->Form->input('visible', array('options' => $visibleOptions, 'label' => $labelOptions));
		if($this->request->data[$Custom_Field]['type'] == 3 || $this->request->data[$Custom_Field]['type'] == 4) {
			echo $this->element('/custom_fields/options');
		} else if($this->request->data[$Custom_Field]['type'] == 7) {
			echo $this->element('/custom_fields/tables');
		}
		echo $this->FormUtility->getFormButtons(array('cancelURL' => array_merge(array('action' => 'index'), $params)));
		echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->Form->end();

$this->end();
?>
