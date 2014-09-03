<?php
$tableData = array();
if (!empty($tableRowData)) {
	$i = 0;
	foreach ($tableRowData as $obj) {
		//	pr($obj);
		if (empty($selectedAreaIds)) {
			$checked = false;
		} else {
			$checked = (in_array($obj['Area_NId'], $selectedAreaIds)) ? 'checked' : false;
		}

		$bodyFirstColOptions = array(
			'type' => 'checkbox',
			'class' => 'icheck-input',
			'label' => false,
			'div' => false,
			'checked' => $checked,
			'value' => $obj['Area_NId'],
			'sectionType' => 'area', 
			'onchange' => 'Visualizer.checkboxChange(this)',
			'url' => 'Visualizer/ajaxUpdateUserCBSelection'
		);
		$additionalClass = 'checkbox-column';
		$input = $this->Form->input($this->action . '.Area_NId.' . $i, $bodyFirstColOptions);

		$row = array();
		$row[] = array($input, array('class' => $additionalClass));
		$row[] = array($obj['Area_ID'], array('class' => 'data-list'));
		for ($i = 1; $i <= count($areaLevelOptions); $i++) {
			$row[] = $obj['level_' . $i . '_name'];
			//	pr($obj['level_' . $i . '_name']);
		}
		$tableData[] = $row;
		$i++;
	}
}

//setup pagination
if ($this->Paginator->counter('{:pages}') > 1) {
	$this->Paginator->options(array('url' => array('action' => 'area')));
	$pgData = $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions());
	$pgData .= $this->Paginator->numbers($this->Utility->getPageNumberOptions());
	$pgData .= $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions());

	$ajaxTableData['pages'] = $pgData;
}
$ajaxTableData['rows'] = $this->Html->tableCells($tableData);


echo json_encode($ajaxTableData);
?>