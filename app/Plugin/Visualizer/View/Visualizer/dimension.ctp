<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->script('Visualizer.visualizer', false);

$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.reset'), array('action' => 'reset'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Visualizer'));
$formOptions['inputDefaults']['label']['class'] = 'col-md-1 control-label left';

$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($this->action, $formOptions);
echo $this->Form->input('search', array('id' => 'search'));
?>
<div class='visualizer-list-table'>
	<?php
	$tableClass = 'table-checkable table-input';

	$headerfirstCol = array($this->Form->input(null, array('class' => 'icheck-input', 'label' => false, 'div' => false, 'type' => 'checkbox', 'checked' => false)) => array(
		'class' => 'checkbox-column', 
		'onchange' => 'Visualizer.checkboxChangeAll(this)',
		'url' => 'Visualizer/ajaxUpdateUserCBSelection'
		));
	
	$colArr = array(
		array('name' => 'Indicator'),
		array('name' => 'Unit'),
		array('name' => 'Dimension', 'col' => 'SubgroupVal.Subgroup_Val'),
	);

	$tableHeaders = $this->Visualizer->getTableHeader($colArr, $sortCol, $sortDirection);
	array_unshift($tableHeaders, $headerfirstCol); 
	
	//$tableHeaders = array($headerfirstCol,__('Indicator'),__('Unit'),__('Dimension'));

	$tableData = array();
	if (!empty($tableRowData)) {
		$i = 0;
		foreach ($tableRowData as $obj) {
			if (empty($selectedDimensionIds)) {
				$checked = false;
			} else {
				$checked = (in_array($obj['IUSId'], $selectedDimensionIds)) ? 'checked' : false;
			}
			
			$bodyFirstColOptions = array(
				'type' => 'checkbox', 
				'class' => 'icheck-input', 
				'label' => false, 
				'div' => false, 
				'checked' => $checked, 
				'value' => $obj['IUSId'],
				'sectionType' => 'IUS', 
				'onchange' => 'Visualizer.checkboxChange(this)',
				'url' => 'Visualizer/ajaxUpdateUserCBSelection');
			$additionalClass = 'checkbox-column center';
			$input = $this->Form->input('IndicatorUnitSubgroup.IUS.' . $i, $bodyFirstColOptions);

			$row = array();
			$row[] = array($input, array('class' => $additionalClass));
			$row[] = $obj['indicator'];
			$row[] = $obj['unit'];
			$row[] = array($obj['subgroupVal'], array('class' => 'data-list'));
			$tableData[] = $row;
			$i++;
		}
	}
	echo $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</div>

<?php
echo $this->Form->end();
$this->end();
?>
