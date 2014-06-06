<?php
echo $this->Html->css('Dashboards.dashboard', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Dashboards/js/Charts/FusionCharts', false);
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

echo $this->Form->create($modelName, array('url' => $formOptions, 'novalidate' => 1, 'class' => 'form-horizontal', 'inputDefaults' => array('class' => 'form-control')));
echo $this->Form->input('geo_level_id', array(
	'options' => $geoLvlOptions,
	'default' => $geoLvlId,
	'div' => 'col-md-4 form-group',
	'url' => 'Dashboards/dashboardsAjaxGetArea',
	'onchange' => 'Dashboards.areaChange(this)',
	'between' => '<div class="col-md-5">',
	'after' => '</div>',
	'label' => array('text' => __('Geographical Level'), 'class' => 'col-md-7  control-label')
));
echo $this->Form->input('area_level_id', array(
	'options' => $areaLvlOptions,
	'default' => $areaId,
	'div' => 'col-md-5 form-group',
	'between' => '<div class="col-md-9">',
	'after' => '</div>',
	'label' => array('text' => __('Area'), 'class' => 'col-md-3 control-label')
));
echo $this->Form->input('year_id', array(
	'options' => $yearsOptions,
	'default' => $yearId,
	'div' => 'col-md-3 form-group',
	'between' => '<div class="col-md-8">',
	'after' => '</div>',
	'label' => array('text' => __('Year'), 'class' => 'col-md-4 control-label')
));

echo $this->Form->input('Update', array(
	'type' => 'submit',
	'label' => false,
	'class' => 'btn_save btn_right',
	'onclick' => 'return Config.checkValidate()',
	'before' => '<div class="col-md-1 form-group">',
	'after' => '</div>',
));

echo $this->Html->div('clear_both underline', '', array('style' => "margin-bottom:10px;"));

echo $this->Form->end();
foreach ($displayChartData as $key => $item) {
	$setupData['chartContainerId'] = 'dashboardChartContainer' . $key;
	$setupData['chartVarId'] = 'dashboardChartVar' . $key;
	$setupData['chartId'] = 'dashboardChartId-' . $key;

	$setupData = array_merge($setupData, $item);

	echo $this->element('chartTemplate', $setupData, array('plugin' => 'Dashboards'));
}

$tableHeaders = $QATableData['tableHeaders'];
$tableData = $QATableData['tableData'];

echo $this->Html->div('clear_both', '');
?>
<div class="form-group">
	<h5><?php echo $tableTitle; ?></h5>

	<?php echo $this->element('templates/table', compact('tableHeaders', 'tableData')); ?>
	<div class="center">
		<?php echo $this->Html->link(__('Download CSV'), array('action' => 'genCSV', $areaId, $yearId), array('class' => 'btn_save')); ?>
	</div>
</div>
<?php $this->end();?>
