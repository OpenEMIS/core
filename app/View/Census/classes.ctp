<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
	<?php
	echo $this->Form->create('CensusClass', array(
		'id' => 'submitForm',
		'onsubmit' => 'return false',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'classes')
	));
	?>
	<h1>
		<span><?php echo __('Classes'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'classesEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
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
		
	<?php echo $this->element('census_legend'); ?>
	</div>
	
	<?php if($displayContent) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('Single Grade Classes Only'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Classes'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Seats'); ?></div>
			</div>
			
			<div class="table_body">
			<?php 
			$totalClasses = 0;
			$totalSeats = 0;
			
			foreach($singleGradeData as $name => $programme) { 
				foreach($programme['education_grades'] as $gradeId => $grade) {
					$totalClasses += $grade['classes'];
					$totalSeats += $grade['seats'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($grade['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
			?>
			
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $name; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $grade['name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['classes']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['seats']; ?></div>
				</div>
				
			<?php 
				}
			}
			?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalSeats; ?></div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Classes'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Classes'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Seats'); ?></div>
			</div>
			
			<?php 
			$totalClasses = 0;
			$totalSeats = 0;
			if(!empty($multiGradeData)) {
			?>
			<div class="table_body">
				<?php foreach($multiGradeData as $obj) { ?>
				<div class="table_row">
					<?php
					$totalClasses += $obj['classes'];
					$totalSeats += $obj['seats'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($obj['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
					?>
					<div class="table_cell <?php echo $record_tag; ?>">
						<?php foreach($obj['programmes'] as $programmeId => $programmeName) { ?>
						<div class="table_cell_row"><?php echo $programmeName; ?></div>
						<?php } ?>
					</div>
					
					<div class="table_cell <?php echo $record_tag; ?>">
						<?php foreach($obj['grades'] as $gradeId => $gradeName) { ?>
						<div class="table_cell_row"><?php echo $gradeName; ?></div>
						<?php } ?>
					</div>
					
					<div class="table_cell cell_number"><?php echo $obj['classes']; ?></div>
					<div class="table_cell cell_number"><?php echo $obj['seats']; ?></div>
				</div>
				<?php } // end for (multigrade) ?>
			</div>
			<?php } // end if empty(multigrade) ?>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalSeats; ?></div>
			</div>
		</div>
	</fieldset>
	
	<?php } ?>
	<?php echo $this->Form->end(); ?>
</div>