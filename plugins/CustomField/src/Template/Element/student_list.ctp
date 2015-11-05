<?php
	$alias = isset($attr['alias']) ? $attr['alias'] : [];
	$sectionOptions = isset($attr['sectionOptions']) ? $attr['sectionOptions'] : [];
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php else : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= $attr['attr']['label']; ?></h3>
	<div class="clearfix">
		<?php
			echo $this->Form->input($alias.".institution_section", [
				'label' => $this->Label->get('InstitutionSurveys.section'),
				'type' => 'select',
				'options' => $sectionOptions,
				'onchange' => "$('#reload').val('changeSection').click();"
			]);
		?>
	</div>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>
