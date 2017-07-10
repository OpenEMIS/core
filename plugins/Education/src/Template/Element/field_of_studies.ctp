<?php
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$fieldOfStudiesOptions = isset($attr['fieldOfStudiesOptions']) ? $attr['fieldOfStudiesOptions'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php $tableClass = 'table-responsive'; ?>
	<div class="clearfix"></div>
	<?php
		$alias = $ControllerAction['table']->alias();
		echo $this->Form->input("$alias.selected_field_of_study", [
			'label' => __('Add Field of Study'),
			'type' => 'select',
			'options' => $fieldOfStudiesOptions,
 			'onchange' => "$('#reload').val('addFieldOfStudy').click();"
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
