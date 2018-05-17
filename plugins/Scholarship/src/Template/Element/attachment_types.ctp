<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="table-in-view">
		<table class="table">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<div class="input">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
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
