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

	<div class="table full_width allow_hover" action="Teachers/leavesView/">
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
				$startDate = new DateTime($obj['TeacherLeave']['date_from']);
				$endDate = new DateTime($obj['TeacherLeave']['date_to']);
				$days = $startDate->diff($endDate)->days;
				$type = $obj['TeacherLeaveType']['name'];
				if(!array_key_exists($obj['TeacherLeaveType']['name'], $total)) {
					$total[$type] = $days;
				} else {
					$total[$type] = $total[$type] + $days;
				}
			?>
			<div class="table_row" row-id="<?php echo $obj['TeacherLeave']['id']; ?>">
				<div class="table_cell"><?php echo $type; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherLeave']['date_from']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherLeave']['date_to']); ?></div>
				<div class="table_cell"><?php echo $obj['TeacherLeave']['comments']; ?></div>
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