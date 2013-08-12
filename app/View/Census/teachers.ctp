<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teachers" class="content_wrapper">
	<h1>
		<span><?php echo __('Teachers'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'teachersEdit', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>	

	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		<?php echo $this->element('census_legend'); ?>
	</div>

	<?php if($displayContent) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('Full Time Equivalent Teachers'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Education Level'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				$total = 0;
				foreach($fte as $record) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_level_name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male'])||(!$record['male']>0) ? 0 : str_replace(".0","",$record['male']); ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female'])||(!$record['female']>0) ? 0 : str_replace(".0","",$record['female']); ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php } ?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Trained Teachers'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Education Level'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				$total = 0;
				foreach($training as $record) {
					$total += $record['male'] + $record['female'];
					$total += $record['male'] + $record['female'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_level_name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php } ?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Single Grade Teachers Only'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Male'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Female'); ?></div>
			</div>
			
			<div class="table_body">
			<?php 
			$totalMale = 0;
			$totalFemale = 0;
			
			foreach($singleGradeData as $name => $programme) { 
				foreach($programme['education_grades'] as $gradeId => $grade) {
					$totalMale += $grade['male'];
					$totalFemale += $grade['female'];
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
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['male']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['female']; ?></div>
				</div>
				
			<?php 
				}
			}
			?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalMale; ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalFemale; ?></div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Teachers'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Male'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Female'); ?></div>
			</div>
			
			<?php 
			$totalMale = 0;
			$totalFemale = 0;
			if(!empty($multiGradeData)) {
			?>
			<div class="table_body">
				<?php foreach($multiGradeData as $obj) { ?>
				<div class="table_row">
					<?php
					$totalMale += $obj['male'];
					$totalFemale += $obj['female'];
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
					
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $obj['male']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $obj['female']; ?></div>
				</div>
				<?php } // end for (multigrade) ?>
			</div>
			<?php } // end if empty(multigrade) ?>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalMale; ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalFemale; ?></div>
			</div>
		</div>
	</fieldset>
	
	<?php } // end display content ?>
</div>
