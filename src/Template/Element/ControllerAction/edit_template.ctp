<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-chevron-left"></i>', $_buttons['back']['url'], ['class' => 'btn btn-xs btn-default', 'data-tooltip' => 'Back', 'escape' => false]);
	if ($action == 'edit') {
		echo $this->Html->link('<i class="fa fa-list"></i>', $_buttons['index']['url'], ['class' => 'btn btn-xs btn-default', 'data-tooltip' => 'Lists', 'escape' => false]);
	}
$this->end();

$this->start('panelBody');
	$template = $this->ControllerAction->getFormTemplate();
	$formOptions = $this->ControllerAction->getFormOptions();
	//$this->Form->templates($template);
	
	echo $this->Form->create($data, $formOptions);
	echo $this->ControllerAction->getEditElements();
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
