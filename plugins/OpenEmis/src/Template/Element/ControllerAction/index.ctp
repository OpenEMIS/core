<?php
echo $this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);

//ControllerActionComponent - Version 1.0.4
$dataKeys = [];
$tableHeaders = $this->ControllerAction->getTableHeaders($_fields, $model, $dataKeys);

$displayAction = $indexButtons->count() > 0;
$displayReorder = array_key_exists('reorder', $indexButtons) && $data->count() > 1;

if ($displayAction) {
	$tableHeaders[] = [__('Actions') => ['class' => 'cell-action']];
}
if ($displayReorder) {
	$tableHeaders[] = [__('Reorder') => ['class' => 'cell-reorder']];
}

$tableData = [];

$eventKey = 'ControllerAction.Model.onUpdateActionButtons';
$this->ControllerAction->onEvent($table, $eventKey, 'onUpdateActionButtons');

foreach ($data as $entity) {
	$row = $this->ControllerAction->getTableRow($entity, $dataKeys);

	if ($displayAction) {
		$this->ControllerAction->dispatchEvent($table, $eventKey, null, [$entity, $indexButtons]);

		$row[] = [$this->element('OpenEmis.actions', ['entity' => $entity, 'buttons' => $indexButtons]), ['class' => 'rowlink-skip']];
	}
	if ($displayReorder) {
		$row[] = [$this->element('OpenEmis.reorder', ['entity' => $entity]), ['class' => 'sorter rowlink-skip']];
	}
	$tableData[] = $row;
}
$tableClass = ''; //isset($data['tableClass']) ? $data['tableClass'] : '';
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable <?php echo $tableClass ?>">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
	</table>
</div>
