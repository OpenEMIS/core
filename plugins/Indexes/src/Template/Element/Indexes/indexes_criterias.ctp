<?php
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$criteriaOptions = isset($attr['criteriaOptions']) ? $attr['criteriaOptions'] : [];
	// $this->Form->unlockField('criteria_type');
?>

<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<?php $tableClass = 'table-responsive'; ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __('Criterias') ?></h3>
	<?php
		$alias = $ControllerAction['table']->alias();

		echo $this->Form->input("$alias.criteria_type", [
			'type' => 'select',
			'label' => __('Add Criteria'),
			'options' => $criteriaOptions,
			'onchange' => "$('#reload').val('addCriteria').click();"
		]);
	?>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="trainer_id">
	<table class="table">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>
