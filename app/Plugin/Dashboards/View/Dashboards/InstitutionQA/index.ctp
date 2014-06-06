<?php /*
echo $this->Html->css('Dashboards.dashboard', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);
echo $this->Html->script('/Dashboards/js/Charts/FusionCharts', false);
echo $this->Html->script('Dashboards.dashboards', false);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="dashboard" class="dashboard_wrapper edit add">
	<h1>
		<span><?php echo $header; ?></span>
		<?php echo $this->Html->link(__('Back'),array('controller' => 'Dashboards','action' => 'general', 'plugin' => false), array('class' => 'divider'));?>
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
	
	if(!empty($displayChartData)):
	?>
	
	<div class="row left">
        <div class="label"><?php echo __('Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('year_id', array('options' => $yearsOptions, 'default' =>$yearId, 'class' => 'dash_options')); ?></div>
    </div>
	<div class="row left">
		<input type="submit" value="<?php echo __("Update"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	</div>
	<div class="clear_both underline"></div>
	<?php echo $this->Form->end(); ?>
	
	
	<?php endif;?>
	
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
	
</div>
 * 
 */?>

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
echo $this->Html->link($this->Label->get('general.back'), array('controller' => 'Dashboards','action' => 'general', 'plugin' => false), array('class' => 'divider'));
$this->end();
$this->start('contentBody');

$formOptions = array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Dashboards');
$formOptions = array_merge($formOptions, $this->params['pass']);

echo $this->Form->create($modelName, array('url' => $formOptions, 'novalidate' => 1, 'class' => 'form-horizontal', 'inputDefaults' => array('class' => 'form-control')));

if(!empty($displayChartData)){
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
}
$this->end();
?>