<?php
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if($btn['attr'] == null){
			$btn['attr'] = array();
		}
		if (!isset($btn['type']) || $btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	if ($ControllerAction['form'] !== false) {
		$formOptions = $this->ControllerAction->getFormOptions();
		if (is_array($ControllerAction['form'])) {
			$formOptions = array_merge($formOptions, $ControllerAction['form']);
		}
		$entity = $ControllerAction['table']->newEmptyEntity();
		if (isset($data)) {
			$entity = $data;
		}
		echo $this->Form->create($entity, $formOptions);
	}
	foreach ($elements as $element) {
		$elementData = isset($element['data']) ? $element['data'] : [];
		$elementOptions = isset($element['options']) ? $element['options'] : [];
		echo $this->element($element['name'], $elementData, $elementOptions);
	}
	if ($ControllerAction['form'] !== false) {
		echo $this->ControllerAction->getFormButtons();
		echo $this->Form->end();
	}
$this->end();
