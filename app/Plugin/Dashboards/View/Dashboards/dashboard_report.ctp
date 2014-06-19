<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->assign('contentId', 'report-list');
$this->start('contentBody');

$ctr = 0;
if (@$enabled === true) {
	if (count($data) > 0) {
		foreach ($data as $module => $arrVals) {
			?>
			<div id="alertError" title="Click to dismiss" class="alert alert-error" style="position:relative; margin-bottom: 10px;display: <?php echo ($msg != '') ? 'block' : 'none'; ?>; opacity: 0.891195;"><div class="alert-icon"></div><div class="alert-content"><?php echo __('The selected report is currently being processed.'); ?></div></div>

			<?php foreach ($arrVals as $type => $arrTypVals) { ?>
			<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
						<thead class="table_head">
							<tr>
								<td class="table_cell col_name"><?php echo __('Name'); ?></td>
								<td class="table_cell col_desc"><?php echo __('Description'); ?></td>
							</tr> 
						</thead> 
						<tbody class="table_body">
								<?php 
									$ctr = 1;
									foreach ($arrTypVals as $key => $value) { 
								?>
								<tr class="table_row" row-id="<?php echo $value['id']; ?>">
									<td class="table_cell col_name"><?php echo $this->Html->link(__($value['name']), array('action' => 'overview', $value['id']), array('escape' => false)); ?></td>
									<td class="table_cell col_desc"><?php echo __($value['description']);?></td>
								</tr>
								<?php  $ctr++;  } ?>
						</tbody>
					</table>
					</div>
				<?php
			}
		}
		$ctr++;
	}
} else {
	echo __('Report Feature disabled');
}
$this->end();
?>