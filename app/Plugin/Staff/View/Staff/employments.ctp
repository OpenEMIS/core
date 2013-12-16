<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="employment" class="content_wrapper">
	<h1>
		<span><?php echo __('Employments'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'employmentsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/employmentsView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Comment'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffEmployment']['id']; ?>">
				<div class="table_cell"><?php echo $obj['StaffEmployment']['name']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffEmployment']['employment_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffEmployment']['comment']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>