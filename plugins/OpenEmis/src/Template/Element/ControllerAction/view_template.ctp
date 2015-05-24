<?php
$this->start('toolbar');
	echo $this->Html->link('<i class="fa kd-back"></i>', $_buttons['back']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Back', 'escape' => false]);
	if (array_key_exists('edit', $_buttons)) {
		echo $this->Html->link('<i class="fa kd-edit"></i>', $_buttons['edit']['url'], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Edit', 'escape' => false]);
	}
	if (array_key_exists('remove', $_buttons)) {
		$primaryKey = $table->primaryKey();
		echo '<div class="delete-wrapper" data-toggle="tooltip" data-placement="bottom" title="Delete">';
		$buttonOptions = ['class' => 'btn btn-xs btn-default', 'escape' => false];
		if (array_key_exists('removeStraightAway', $_buttons['remove']) && $_buttons['remove']['removeStraightAway']) {
			$buttonOptions['data-toggle'] = 'modal';
			$buttonOptions['data-target'] = '#delete-modal';
			$buttonOptions['field-target'] = '#recordId';
			$buttonOptions['field-value'] = $data->$primaryKey;
		}
		echo $this->Html->link('<i class="fa kd-trash"></i>', '#', $buttonOptions);
		echo '</div>';
	}
$this->end();

$this->start('panelBody');
	echo $this->ControllerAction->getViewElements($data);
$this->end();
