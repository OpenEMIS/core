<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', key($data));

$this->start('contentBody');

?>
<?php if (count($data) > 0) { ?>
	<?php foreach ($data as $module => $arrVals) { ?>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered" action="Reports/<?php echo $this->action; ?>/">
				<thead>
					<tr>
						<td class="table_cell col_name"><?php echo __('Name'); ?></td>
						<td class="table_cell" style="width:100px"><?php echo __('Types'); ?></td> 
					</tr>
				</thead> 
				<tbody class="table_body">
					<?php foreach ($arrVals as $arrTypVals) { ?>
						<tr class="table_row" row-id="<?php echo $arrTypVals['name']; ?>">
							<td class="table_cell col_name"><?php echo __($arrTypVals['name']); ?></td>
							<td class="table_cell"  style="width:100px;text-align: center">
								<?php foreach ($arrTypVals['types'] as $val) { ?>
									<?php
									echo $this->Html->link(__($val), array('controller' => 'InstitutionSites', 'action' => $actionName, $arrTypVals['name'], $val));
								}
								?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } ?>
<?php
}
$this->end();
?>