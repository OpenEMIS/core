<?php
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if ($btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
	// echo $this->Html->link('<i class="fa kd-upload"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Import' , 'escape' => false]);
	// echo $this->Html->link('<i class="fa kd-download"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Export' ,'escape' => false]);
$this->end();

$this->start('panelBody');
	if (isset($toolbarElements)) {
		foreach ($toolbarElements as $element) {
			echo $this->element($element['name'], $element['data'], $element['options']);
		}
	}

	if ($ControllerAction['form']) {
		$formOptions = $this->ControllerAction->getFormOptions();
		if (isset($ControllerAction['url'])) {
			$formOptions['url'] = $ControllerAction['url'];
		}
		echo $this->Form->create($ControllerAction['table']->newEntity(), $formOptions);
	}

 	usort($indexElements, function($a, $b) { 
 		if (!isset($a['order']) && !isset($b['order'])) {
 			return 1;
 		} else if (!isset($a['order']) && isset($b['order'])) {
 			return 1;
 		} else if (isset($a['order']) && !isset($b['order'])) {
 			return -1;
 		} else {
 			return $a["order"] - $b["order"]; 
 		}
 	});
	foreach ($indexElements as $element) {
		echo $this->element($element['name'], $element['data'], $element['options']);
	}

	if ($ControllerAction['form']) {
		echo $this->ControllerAction->getFormButtons();
		echo $this->Form->end();
	}
$this->end();
