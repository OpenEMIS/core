<?php
$this->extend('OpenEmis./Layout/Panel');
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
	$template = $this->ControllerAction->getFormTemplate();
	$formOptions = $this->ControllerAction->getFormOptions();
	$this->Form->templates($template);
	
	echo $this->Form->create($data, $formOptions);
	echo $this->ControllerAction->getEditElements($data);
	echo '<div class="form-buttons"><div class="button-label"></div>';
	echo $this->Form->button(__('Generate'), ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'generate']);
	echo $this->Form->button('reload', ['id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden']);
	echo '</div>';
	echo $this->Form->end();
$this->end();
?>
