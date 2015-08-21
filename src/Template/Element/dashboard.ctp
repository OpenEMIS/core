<?php
/**
* Mini Dashboard
*/
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>
<!-- Please take note of the CSS for this chart place holder -->
<style type="text/css">
	.data-section {
		vertical-align: middle;
	}
	.minidashboard-donut {
		height: 100px;
		width: 100px;
		visibility: hidden;
	}
</style>
<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<!--Getting the correct icon and the header name base on the calling method-->
		<i class="kd-<?=$model ?> icon"></i>
		<div class="data-field">
			<h4><?= __('Total ' . ucfirst($model)) ?>:</h4>
			<h1 class="data-header">
			<?= $modelCount ?>
			</h1>
		</div>
	</div>
	<?php foreach ( $modelArray as $highChartData ) : ?>
	<div class="data-section">
		<div class="data-field">
			<div class="highchart minidashboard-donut"><?php echo $highChartData; ?></div>
		</div>
	</div>
	<?php endforeach ?>
</div>
