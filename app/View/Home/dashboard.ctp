<?php
echo $this->Html->css('highchart-override', 'stylesheet', array('inline' => false));
echo $this->Html->script('/HighCharts/js/highcharts', false);
echo $this->Html->script('/HighCharts/js/modules/exporting', false);
echo $this->Html->script('dashboards', false);
echo $this->element('alert');
?>

<div class="row">
	<div class="col-md-12">
		<h3>Dashboard</h3>
	</div>
</div>

<div class="row">
	<div id="workbench" class="col-md-8">
		<div style="padding: 0px 20px 0px 20px; border: 1px solid grey">
			<h5><?php echo __('Workbench'); ?></h5>
			<div style='overflow:auto;height:320px'>
				<table class="table table-striped table-hover table-bordered">
					<thead class="table_head">
						<tr>
							<td class="action"></td>
							<td class="table_cell"><?php echo __('Request Title'); ?></td>
							<td class="table_cell"><?php echo __('Received Date'); ?></td>
							<td class="table_cell"><?php echo __('Due Date'); ?></td>
							<td class="table_cell"><?php echo __('Requester'); ?></td>
							<td class="table_cell"><?php echo __('Type'); ?></td>
						</tr> 
					</thead> 
					<tbody class="table_body">

					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div id="news" class="col-md-4">
		<div style="padding: 0px 20px 0px 20px; border: 1px solid grey">
			<h5><?php echo __('Notices'); ?></h5>
			<div style='overflow:auto;height:320px'>
				<table class="table table-striped table-hover table-bordered">
					<tbody class="table_body">
					<?php 
					foreach ($noticeData as $key => $value) {
						?>
						<tr>
							<td>
								<?php echo $this->Utility->formatDate($value['Notice']['created']) ?>: 
								<?php echo $value['Notice']['message'] ?>
							</td>
						</tr>
						<?php 
					} 
					?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<hr>

<div class="row institution-dashboard">
	<?php foreach ($highChartDatas as $key => $highChartData) : ?>
		<div class="highchart col-md-4"><?php echo $highChartData; ?></div>
	<?php endforeach ?>
</div>
