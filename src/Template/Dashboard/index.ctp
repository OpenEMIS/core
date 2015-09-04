<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');
?>
<div class="row dashboard-container">
	<div id="news">
		<h5><?php echo __('Notices'); ?></h5>
		<div class="dashboard-content">
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
		</div>
	</div>
</div>

<div class="row dashboard-container">
	<div id="workbench">
		<h5><?php echo __('Workbench'); ?></h5>
		<div class="dashboard-content">
			<table class="table table-striped table-hover table-bordered">
				<thead class="table_head">
					<tr>
						<th class="action"></td>
						<th class="table_cell"><?php echo __('Request Title'); ?></td>
						<th class="table_cell"><?php echo __('Received Date'); ?></td>
						<th class="table_cell"><?php echo __('Due Date'); ?></td>
						<th class="table_cell"><?php echo __('Requester'); ?></td>
						<th class="table_cell"><?php echo __('Type'); ?></td>
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

<?php
$this->end();
?>
