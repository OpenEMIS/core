<?php
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$this->Form->unlockField('trainer_id');
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php $tableClass = 'table-responsive'; ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __('Trainers') ?></h3>
	<?php
		$url = $this->Url->build([
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action'],
		    'ajaxTrainerAutocomplete'
		]);
		$alias = $ControllerAction['table']->alias();

		echo $this->Form->input("$alias.trainer_search", [
			'label' => __('Add Internal Trainer'),
			'type' => 'text',
			'class' => 'autocomplete',
			'value' => '',
			'autocomplete-url' => $url,
			'autocomplete-no-results' => __('No Trainer found.'),
			'autocomplete-class' => 'error-message',
			'autocomplete-target' => 'trainer_id',
			'autocomplete-submit' => "$('#reload').val('addTrainer').click();",
			'autocomplete-before-search' => 'Autocomplete.extra["type"] = "Staff"'
		]);
		echo $this->Form->hidden("$alias.trainer_id", ['autocomplete-value' => 'trainer_id']);

		echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Trainer').'</span>', [
			'label' => __('Add External Trainer'),
			'type' => 'button',
			'class' => 'btn btn-default',
			'aria-expanded' => 'true',
			'onclick' => "$('#reload').val('addTrainer').click();"
		]);
	?>
	<div class="clearfix"></div>
	<hr>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="trainer_id">
	<table class="table">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>
