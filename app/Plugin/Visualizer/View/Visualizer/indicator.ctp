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
	$headerfirstCol = array('' => array('class' => 'checkbox-column'));
	
	$colArr = array(
		array('name' => 'Indicator', 'col' => 'Indicator_Name'),
		array('name' => 'Description', 'col' => 'Indicator_Info'),
	);

	$tableHeaders = $this->Visualizer->getTableHeader($colArr, $sortCol, $sortDirection);
	array_unshift($tableHeaders, $headerfirstCol); 

	$tableData = array();
	if (!empty($tableRowData)) {
		$i = 0;
		foreach ($tableRowData as $obj) {
			//	pr((($obj['checked'])? 'checked': ''));
			$bodyFirstColOptions = array('type' => 'radio', 'options' => array($obj['id'] => ''), 'value' => $selectedIndicatorId, 'label' => false, 'div' => false, 'class' => false);
			if ($obj['checked']) {
				$bodyFirstColOptions['checked'] = 'checked';
			} else {
				$bodyFirstColOptions['checked'] = false;
			}
			$additionalClass = 'center';
			$input = $this->Form->input($this->action . '.id', $bodyFirstColOptions);

			$row = array();
			$row[] = array($input, array('class' => $additionalClass));
			$row[] = array($obj['name'], array('class' => 'data-list'));
			$row[] = $obj['desc'];
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
