<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<div class="input table">
		<label><?= $attr['attr']['label']; ?></label>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
					<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
				</table>
			</div>
		</div>
	</div>
<?php endif ?>
