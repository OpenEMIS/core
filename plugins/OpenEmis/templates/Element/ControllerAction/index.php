<?php
echo $this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);

use Cake\Event\Event;

//ControllerActionComponent - Version 1.0.5
$dataKeys = [];
$table = $ControllerAction['table'];
$tableHeaders = $this->ControllerAction->getTableHeaders($ControllerAction['fields'], $table->getAlias(), $dataKeys);

$displayAction = is_array($indexButtons) ? count($indexButtons) : $indexButtons->count() > 0;
//$displayReorder = isset($reorder) && $reorder && $data->count() > 1;//commnet cakephp4
$displayReorder = isset($reorder) && $reorder;

if ($displayAction) {
	$tableHeaders[] = [__('Actions') => ['class' => 'cell-action']];
}
if ($displayReorder) {
	echo $this->Html->script('ControllerAction.reorder', ['block' => true]);
	$tableHeaders[] = [__('Reorder') => ['class' => 'cell-reorder']];
}

$tableData = [];

$eventKey = 'Model.custom.onUpdateActionButtons';
$this->ControllerAction->onEvent($table, $eventKey, 'onUpdateActionButtons');

//trigger event to get which field need to be highlighted
$searchableFields = new ArrayObject();
$event = new Event('ControllerAction.Model.getSearchableFields', $this->ControllerAction, [$searchableFields]);
$event = $table->getEventManager()->dispatch($event);

foreach ($data as $entity) {
	$row = $this->ControllerAction->getTableRow($entity, $dataKeys, $searchableFields->getArrayCopy());

	if ($displayAction) {
		$buttons = $indexButtons->getArrayCopy();
		$event = $this->ControllerAction->dispatchEvent($table, $eventKey, null, [$entity, $indexButtons->getArrayCopy()]);
		$buttons = $event->getResult();

		if (empty($buttons)) {
			$row[] = '';
		} else {
			$row[] = [$this->element('OpenEmis.actions', ['entity' => $entity, 'buttons' => $buttons]), ['class' => 'rowlink-skip']];
		}
	}
	if ($displayReorder) {
		$row[] = [$this->element('OpenEmis.reorder', ['entity' => $entity]), ['class' => 'sorter rowlink-skip']];
	}
	$tableData[] = $row;
}

$tableClass = 'table table-curved table-sortable table-checkable';
if (isset($tabElements)) {
	if (isset($toolbarElements)) {
	} else {
		$tableClass = 'table table-sortable table-checkable';
	}
}
$url = [
	'plugin' => $this->request->getParam('plugin'),
	'controller' =>$this->request->getParam('controller'),
	'action' => $this->request->getParam('action')
];

if ( $this->request->getParam('action') == 'index') {
	$url['action'] = 'reorder';
} else {
	$url[] = 'reorder';
}

$this->ControllerAction->HtmlField->includes('index', $table);

$baseUrl = $this->Url->build($url);
?>

<div class="table-wrapper" ng-class="disableElement">
	<div class="table-responsive">
		<table class="<?= $tableClass ?>" <?= $displayReorder ? 'id="sortable" url="' . $baseUrl . '"' : '' ?>>
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
		</table>
	</div>
</div>
<?php
echo $this->element('OpenEmis.pagination_new');
?>
