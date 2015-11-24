<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table table-checkable table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php else : ?>
	<div class="input table">
		<label><?= $attr['attr']['label']; ?></label>
		<div class="table-in-view">
			<table class="table table-checkable table-input">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php endif ?>
