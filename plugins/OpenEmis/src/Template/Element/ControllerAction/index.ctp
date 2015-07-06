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

<script>
$( document ).ready( function(){

	var currentOrder = getOrder("td","data-row-id");
	var originalOrder = currentOrder;
	var urlToPost = $( "table" ).attr( "url" );
	
	//Helper function to keep table row from collapsing when getting dragged around
	var preventCollapse = function(e, ui) {
		ui.children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

	// Sortable only when mouse over the arrows
	$( "td.sorter.rowlink-skip" ).mousedown(function(){
		// Sortable on tbody
		$( "#sortable tbody" ).sortable({
			forcePlaceholderSize: true,	
			helper: preventCollapse,
			cursor: "none",
			axis: "y",
			stop: function(event, ui){
				currentOrder = getOrder("td","data-row-id");
				if(! isSame(currentOrder,originalOrder)){
					$.ajax({
						cache: false,
						url: urlToPost,
						type: "POST",
						data: {
							ids: JSON.stringify(currentOrder)
						},
						traditional: true,
						success: function(data){
							originalOrder = currentOrder;
						}
					});
				}
			}
		}).disableSelection();
		
		// Re-enable the sortable if the mouse has already been release
		$( "#sortable tbody" ).sortable('enable');
	})

	// Disable sortable on any other portion of the body if the mouse is move away
	$( document ).mouseup( function(){
		$( "#sortable tbody" ).sortable('disable');
	});
});

// Check if array order has change
function isSame(array1, array2){
	if(array1.length==array2.length){
		for(i = 0; i<array1.length; i++){
			if(!(array1[i] == array2[i])){
				return false;
			}
		}
	return true;
	}
}

// Get order of each row
function getOrder(htmlTag, attributeName){
	return $( htmlTag ).map(function(){
		return $(this).attr( attributeName );
	}).get();
}
</script>

<div class="table-responsive">
	<table class="<?= $tableClass ?>" <?= $displayReorder ? 'id="sortable" url="' . $baseUrl . '"' : '' ?>>
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
	</table>
</div>
