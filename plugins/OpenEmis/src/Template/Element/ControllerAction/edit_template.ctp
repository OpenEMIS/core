<?php
$this->start('toolbar');
	echo $this->Html->link('<i class="fa kd-back"></i>', $_buttons['back']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Back' ,'escape' => false]);
	if ($action == 'edit') {
		echo $this->Html->link('<i class="fa kd-lists"></i>', $_buttons['index']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Lists' ,'escape' => false]);
	}
$this->end();

$this->start('panelBody');
	$template = $this->ControllerAction->getFormTemplate();
	$formOptions = $this->ControllerAction->getFormOptions();
	$this->Form->templates($template);
	
	echo $this->Form->create($data, $formOptions);
	echo $this->ControllerAction->getEditElements($data);
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
