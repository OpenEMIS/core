<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper">
	<h1>
		<span><?php echo __('Leave'); ?></span>
		<?php 
		if ($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'leavesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="table full_width allow_hover" action="Staff/leavesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('From'); ?></div>
			<div class="table_cell"><?php echo __('To'); ?></div>
			<div class="table_cell"><?php echo __('Comments'); ?></div>
			<div class="table_cell"><?php echo __('No of Days'); ?></div>
		</div>
		
		<div class="table_body">
			<?php
			$total = array();
			foreach($data as $obj): 
				$startDate = new DateTime($obj['StaffLeave']['date_from']);
				$endDate = new DateTime($obj['StaffLeave']['date_to']);
				$days = $startDate->diff($endDate)->days;
				$type = $obj['StaffLeaveType']['name'];
				if(!array_key_exists($obj['StaffLeaveType']['name'], $total)) {
					$total[$type] = $days;
				} else {
					$total[$type] = $total[$type] + $days;
				}
			?>
			<div class="table_row" row-id="<?php echo $obj['StaffLeave']['id']; ?>">
				<div class="table_cell"><?php echo $type; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffLeave']['date_from']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffLeave']['date_to']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffLeave']['comments']; ?></div>
				<div class="table_cell cell_number"><?php echo $days; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="summary" style="margin-left: 10px; font-size: 11px;">
	<?php 
	foreach($total as $name => $val) {
		echo $name . ' = ' . $val . '<br>';
	}
	?>
	</div>
</div>