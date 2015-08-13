<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if (!array_key_exists('type', $btn) || $btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	$tableHeaders = [
		__('Name'),
		__('Started On'),
		__('Completed On'),
		__('Expires On'),
		[__('Status') => ['style' => 'width: 150px']]
	];

	$params = $this->request->params;
	$url = ['plugin' => $params['plugin'], 'controller' => $params['controller'], 'action' => 'ajaxGetReportProgress'];
	$url = $this->Url->build($url);
	$table = $ControllerAction['table'];
?>

<style type="text/css">
.none { display: none !important; }
</style>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered" id="ReportList" url="<?= $url ?>">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr row-id="<?= $obj->id ?>">
				<td><?= $obj->name ?></td>
				<td><?= $table->formatDateTime($obj->created) ?></td>
				<td class="modified"><?= !empty($obj->file_path) ? $table->formatDateTime($obj->modified) : '' ?></td>
				<td><?= $table->formatDateTime($obj->expiry_date) ?></td>
				<td>
					<?php
					$downloadClass = 'download';
					$errorClass = 'none';
					$status = $obj->status;
					if ($status == 1 && empty($obj->file_path)) {
						$downloadClass = 'download none';
						$progress = 0;
						$current = $obj->current_records;
						$total = $obj->total_records;
						if ($current > 0 && $total > 0) {
							$progress = intval($current / $total * 100);
						}

						echo '<div class="progress progress-striped active" style="margin-bottom:0">';
						echo '<div class="progress-bar progress-bar-striped" role="progressbar" data-transitiongoal="' . $progress . '"></div>';
						echo '</div>';
					} else if ($status == -1) {
						$downloadClass = 'none';
						$errorClass = '';
					}
					echo $this->Html->link(__('Download'), ['action' => 'download', $obj->id], ['class' => $downloadClass]);
					?>
					<a href="#" data-toggle="tooltip" title="<?= __('Please contact the administrator for assistance.') ?>" class="<?php echo $errorClass ?>"><?php echo __('Error') ?></a>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
