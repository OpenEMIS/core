<?php
echo $this->Html->script('doughnut', ['block' => true]); 
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>

<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section" style="vertical-align: middle;">
		<i class="kd-<?=$model ?> icon"></i>
		<div class="data-field">
			<h4>Total Institutions:</h4>
			<h1 class="data-header">
			<?= $modelCount ?>
			</h1>
		</div>
	</div>
	<?php foreach ( $modelArray as $highChartData ) : ?>
	<div class="data-section" style="vertical-align: middle;">
		<div class="data-field" style="postion: relative; display: inline-block">
				<div class="highchart" style="height: 140px; width:140px; display: none;"><?php echo $highChartData; ?></div>
		</div>
	</div>
	<?php endforeach ?>
</div>
