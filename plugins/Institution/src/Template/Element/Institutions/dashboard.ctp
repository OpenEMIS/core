<?php
echo $this->Html->script('doughnut', ['block' => true]); 
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>

<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section" style="vertical-align: middle;">
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4>Total Institutions:</h4>
			<h1 class="data-header">
			<?= $institutionCount ?>
			</h1>
		</div>
	</div>
	<?php foreach ( $institutionSiteArray as $key => $highChartData ) : ?>
	<div class="data-section" style="vertical-align: middle;">
		<div class="data-field">
			<h4 style="float:left;"><?=$key?>:</h4>
				<div class="highchart" style="height: 98px; display: none; width:200px; float: right; position: relative; right: 30px;"><?php echo $highChartData; ?></div>
		</div>
	</div>
	<?php endforeach ?>
</div>
