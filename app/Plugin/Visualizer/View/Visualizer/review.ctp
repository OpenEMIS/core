<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.font-awesome.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('Visualizer.visualizer', false);

$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.reset'), array('action' => 'reset'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$tableClass = 'table-checkable table-input';
$tableWrapperClass = 'collapse';
?>

<fieldset>
    <legend class="reviewTitle" data-toggle="collapse" data-target="#collapseIndicator" onclick='Visualizer.legendShowHide(this)'><?php echo __('Indicators')?> <i class='fa fa-plus'></i></legend>
	<?php 
		$tableWrapperId = 'collapseIndicator';
		$tableHeaders = array(__('Indicator'),__('Unit'),__('Dimension'));
		$tableData = array();
		foreach ($reviewData['indicator'] as $obj) {
			$row = array();
			$row[] = $obj['indicator'];
			$row[] = $obj['unit'];
			$row[] = $obj['subgroupVal'];
			$tableData[] = $row;
		}
		
		echo $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass', 'tableWrapperId', 'tableWrapperClass'));
	?>
</fieldset>

<fieldset>
    <legend class="reviewTitle" data-toggle="collapse" data-target="#collapseArea" onclick='Visualizer.legendShowHide(this)'><?php echo __('Areas')?> <i class='fa fa-plus'></i></legend>
	<?php 
		$tableWrapperId = 'collapseArea';
		$tableHeaders = $areaLevelOptions;
		array_unshift($tableHeaders, __('Area ID'));
		$tableData = array();
		foreach ($reviewData['area'] as $obj) {
			$row = array();
			$row[] = array($obj['Area_ID'], array('class' => 'data-list'));
			for ($i = 2; $i <= count($tableHeaders); $i++) {
				$row[] = $obj['level_' . ($i-1) . '_name'];
			}
			$tableData[] = $row;
		}
		
		echo $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass', 'tableWrapperId', 'tableWrapperClass'));
	?>
</fieldset>

<fieldset>
    <legend class="reviewTitle" data-toggle="collapse" data-target="#collapseTime" onclick='Visualizer.legendShowHide(this)'><?php echo __('Time Periods')?> <i class='fa fa-plus'></i></legend>
	<?php 
		$tableWrapperId = 'collapseTime';
		$tableHeaders = array(__('Time Period'));
		$tableData = array();
		foreach ($reviewData['timeperiod'] as $obj) {
			$row = array();
			$row[] = $obj['TimePeriod'];
			$tableData[] = $row;
		}
		
		echo $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass', 'tableWrapperId', 'tableWrapperClass'));
	?>
</fieldset>

<fieldset>
    <legend class="reviewTitle" data-toggle="collapse" data-target="#collapseSource" onclick='Visualizer.legendShowHide(this)'><?php echo __('Sources')?> <i class='fa fa-plus'></i></legend>
	<?php 
		$tableWrapperId = 'collapseSource';
		$tableHeaders = array(__('Source'));
		$tableData = array();
		foreach ($reviewData['source'] as $obj) {
			$row = array();
			$row[] = $obj['IC_Name'];
			$tableData[] = $row;
		}
		
		echo $this->element('/layout/table', compact('tableHeaders', 'tableData', 'tableClass', 'tableWrapperId', 'tableWrapperClass'));
	?>
</fieldset>
<?php

$this->end();
?>
