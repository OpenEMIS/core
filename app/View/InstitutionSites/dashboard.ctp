<?php
echo $this->Html->css('highchart-override', 'stylesheet', array('inline' => false));
echo $this->Html->script('/HighCharts/js/highcharts', false);
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('institution_site_dashboards', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>

<div class="row institution-dashboard">
	<?php foreach ($highChartDatas as $key => $highChartData) : ?>
		<div class="highchart col-md-6"><?php echo $highChartData; ?></div>
	<?php endforeach ?>
</div>

<?php
$this->end();
?>
