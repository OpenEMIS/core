<?php
echo $this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);

//ControllerActionComponent - Version 1.0.4
$dataKeys = [];
$tableHeaders = $this->ControllerAction->getTableHeaders($_fields, $model, $dataKeys);

$displayAction = $indexButtons->count() > 0;
$displayReorder = isset($reorder) && $reorder && $data->count() > 1;

if ($displayAction) {
	$tableHeaders[] = [__('Actions') => ['class' => 'cell-action']];
}
if ($displayReorder) {
	echo $this->Html->script('OpenEmis.jquery-ui.min', ['block' => true]);
	echo $this->Html->script('ControllerAction.reorder', ['block' => true]);
	$tableHeaders[] = [__('Reorder') => ['class' => 'cell-reorder']];
}

$tableData = [];

$eventKey = 'ControllerAction.Model.onUpdateActionButtons';
$this->ControllerAction->onEvent($table, $eventKey, 'onUpdateActionButtons');

foreach ($data as $entity) {
	$row = $this->ControllerAction->getTableRow($entity, $dataKeys);

	if ($displayAction) {
		$buttons = $indexButtons->getArrayCopy();
		$event = $this->ControllerAction->dispatchEvent($table, $eventKey, null, [$entity, $indexButtons->getArrayCopy()]);
		if (!empty($event->result)) {
			$buttons = $event->result;
		}

		$row[] = [$this->element('OpenEmis.actions', ['entity' => $entity, 'buttons' => $buttons]), ['class' => 'rowlink-skip']];
	}
	if ($displayReorder) {
		$row[] = [$this->element('OpenEmis.reorder', ['entity' => $entity]), ['class' => 'sorter rowlink-skip']];
	}
	$tableData[] = $row;
}

$tableClass = 'table table-striped table-hover table-bordered table-sortable';

$url = [
	'plugin' => $this->request->params['plugin'],
	'controller' => $this->request->params['controller'],
	'action' => $this->request->params['action']
];

if ($this->request->params['action'] == 'index') {
	$url['action'] = 'reorder';
} else {
	$url[] = 'reorder';
}

$baseUrl = $this->Url->build($url);
?>

<div class="table-responsive">
	<table class="<?= $tableClass ?>" <?= $displayReorder ? 'id="sortable" url="' . $baseUrl . '"' : '' ?>>
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
	</table>
</div>