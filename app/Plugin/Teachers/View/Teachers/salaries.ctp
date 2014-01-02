<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="salary" class="content_wrapper">
	<h1>
		<span><?php echo __('Salary'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'salariesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Teachers/salariesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Gross'); ?></div>
			<div class="table_cell"><?php echo __('Additions'); ?></div>
			<div class="table_cell"><?php echo __('Deductions'); ?></div>
			<div class="table_cell"><?php echo __('Net'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['TeacherSalary']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherSalary']['salary_date']); ?></div>
				<div class="table_cell"><?php echo $obj['TeacherSalary']['gross_salary']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherSalary']['additions']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherSalary']['deductions']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherSalary']['net_salary']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>