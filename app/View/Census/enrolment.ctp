<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_enrolment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="enrolment" class="content_wrapper">
	<?php
	echo $this->Form->create('CensusStudent', array(
			'id' => 'submitForm',
			'onsubmit' => 'return false',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Census', 'action' => 'enrolment')
		)
	);
	?>
	<h1>
		<span><?php echo __('Enrolment'); ?></span>
		<?php
		if($_edit && $displayContent) {
			echo $this->Html->link(__('Edit'), array('action' => 'enrolmentEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if($displayContent) { ?>
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $years,
				'default' => $selectedYear
			));
			?>
		</div>
	</div>
	<?php } ?>
	
	<?php foreach($data as $key => $val) { ?>
	<fieldset class="section_group" programme-id="<?php echo $val['id']; ?>">
		<legend><?php echo $key ?></legend>
		
		<div class="row" style="margin-bottom: 15px;">
			<div class="label grade"><?php echo __('Grade'); ?></div>
			<div class="value grade">
				<?php
					echo $this->Form->input('education_grade_id', array(
						'id' => 'EducationGradeId',
						'options' => $val['grades'],
						'onchange' => sprintf('CensusEnrolment.get(%d)', $val['id']),
						'autocomplete' => 'off'
					));
				?>
			</div>
			<div class="label category"><?php echo __('Category'); ?></div>
			<div class="value category">
				<?php
					echo $this->Form->input('student_category_id', array(
						'id' => 'StudentCategoryId',
						'options' => $category,
						'onchange' => sprintf('CensusEnrolment.get(%d)', $val['id']),
						'autocomplete' => 'off'
					));
				?>
			</div>
		</div>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Age'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<?php 
			$total = 0;
			$records = $val['enrolment'];
			if(!empty($records) && !(sizeof($records)==1 && $records[0]['male']==0 && $records[0]['female']==0)) {
			?>
			<div class="table_body">
				<?php
				foreach($records as $record) {
					$total += $record['male'] + $record['female'];
				?>
				<div class="table_row">
					<div class="table_cell cell_number"><?php echo $record['age']; ?></div>
					<div class="table_cell cell_number"><?php echo $record['male']; ?></div>
					<div class="table_cell cell_number"><?php echo $record['female']; ?></div>
					<div class="table_cell cell_number"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php } // end foreach (records) ?>
			</div>
			<?php } ?>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total ?></div>
			</div>
		</div>
	</fieldset>
	<?php } // end foreach (data) ?>
	
	<?php echo $this->Form->end(); ?>
</div>
