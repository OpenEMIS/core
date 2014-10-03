<?php
echo $this->Html->css('Dashboards.dashboard', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/HighCharts/js/highcharts', false);
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('Dashboards.dashboards', false);

$this->extend('/Elements/layout/container_blank');
$this->assign('contentHeader', $header);
$this->assign('contentClass', '');
$this->assign('contentId', 'dashboard');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'dashboardReport'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');

$formOptions = array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Dashboards');
$formOptions = array_merge($formOptions, $this->params['pass']);

if (!empty($QATableData['tableData'])) {
	
	echo $this->Form->create($modelName, array('url' => $formOptions, 'novalidate' => 1, 'class' => 'form-horizontal', 'inputDefaults' => array('class' => 'form-control','div' => 'row left_control','after' => '</div>')));
	echo $this->Form->input('geo_level_id', array(
		'options' => $geoLvlOptions,
		'default' => $geoLvlId,
		
		'url' => 'Dashboards/dashboardsAjaxGetArea',
		'onchange' => 'Dashboards.areaChange(this)',
		'between' => '<div class="col-md-5">',
		'label' => array('text' => __('Geographical Level'), 'class' => 'col-md-7 control-label')
	));
	echo $this->Form->input('area_level_id', array(
		'options' => $areaLvlOptions,
		'default' => $areaId,
		'between' => '<div class="col-md-8">',
		'label' => array('text' => __('Area'), 'class' => 'col-md-4 control-label')
	));
	echo $this->Form->input('year_id', array(
		'options' => $yearsOptions,
		'default' => $yearId,
		'between' => '<div class="col-md-5">',
		'label' => array('text' => __('Year'), 'class' => 'col-md-5 control-label')
	));
	
	echo $this->Form->input(__('Update'), array(
		'type' => 'submit',
		'label' => false,
		'div' => 'col-md-1 form-group',
		'after' => false,
		'class' => 'btn_save btn_right',
		'onclick' => 'return Config.checkValidate()',
	));
	
	echo $this->Html->div('clear_both underline', '', array('style' => "margin-bottom:10px;"));
	
	echo $this->Form->end();
	/*foreach ($displayChartData as $key => $item) {
		$setupData['chartContainerId'] = 'dashboardChartContainer' . $key;
		$setupData['chartVarId'] = 'dashboardChartVar' . $key;
		$setupData['chartId'] = 'dashboardChartId-' . $key;
	
		$setupData = array_merge($setupData, $item);
	
		echo $this->element('chartTemplate', $setupData, array('plugin' => 'Dashboards'));
	*/
	$allGraph = '';
	foreach ($displayChartData as $key => $item) {
		$allGraph .= $this->Html->div('hc_graph_wrapper row', '', array('id' => 'highchart-container-'.$key, 'url' => $this->Html->url($item['chartURLdata'])));
	}
	echo $this->Html->div(NULL, $allGraph, array('id' => 'hc_graph_container'));
	
	
	$tableHeaders = $QATableData['tableHeaders'];
	$tableData = $QATableData['tableData'];
	
	//echo $this->Html->div('clear_both', '');
	
	?>
	
	<div class="form-group">
		<h5><?php echo $tableTitle; ?></h5>
	
		<?php echo $this->element('templates/table', compact('tableHeaders', 'tableData')); ?>
		<div class="center">
			<?php echo $this->Html->link(__('Download CSV'), array('action' => 'genCSV', $areaId, $yearId), array('class' => 'btn_save')); ?>
		</div>
	</div>

<?php 
} // end if
$this->end();
?>
