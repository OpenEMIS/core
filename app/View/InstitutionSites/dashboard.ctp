<?php
echo $this->Html->script('/HighCharts/js/highcharts', false);
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('institution_site_dashboards', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<?php
	pr($highChartDatas);
?>

<?php foreach ($highChartDatas as $key => $highChartData) : ?>
	<?php if ($key == 0) : ?>
		<div class="form-group">
	<?php endif ?>
			<div id="container<?php echo $key;?>" class="col-md-6"><?php echo $highChartData; ?></div>
	<?php if ($key % 2 == 1) : ?>
		</div>
		<div class="form-group">
	<?php endif ?>
	<?php if ($key == count($highChartDatas)) : ?>
		</div>
	<?php endif ?>
<?php endforeach ?>

<?php
$this->end();
?>
