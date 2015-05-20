<?php
$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-plus"></i>', $_buttons['add']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Add' , 'escape' => false]); 
	echo $this->Html->link('<i class="fa fa-upload"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Import' , 'escape' => false]);
	echo $this->Html->link('<i class="fa fa-download"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Export' ,'escape' => false]);
	echo $this->element('ControllerAction.search');
$this->end();

$this->start('panelBody');
	foreach ($indexElements as $element) {
		echo $this->element($element['name'], $element['data'], $element['options']);
	}
$this->end();
