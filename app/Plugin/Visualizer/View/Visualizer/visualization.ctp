<?php
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->script('/FusionCharts/js/Charts/FusionCharts', false);
echo $this->Html->script('Visualizer.visualizer', false);

$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Visualizer'));
$formOptions['inputDefaults']['label']['class'] = 'col-md-1 control-label';
echo $this->Form->create($this->action, $formOptions);

if ($visualType == 'table') {
	$tableClass = 'table-checkable table-input';
	$tableHeaders = array(__('Time Period'), __('Area ID'), __('Area Name'), __('Indicator'), __('Data Value'), __('Unit'), __('Dimension'), 'Source');
	$tableData = array();
	if (!empty($data)) {
		$i = 0;
		foreach ($data as $obj) {
			$row = array();
			$row[] = $obj['TimePeriod']['TimePeriod'];
			$row[] = $obj['DIArea']['Area_ID'];
			$row[] = $obj['DIArea']['Area_Name'];
			$row[] = $obj['Indicator']['Indicator_Name'];
			$row[] = $obj['DIData']['Data_Value'];
			$row[] = $obj['Unit']['Unit_Name'];
			$row[] = $obj['SubgroupVal']['Subgroup_Val'];
			$row[] = $obj['IndicatorClassification']['IC_Name'];

			$tableData[] = $row;
			$i++;
		}
	}
	$displayTable = $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	echo $this->Html->div('visualizer-list-table disable-overflow', $displayTable);
} else {
	if($showVisualization){
		$key = '0';
		$setupData['chartContainerId'] = 'visualizerContainer' . $key;
		$setupData['chartVarId'] = 'visualizerVar' . $key;
		$setupData['chartId'] = 'visualizerId-' . $key;

		$setupData = array_merge($setupData, $displayChartData);

		echo $this->element('chartTemplate', $setupData, array('plugin' => 'FusionCharts'));
	}
}

if ($visualType == 'table') {
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
	$exportBtn =  $this->Html->link(__('Download CSV'), array('action' => 'genCSV', $id), array('class' => 'btn_save'));
	echo $this->Html->div('right mt12', $exportBtn);
}
echo $this->Form->end();
$this->end();
?>
