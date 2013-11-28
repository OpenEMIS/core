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
			<div class="table_cell"><?php echo __('Status'); ?></div>
			<div class="table_cell"><?php echo __('First Day'); ?></div>
			<div class="table_cell"><?php echo __('Last Lay'); ?></div>
			<div class="table_cell"><?php echo __('Comments'); ?></div>
			<div class="table_cell"><?php echo __('No of Days'); ?></div>
		</div>
		
		<div class="table_body">
			<?php
			$total = array();
			foreach($data as $obj): 
				$startDate = new DateTime($obj['StaffLeave']['date_from']);
				$endDate = new DateTime($obj['StaffLeave']['date_to']);
				$days = $obj['StaffLeave']['number_of_days'];
				$type = $obj['StaffLeaveType']['name'];
				$status = $obj['LeaveStatus']['name'];
				if(!array_key_exists($obj['StaffLeaveType']['name'], $total)) {
					$total[$type] = $days;
				} else {
					$total[$type] = $total[$type] + $days;
				}
			?>
			<div class="table_row" row-id="<?php echo $obj['StaffLeave']['id']; ?>">
				<div class="table_cell"><?php echo $type; ?></div>
				<div class="table_cell"><?php echo $status; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffLeave']['date_from']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffLeave']['date_to']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffLeave']['comments']; ?></div>
				<div class="table_cell cell_number"><?php echo $days; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="table" style="margin: 30px auto 0 auto;">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Total Days'); ?></div>
		</div>
		<div class="table_body">
			<?php foreach($total as $name => $val): ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $name; ?></div>
				<div class="table_cell cell_number"><?php echo $val; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>