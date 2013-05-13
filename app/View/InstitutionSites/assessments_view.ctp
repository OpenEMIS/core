<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment" class="content_wrapper">
	<h1>
		<span><?php echo __('Assessment Details'); ?></span>
		<?php 
		echo $this->Html->link(__('List'), array('action' => 'assessmentsList'), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'assessmentsEdit', $data['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group info">
		<legend><?php echo __('Assessment Details'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value"><?php echo $data['school_year_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Code'); ?></div>
			<div class="value"><?php echo $data['code']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $data['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Description'); ?></div>
			<div class="value description"><?php echo $data['description']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Level'); ?></div>
			<div class="value"><?php echo $data['education_level_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value"><?php echo $data['education_programme_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Grade'); ?></div>
			<div class="value"><?php echo $data['education_grade_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Utility->getStatus($data['visible']); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_group items">
		<legend><?php echo __('Assessment Items'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_subject_code"><?php echo __('Code'); ?></div>
				<div class="table_cell"><?php echo __('Subject'); ?></div>
				<div class="table_cell cell_number_input"><?php echo __('Minimum'); ?></div>
				<div class="table_cell cell_number_input"><?php echo __('Maximum'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				foreach($data['AssessmentItem'] as $item) {
					if($item['visible'] == 1) {
				?>
				<div class="table_row">
					<div class="table_cell"><?php echo $item['code']; ?></div>
					<div class="table_cell"><?php echo $item['name']; ?></div>
					<div class="table_cell cell_number"><?php echo $item['min']; ?></div>
					<div class="table_cell cell_number"><?php echo $item['max']; ?></div>
				</div>
				<?php }
				} ?>
			</div>
		</div>
	</fieldset>
</div>
