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
$tableClass = 'table-checkable table-input';
?>

<fieldset>
    <legend><?php echo __('Indicators')?></legend>
	<?php 
		$tableHeaders = array(__('Indicator'),__('Unit'),__('Dimension'));
		$tableData = array();
		foreach ($reviewData['indicator'] as $obj) {
			$row = array();
			$row[] = $obj['indicator'];
			$row[] = $obj['unit'];
			$row[] = $obj['subgroupVal'];
			$tableData[] = $row;
		}
		
		echo $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</fieldset>

<fieldset>
    <legend><?php echo __('Areas')?></legend>
	<?php 
		$tableHeaders = $areaLevelOptions;
		$tableData = array();
		foreach ($reviewData['area'] as $obj) {
			$row = array();
			for ($i = 1; $i <= count($tableHeaders); $i++) {
				$row[] = $obj['level_' . $i . '_name'];
			}
			$tableData[] = $row;
		}
		
		echo $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</fieldset>

<fieldset>
    <legend><?php echo __('Time Periods')?></legend>
	<?php 
		$tableHeaders = array(__('Time Period'));
		$tableData = array();
		foreach ($reviewData['timeperiod'] as $obj) {
			$row = array();
			$row[] = $obj['TimePeriod'];
			$tableData[] = $row;
		}
		
		echo $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</fieldset>

<fieldset>
    <legend><?php echo __('Sources')?></legend>
	<?php 
		$tableHeaders = array(__('Source'));
		$tableData = array();
		foreach ($reviewData['source'] as $obj) {
			$row = array();
			$row[] = $obj['IC_Name'];
			$tableData[] = $row;
		}
		
		echo $this->element('/templates/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</fieldset>
<?php

$this->end();
?>
