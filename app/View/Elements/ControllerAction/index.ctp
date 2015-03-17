<?php
//ControllerActionComponent - Version 1.0.3

$dataKeys = array();
$tableHeaders = array();

$displayFields = $_fields;

$_attrDefaults = array(
	'type' => 'string',
	'model' => $model,
	'hyperlink' => false,
	'sort' => false
);

foreach ($displayFields as $_field => $attr) {
	$_fieldAttr = array(
		'type' => 'string',
		'model' => $model,
		'hyperlink' => false,
		'sort' => false
	);
	$_fieldAttr = array_merge($_attrDefaults, $attr);
	$_type = $_fieldAttr['type'];
	$visible = $this->FormUtility->isFieldVisible($_fieldAttr, 'index');

	if ($visible && $_type != 'hidden') {
		$_fieldModel = $_fieldAttr['model'];
		$label = $this->Label->getLabel2($_fieldModel, $_field, $_fieldAttr);

		if (!in_array($_type, array('hidden', 'image', 'file', 'file_upload', 'element'))) {
			$tableHeaders[] = $label;
			$dataKeys[$_field] = $_fieldAttr;
		}
	}
}

$tableData = array();
foreach ($data as $key => $obj) {
	$row = array();
	foreach ($dataKeys as $_field => $attr) {
		if (array_key_exists($_field, $obj[$_fieldModel])) {
			$value = '';
			if (array_key_exists('dataModel', $attr) && array_key_exists('dataField', $attr)) {
				$dataModel = $attr['dataModel'];
				$dataField = $attr['dataField'];
				$value = $obj[$dataModel][$dataField];
			} else if (isset($obj[$_fieldModel][$_field])) {
				$value = $obj[$_fieldModel][$_field];
			}
			
			if (array_key_exists('displayFormat', $attr)) {
				switch ($attr['displayFormat']) {
					case 'date':
						$value = $this->Utility->formatDate($value, null, false);
						break;
				}
			}

			if ($attr['hyperlink']) {
				$actionParams = $_triggerFrom == 'Controller' ? array('action' => 'view') : array('action' => $model, 'view');
				$actionParams[] = $obj[$_fieldModel]['id'];
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
