<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	// pr($tableHeaders);
	// pr($tableCells);
?>

<?php if ($action == 'view') : ?>
	<div class="table-in-view col-md-4 table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>
