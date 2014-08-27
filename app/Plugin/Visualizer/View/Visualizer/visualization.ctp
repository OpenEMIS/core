<?php
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.font-awesome.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.metro-ui.icon-font', 'stylesheet', array('inline' => false));
//echo $this->Html->css('Visualizer.bootstrap.icon-large', 'stylesheet', array('inline' => false));
if ($visualType != 'map') {
	echo $this->Html->script('/HighCharts/js/highcharts', false);
} else {
	echo $this->Html->script('/HighCharts/js/highmaps', false);
	echo $this->Html->script('/HighCharts/js/modules/data', false);
	echo $this->Html->script('/HighCharts/js/modules/drilldown', false);
}
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('/HighCharts/js/modules/no-data-to-display', false);

echo $this->Html->script('Visualizer.visualizer', false);
echo $this->Html->script('Visualizer.visualizer.visualization', false);
$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Visualizer'));
$formOptions['inputDefaults']['label']['class'] = 'col-md-1 control-label';
echo $this->Form->create($this->action, $formOptions);

if ($visualType == 'table') {
	$pageTitle = '';

	$tableClass = 'table-checkable table-input';

	$colArr = array(
		array('name' => 'Time Period', 'col' => 'TimePeriod.TimePeriod'),
		array('name' => 'Area ID', 'col' => 'DIArea.Area_ID'),
		array('name' => 'Area Name', 'col' => 'DIArea.Area_Name'),
		//array('name' => 'Indicator', 'col' => 'Indicator.Indicator_Name'),
		array('name' => 'Data Value', 'col' => 'DIData.Data_Value'),
		//array('name' => 'Unit', 'col' => 'Unit.Unit_Name'),
		array('name' => 'Dimension', 'col' => 'SubgroupVal.Subgroup_Val'),
		array('name' => 'Source', 'col' => 'IndicatorClassification.IC_Name'),
	);

	$tableHeaders = $this->Visualizer->getTableHeader($colArr, $sortCol, $sortDirection);

	//$tableHeaders = array(__('Time Period'), __('Area ID'), __('Area Name'), __('Indicator'), __('Data Value'), __('Unit'), __('Dimension'), __('Source'));
	$tableData = array();
	if (!empty($data)) {
		$i = 0;
		foreach ($data as $obj) {
			$row = array();
			$row[] = $obj['TimePeriod']['TimePeriod'];
			$row[] = $obj['DIArea']['Area_ID'];
			$row[] = $obj['DIArea']['Area_Name'];
			//$row[] = $obj['Indicator']['Indicator_Name'];
			$row[] = $obj['DIData']['Data_Value'];
			//$row[] = $obj['Unit']['Unit_Name'];
			$row[] = $obj['SubgroupVal']['Subgroup_Val'];
			$row[] = $obj['IndicatorClassification']['IC_Name'];

			if (empty($pageTitle)) {
				$pageTitle = $obj['Indicator']['Indicator_Name'];// sprintf('%s - %s', $obj['Indicator']['Indicator_Name'], $obj['Unit']['Unit_Name']);
				$pageSubTitle = sprintf('Year : %s  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Unit : %s', $yearCaption, $obj['Unit']['Unit_Name']);
			}
			$tableData[] = $row;
			$i++;
		}
		
		$displayTable = $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass'));
		echo $this->Html->div('visualizer-table-caption', $pageTitle);
		echo $this->Html->div('visualizer-table-subcaption', $pageSubTitle);
		echo $this->Html->div('visualizer-list-table disable-overflow', $displayTable);
	}
	
} else {
	if ($showVisualization) {
		echo $this->Html->div('highchart-big', '', array(
			'id' => 'highchart-container', 
			'ref' => $id, 
			'type' => $visualType,
			'url' =>'Visualizer/VisualizeHighChart/'.$visualType.DS.$id,
			'redirecturl' =>'Visualizer/visualization/table'.DS.$id ));
	}
}

if ($visualType == 'table' && !empty($data)) {
	if ($this->Paginator->counter('{:pages}') > 1) :
		?>
		<div class="left w500">
			<ul id="pagination">
				<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
			</ul>
		</div>
		<?php
	endif;
	$exportBtn = $this->Html->link(__('Download CSV'), array('action' => 'genCSV', $id), array('class' => 'btn_save'));
	echo $this->Html->div('right mt12', $exportBtn);
}
echo $this->Form->end();
$this->end();
?>
