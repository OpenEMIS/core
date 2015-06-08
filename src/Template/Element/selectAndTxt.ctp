<?php
	$selectOptions = (array_key_exists('selectOptions', $attr))? $attr['selectOptions']: [];
	$txtPlaceHolder = (array_key_exists('txtPlaceHolder', $attr))? $attr['txtPlaceHolder']: [];
	$selectId = (array_key_exists('selectId', $attr))? $attr['selectId']: [];
	$txtId = (array_key_exists('txtId', $attr))? $attr['txtId']: [];
	$label = (array_key_exists('label', $attr))? $attr['label']: [];

	echo $this->Form->input($selectId, 
			array(
				'label' => array('text' => $label),//'class' => $labelOptions['class'], 
				'options' => $selectOptions
				)
			);
	echo $this->Form->input($txtId,
		array(
			'placeholder' => $txtPlaceHolder,
			'label' => array('text' => '')//'class' => $labelOptions['class'], 
		)
	);  
?>