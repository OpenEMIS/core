<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-chevron-left"></i>', $_buttons['back']['url'], ['class' => 'btn btn-xs btn-default', 'data-tooltip' => 'Back', 'escape' => false]);
	if (array_key_exists('edit', $_buttons)) {
		echo $this->Html->link('<i class="fa fa-pencil"></i>', $_buttons['edit']['url'], ['class' => 'btn btn-xs btn-default', 'data-tooltip' => 'Edit', 'escape' => false]);
	}
	if (array_key_exists('remove', $_buttons)) {
		$primaryKey = $modelObj->primaryKey();
		$buttonOptions = ['class' => 'btn btn-xs btn-default', 'escape' => false, 'data-tooltip' => 'Delete'];
		if (array_key_exists('removeStraightAway', $_buttons['remove']) && $_buttons['remove']['removeStraightAway']) {
			$buttonOptions['data-toggle'] = 'modal';
			$buttonOptions['data-target'] = '#delete-modal';
			$buttonOptions['field-target'] = '#recordId';
			$buttonOptions['field-value'] = $data->$primaryKey;
		}
		echo $this->Html->link('<i class="fa fa-trash"></i>', '#', $buttonOptions);
	}
$this->end();

$this->start('panelBody');
	echo $this->ControllerAction->getViewElements();
$this->end();
