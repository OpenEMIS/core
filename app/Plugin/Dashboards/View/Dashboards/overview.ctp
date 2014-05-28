<?php
echo $this->Html->css('Dashboards.dashboard', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);
echo $this->Html->script('/Dashboards/js/Charts/FusionCharts', false);
echo $this->Html->script('Dashboards.dashboards', false);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="dashboard" class="dashboard_wrapper edit add">
	<h1>
		<span><?php echo $header; ?></span>
		<?php
        echo $this->Html->link(__('Back'), array('action' => 'dashboardReport'), array('class' => 'divider'));
		?>
	</h1>

	<?php echo $this->element('alert'); ?>
	<?php
	$formOptions = array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Dashboards');
	$formOptions = array_merge($formOptions, $this->params['pass']);

	// $pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';
	echo $this->Form->create($modelName, array(
		'url' => $formOptions,
		// 'link' => 'Quality/' . $this->action . $pathId,
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	<div class="row left">
        <div class="label"><?php echo __('Geographical Level'); ?></div>
        <div class="value"><?php echo $this->Form->input('geo_level_id', array('options' => $geoLvlOptions, 'default' => $regionId, 'class' => 'dash_options', 'url' => 'Dashboards/dashboardsAjaxGetArea', 'onchange' => 'Dashboards.areaChange(this)')); ?></div>
    </div>
	<div class="row left">
        <div class="label"><?php echo __('Area'); ?></div>
        <div class="value"><?php echo $this->Form->input('area_level_id', array('options' => $areaLvlOptions, 'default' => $areaId, 'class' => 'dash_options', 'url' => 'Dashboards/dashboardsAjaxGetArea'/*, 'onchange' => 'Dashboards.FDChange(this)'*/)); ?></div>
    </div>
	<?php /*<div class="row left">
        <div class="label"><?php echo __('Field Directorate'); ?></div>
        <div class="value"><?php echo $this->Form->input('fd_level_id', array('options' => $FDLvlOptions, 'default' => $FDId, 'class' => 'dash_options')); ?></div>
    </div>*/ ?>
	<div class="row left">
        <div class="label"><?php echo __('Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('year_id', array('options' => $yearsOptions, 'default' =>$yearId, 'class' => 'dash_options')); ?></div>
    </div>
	<div class="row left">
		<input type="submit" value="<?php echo __("Update"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	</div>
	<div class="clear_both underline"></div>
	<?php echo $this->Form->end(); ?>
	
	
	<?php /*
	echo '<div style="padding-top:10px;"></div>';
	foreach ($totalKGInfo as $item) {
		echo sprintf('%s = %s <br/>', $item['JORIndicator']['Indicator_Name'], $item['JORData']['Data_Value']);
	}*/
	?>
	
	<div style="padding-top:10px;"></div>
	<?php 
		foreach($displayChartData as $key => $item){
			if($item !== 'break'){
				$setupData['chartContainerId'] = 'dashboardChartContainer'.$key;
				$setupData['chartVarId'] = 'dashboardChartVar'.$key;
				$setupData['chartId'] = 'dashboardChartId-'.$key;
				
				$setupData = array_merge($setupData, $item);
				
				echo $this->element('chartTemplate', $setupData,array('plugin' => 'Dashboards'));
			}
			else{
				echo '<div class="clear_both" style="padding-top:10px; margin-bottom:10px;"></div>';
			}
		}
	?>
	
	<div class="table-responsive">
		<h3><?php echo $tableTitle; ?></h3>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr><?php echo $this->Html->tableHeaders($QATableData['tableHeaders']); ?></tr>
			</thead>
			<tbody><?php echo $this->Html->tableCells($QATableData['tableData'], array(),array('class' => 'even')); ?></tbody>
		</table>
	</div>
	<div class="controls">
		<?php echo $this->Html->link(__('Download CSV'), array('action' => 'genCSV',$areaId,$yearId), array('class' => 'btn_save')); ?>
	</div>
</div>