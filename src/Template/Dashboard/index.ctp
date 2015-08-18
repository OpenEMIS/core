<?php
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('highchart/modules/exporting', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>
<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');
?>
<div class="row">
	<div id="news">
		<div style="padding: 0px 20px 20px 20px; border: 1px solid grey">
			<h5><?php echo __('Notices'); ?></h5>
			<div style='overflow:auto;max-height:290px'>
			<?php 	//if (!empty($noticeData)) { ?>
				<table class="table table-striped table-hover table-bordered">
					<tbody class="table_body">
					<?php 
					$count = 0;
					foreach ($noticeData as $key => $value) {
						$count++;
						?>
						<tr>
							<td>
								<?php echo $value->created ?>: 
								<?php echo $value->message ?>
							</td>
						</tr>
						<?php 
					} 

					if ($count == 0 ) {
						?>
						<tr>
							<td>
								<?=__('No Notices')?>
							</td>
						</tr>	
					<?php	
					}
					?>
					</tbody>
				</table>
			<?php 

					// } else {
					// 	//$this->Alert->getMessage('general.select.noOptions');
					// }
			?>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div id="workbench">
		<div style="padding: 0px 20px 20px 20px; border: 1px solid grey">
			<h5><?php echo __('Workbench'); ?></h5>
			<div style='overflow:auto;max-height:290px'>
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
						<?php foreach ($workbenchData as $key => $obj) : ?>
							<tr>
								<td></td>
								<td>
									<?php
										if (array_key_exists('url', $obj['request_title']) && !empty($obj['request_title']['url'])) {
											echo $this->Html->link($obj['request_title']['title'], $obj['request_title']['url']);
										} else {
											echo $obj['request_title']['title'];
										}
									?>
								</td>
								<td><?= $obj['receive_date'] ?></td>
								<td><?= $obj['due_date'] ?></td>
								<td><?= $obj['requester'] ?></td>
								<td><?= $obj['type'] ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php
$this->end();
?>
