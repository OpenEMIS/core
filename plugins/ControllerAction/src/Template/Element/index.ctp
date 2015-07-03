<?php
echo $this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);

//ControllerActionComponent - Version 1.0.4
$dataKeys = array();
$tableHeaders = $this->ControllerAction->getTableHeaders($_fields, $model, $dataKeys);

$displayAction = !empty($indexButtons);
$displayReorder = array_key_exists('reorder', $indexButtons) && count($data) > 1;

if ($displayAction) {
	$tableHeaders[] = [__('Actions') => ['class' => 'cell-action']];
}
if ($displayReorder) {
	$tableHeaders[] = [__('Reorder') => ['class' => 'cell-reorder']];
}
// if(isset($moreRows) && $moreRows) {
// 	$tableHeaders[] = array(__($moreRows['tableHeader']) => array('class' => 'cell-reorder'));
// }

$tableData = array();

foreach ($data as $obj) {
	$row = $this->ControllerAction->getTableRow($obj, $dataKeys, $data);

	if ($displayAction) {
		$row[] = [$this->element('ControllerAction.actions', ['obj' => $obj]), ['class' => 'rowlink-skip']];
	}
	if ($displayReorder) {
		$row[] = [$this->element('ControllerAction.reorder', ['obj' => $obj]), ['class' => 'sorter rowlink-skip']];
	}
	// if(isset($moreRows) && $moreRows) {
	// 	$options = $moreRows;
	// 	$options['model'] = $model;
	// 	$options['param'] = $obj->{$modelObj->primaryKey()};
	// 	$row[] = $this->ControllerAction->getExecuteButton($options);
	// }
	// example of thumbnails
	//array_unshift($row, $this->element('thumbnail'));
	$tableData[] = $row;
}
$tableClass = ''; //isset($data['tableClass']) ? $data['tableClass'] : '';

if(isset($moreRows) && $moreRows) {
	$formOptions = $this->ControllerAction->getFormOptions($buttons[$action]['url']);
	echo $this->Form->create($model, $formOptions);
}

// example of thumbnails
//array_unshift($tableHeaders, 'Picture');
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable <?php echo $tableClass ?>">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
	</table>
</div>
<?php 
if(isset($moreRows) && $moreRows) {
	echo $this->Form->end();
}
?>
