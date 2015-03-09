<?php
echo $this->Html->script('/HighCharts/js/highcharts', false);
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('institution_site_dashboards', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>

<?php
	$index = 1;
	foreach ($highChartDatas as $key => $highChartData) :
?>
	<?php if ($index == 1) : ?>
		<div class="form-group">
	<?php endif ?>
			<div id="container<?php echo $index;?>" class="col-md-6"><?php echo $highChartData; ?></div>
	<?php if ($index % 2 == 0) : ?>
		</div>
		<div class="row"></div>
		<div class="form-group">
	<?php endif ?>
	<?php if ($index == count($highChartDatas) + 1) : ?>
		</div>
	<?php endif ?>
<?php
	$index++;
	endforeach;
?>

<?php
$this->end();
?>
