<?php
$dataKeys = array();
$tableHeaders = array();

$displayFields = $_fields;

foreach ($displayFields as $_field => $attr) {
	$_attr = array(
		'type' => 'string',
		'model' => $model,
		'hyperlink' => false,
		'sort' => false
	);
	$_attr = array_merge($_attr, $attr);
	$_type = $_attr['type'];
	$visible = $this->FormUtility->isFieldVisible($_attr, 'index');

	if ($visible && $_type != 'hidden') {
		$_model = $_attr['model'];
		$label = $this->Label->getLabel2($_model, $_field, $_attr);

		if (!in_array($_type, array('hidden', 'image', 'file', 'file_upload', 'element'))) {
			$tableHeaders[] = $label;
			$dataKeys[$_field] = $_attr;
		}
	}
}

$tableData = array();
foreach ($data as $key => $obj) {
	$row = array();
	foreach ($dataKeys as $_field => $attr) {
		if (array_key_exists($_field, $obj[$_model])) {
			$value = '';
			if (array_key_exists('dataModel', $attr) && array_key_exists('dataField', $attr)) {
				$dataModel = $attr['dataModel'];
				$dataField = $attr['dataField'];
				$value = $obj[$dataModel][$dataField];
			} else if (isset($obj[$_model][$_field])) {
				$value = $obj[$_model][$_field];
			}

			if ($attr['hyperlink']) {
				$actionParams = $_triggerFrom == 'Controller' ? array('action' => 'view') : array('action' => $model, 'view');
				$actionParams[] = $obj[$_model]['id'];
				$value = $this->Html->link($value, $actionParams);
			}
			$row[] = $value;
		}
	}
	$tableData[] = $row;
}
?>

<table class="table table-striped table-hover table-bordered <?php echo isset($tableClass) ? $tableClass : ''; ?>">
	<thead>
		<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
	</thead>
	<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>
</table>
