<?php
echo $this->Html->css('/js/plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', array('inline' => false));
echo $this->Html->script('plugins/progressbar/bootstrap-progressbar.min', array('inline' => false));
echo $this->Html->script('report.list', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Reports'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered" id="ReportList" url="<?php echo $this->Html->url(array('action' => 'ajaxGetReportProgress')) ?>">
		<thead>
			<tr>
				<th><?php echo __('Name') ?></th>
				<th><?php echo __('Started On') ?></th>
				<th><?php echo __('Completed On') ?></th>
				<th><?php echo __('Expires On') ?></th>
				<th style="width: 150px"><?php echo __('Status') ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr row-id="<?php echo $obj[$model]['id'] ?>">
				<td><?php echo $obj[$model]['name'] ?></td>
				<td><?php echo $obj[$model]['created'] ?></td>
				<td class="modified"><?php echo !empty($obj[$model]['file_path']) ? $obj[$model]['modified'] : '' ?></td>
				<td><?php echo $obj[$model]['expiry_date'] ?></td>
				<td>
					<?php
					$class = '';
					if (empty($obj[$model]['file_path'])) {
						$class = 'none';
						$progress = 0;
						$current = $obj[$model]['current_records'];
						$total = $obj[$model]['total_records'];
						if ($current > 0 && $total > 0) {
							$progress = intval($current / $total * 100);
						}

						echo '<div class="progress progress-striped active" style="margin-bottom:0">';
						echo '<div class="progress-bar progress-bar-striped" role="progressbar" data-transitiongoal="' . $progress . '"></div>';
						echo '</div>';
					}
					echo $this->Html->link(__('Download'), array('action' => 'download', $obj[$model]['id']), array('class' => $class));
					?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
