<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('toolbar');
	echo $this->Html->link('<i class="fa kd-add"></i>', $_buttons['add']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Add' , 'escape' => false]); 
	echo $this->Html->link('<i class="fa kd-upload"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Import' , 'escape' => false]);
	echo $this->Html->link('<i class="fa kd-download"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Export' ,'escape' => false]);
	echo $this->element('ControllerAction.search');
$this->end();

$this->start('panelBody');
?>

<h1>Institution :: Tests :: index</h1>

<?php
$this->end();
?>