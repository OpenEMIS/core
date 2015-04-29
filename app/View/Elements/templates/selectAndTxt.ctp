<?php
$labelOptions = $this->FormUtility->getLabelOptions();
	echo $this->Form->input($selectId, 
			array(
				'label' => array('class' => $labelOptions['class'], 'text' => $label),
				'options' => $selectOptions
				)
			);
	echo $this->Form->input($txtId,
		array(
			'placeholder' => $txtPlaceHolder,
			'label' => array('class' => $labelOptions['class'], 'text' => '')
		)
	);  
?>