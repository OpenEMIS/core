<?php
$this->start('toolbar');
	if (array_key_exists('add', $_buttons)) {
		echo $this->Html->link('<i class="fa kd-add"></i>', $_buttons['add']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Add' , 'escape' => false]);
	}
	echo $this->Html->link('<i class="fa kd-upload"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Import' , 'escape' => false]);
	echo $this->Html->link('<i class="fa kd-download"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Export' ,'escape' => false]);
	echo $this->element('ControllerAction.search');
$this->end();

$this->start('panelBody');
	if (isset($toolbarElements)) {
		foreach ($toolbarElements as $element) {
			echo $this->element($element['name'], $element['data'], $element['options']);
		}
	}

	foreach ($indexElements as $element) {
		echo $this->element($element['name'], $element['data'], $element['options']);
	}
$this->end();
