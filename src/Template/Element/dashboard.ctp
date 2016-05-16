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
<div class="overview-box alert" ng-class="disableElement">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<!--Getting the correct icon and the header name base on the calling method-->
		<?php if (isset($iconClass) && !empty($iconClass)): ?>
		<i class="<?=$iconClass ?> icon"></i>
		<?php else: ?>
		<i class="kd-<?=$model ?> icon"></i>
		<?php endif; ?>
		<div class="data-field">
			<h4><?= __('Total ' . ucfirst($model)) ?>:</h4>
			<h1 class="data-header">
			<?= number_format($modelCount) ?>
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
