<?php
$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-chevron-left"></i>', $_buttons['back']['url'],
		[
			'class' => 'btn btn-xs btn-default', 
			'data-toggle' => 'tooltip', 
			'data-placement' => 'bottom', 
			'title' => __('Back'),
			'escape' => false
		]
	);
	if ($action == 'edit') {
		echo $this->Html->link('<i class="fa fa-list"></i>', $_buttons['index']['url'],
			[
				'class' => 'btn btn-xs btn-default',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'title' => __('Lists'),
				'escape' => false
			]
		);
	}
$this->end();

$this->start('panelBody');
	//$template = $this->ControllerAction->getFormTemplate();
	//$formOptions = $this->ControllerAction->getFormOptions();
	//$this->Form->templates($template);
	
	echo $this->Form->create($data);
	echo $this->ControllerAction->getEditElements($data);
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
