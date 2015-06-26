<?php
$this->start('toolbar');
	echo $this->Html->link('<i class="fa kd-back"></i>', $_buttons['back']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Back', 'escape' => false]);
	if (array_key_exists('edit', $_buttons)) {
		echo $this->Html->link('<i class="fa kd-edit"></i>', $_buttons['edit']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Edit', 'escape' => false]);
	}
	if (array_key_exists('delete', $_buttons)) {
		$primaryKey = $table->primaryKey();
		$buttonOptions = ['class' => 'btn btn-xs btn-default', 'escape' => false];
		if (array_key_exists('removeStraightAway', $_buttons['delete']) && $_buttons['delete']['removeStraightAway']) {
			$buttonOptions['data-toggle'] = 'modal';
			$buttonOptions['data-target'] = '#delete-modal';
			$buttonOptions['field-target'] = '#recordId';
			$buttonOptions['field-value'] = $data->$primaryKey;
		}
		echo $this->Html->link('<i class="fa kd-trash" data-toggle="tooltip" data-placement="bottom" title="Delete"></i>', '#', $buttonOptions);
	}
$this->end();

$this->start('panelBody');
	echo $this->ControllerAction->getViewElements($data);
$this->end();
