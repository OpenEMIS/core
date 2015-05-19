<?php
//ControllerActionComponent - Version 1.0.4
$dataKeys = array();
$tableHeaders = $this->ControllerAction->getTableHeaders($_fields, $model, $dataKeys);

$displayAction = array_key_exists('view', $_buttons) || array_key_exists('edit', $_buttons) || array_key_exists('remove', $_buttons);
$displayReorder = array_key_exists('reorder', $_buttons) && count($data) > 1;

if ($displayAction) {
	$tableHeaders[] = array(__('Actions') => array('class' => 'cell-action'));
}
if ($displayReorder) {
	$tableHeaders[] = array(__('Reorder') => array('class' => 'cell-reorder'));
}
if(isset($moreRows) && $moreRows) {
	$tableHeaders[] = array(__($moreRows['tableHeader']) => array('class' => 'cell-reorder'));
}

$tableData = array();

foreach ($data as $obj) {
	$row = $this->ControllerAction->getTableRow($obj, $dataKeys, $data);

	if ($displayAction) {
		$row[] = $this->element('ControllerAction.actions', array('obj' => $obj));
	}
	if ($displayReorder) {
		$row[] = array($this->element('ControllerAction.reorder', array('obj' => $obj)), array('class' => 'sorter'));
	}
	if(isset($moreRows) && $moreRows) {
		$options = $moreRows;
		$options['model'] = $model;
		$options['param'] = $obj->{$modelObj->primaryKey()};
		$row[] = $this->ControllerAction->getExecuteButton($options);
	}
	// example of thumbnails
	//array_unshift($row, $this->element('thumbnail'));
	$tableData[] = $row;
}
$tableClass = ''; //isset($data['tableClass']) ? $data['tableClass'] : '';

if(isset($moreRows) && $moreRows) {
	$formOptions = $this->ControllerAction->getFormOptions($_buttons[$action]['url']);
	echo $this->Form->create($model, $formOptions);
}

// example of thumbnails
//array_unshift($tableHeaders, 'Picture');
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable <?php echo $tableClass ?>">
		<thead><?php echo $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?php echo $this->Html->tableCells($tableData) ?></tbody>
	</table>
</div>
<?php 
if(isset($moreRows) && $moreRows) {
	echo $this->Form->end();
}
 ?>
