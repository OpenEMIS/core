<?php
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if ($btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	if (isset($toolbarElements)) {
		foreach ($toolbarElements as $element) {
			echo $this->element($element['name'], $element['data'], $element['options']);
		}
	}
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);
	echo $this->ControllerAction->getViewElements($data);
$this->end();
