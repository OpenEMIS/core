<?php 
	echo $this->Form->input($selectId, 
			array(
				'label' => array('class' => 'col-md-3 control-label', 'text' => $label),
				'options' => $selectOptions
				)
			);
	echo $this->Form->input($txtId,
		array(
			'placeholder' => $txtPlaceHolder,
			'label' => array('class' => 'col-md-3 control-label', 'text' => '')
		)
	);  
?>