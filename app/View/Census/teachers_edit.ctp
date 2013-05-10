<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_teachers', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teachers" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusTeacher', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'Census', 'action' => 'teachersEdit')
	));
	?>
	<h1>
		<span><?php echo __('Teachers'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'teachers'), array('id' => 'edit-link', 'class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>	
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'name' => 'school_year_id',
				'options' => $years,
				'default' => $selectedYear
			));
			?>
		</div>
		<div style="float:right;">
			<ul class="legend">
				<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
				<li><span class="external"></span><?php echo __('External'); ?></li>
				<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
			</ul>
		</div>
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
				$i = 0;
				$fieldName = 'data[CensusTeacherFte][%d][%s]';
				foreach($fte as $record) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					switch ($record['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $i, 'id'), 'value' => $record['id']));
					echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $i, 'education_level_id'), 'value' => $record['education_level_id']));
					?>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_level_name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('male', array(
								'type' => 'text',
								'class' =>$record_tag,
								'name' => sprintf($fieldName, $i, 'male'),
								'computeType' => 'cell_value',
								'value' => is_null($record['male']) ? 0 : $record['male'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('female', array(
								'type' => 'text',
								'name' => sprintf($fieldName, $i, 'female'),
								'class' =>$record_tag,
								'computeType' => 'cell_value',
								'value' => is_null($record['female']) ? 0 : $record['female'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							));
						?>
						</div>
					</div>
					<div class="table_cell cell_number cell_subtotal"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php $i=$i+1;
				} ?>
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
				$i = 0;
				$fieldName = 'data[CensusTeacherTraining][%d][%s]';
				foreach($training as $record) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					switch ($record['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $i, 'id'), 'value' => $record['id']));
					echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $i, 'education_level_id'), 'value' => $record['education_level_id']));
					?>
					<div class="table_cell"><?php echo $record['education_level_name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('male', array(
								'type' => 'text',
								'class' =>$record_tag,
								'name' => sprintf($fieldName, $i, 'male'),
								'computeType' => 'cell_value',
								'value' => is_null($record['male']) ? 0 : $record['male'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('female', array(
								'type' => 'text',
								'class' =>$record_tag,
								'name' => sprintf($fieldName, $i, 'female'),
								'computeType' => 'cell_value',
								'value' => is_null($record['female']) ? 0 : $record['female'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							));
						?>
						</div>
					</div>
					<div class="table_cell cell_number cell_subtotal"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php $i=$i+1; } ?>
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
			$i = 0;
			$fieldName = 'data[CensusTeacher][%d][%s]';
			
			foreach($singleGradeData as $name => $programme) { 
				foreach($programme['education_grades'] as $gradeId => $grade) {
					$totalMale += $grade['male'];
					$totalFemale += $grade['female'];
					$record_tag="";
					switch ($grade['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
			?>
			
				<div class="table_row">
					<?php
					echo $this->Form->hidden('education_grade_id', array(
						'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $i),
						'value' => $gradeId
					));
					?>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $name; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $grade['name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('male', array(
								'type' => 'text',
								'class' => $record_tag,
								'name' => sprintf($fieldName, $i, 'male'),
								'computeType' => 'total_male',
								'value' => $grade['male'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							)); 
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('female', array(
								'type' => 'text',
								'class' => $record_tag,
								'name' => sprintf($fieldName, $i++, 'female'),
								'computeType' => 'total_female',
								'value' => $grade['female'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							));
						?>
						</div>
					</div>
				</div>
				
			<?php 
				}
			}
			?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number total_male"><?php echo $totalMale; ?></div>
				<div class="table_cell cell_value cell_number total_female"><?php echo $totalFemale; ?></div>
			</div>
		</div>
	</fieldset>
	
	<?php
	$totalMale = 0;
	$totalFemale = 0;
	?>
	
	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Teachers'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Male'); ?></div>
				<div class="table_cell cell_gender"><?php echo __('Female'); ?></div>
				<div class="table_cell cell_delete"></div>
			</div>
			
			<?php if(!empty($multiGradeData)) { ?>
			<div class="table_body">
				<?php foreach($multiGradeData as $obj) { ?>
				<div class="table_row">
					<?php
					$totalMale += $obj['male'];
					$totalFemale += $obj['female'];
					$gradeIndex = 0;
					$record_tag="";
					switch ($obj['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
					?>
					<div class="table_cell">
						<?php foreach($obj['programmes'] as $programmeId => $programmeName) { ?>
						<div class="table_cell_row <?php echo $record_tag; ?>"><?php echo $programmeName; ?></div>
						<?php } ?>
					</div>
					
					<div class="table_cell">
						<?php foreach($obj['grades'] as $gradeId => $gradeName) { ?>
						<div class="table_cell_row">
							<?php 
							echo $gradeName;
							echo $this->Form->hidden('education_grade_id', array(
								'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][%d]', $i, $gradeIndex++),
								'value' => $gradeId
							));
							?>
						</div>
						<?php } ?>
					</div>
					
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('male', array(
								'type' => 'text',
								'class'=>$record_tag,
								'name' => sprintf($fieldName, $i, 'male'),
								'computeType' => 'total_male',
								'value' => $obj['male'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							)); 
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('female', array(
								'type' => 'text',
								'class'=>$record_tag,
								'name' => sprintf($fieldName, $i++, 'female'),
								'computeType' => 'total_female',
								'value' => $obj['female'],
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
					</div>
				</div>
				<?php } // end for (multigrade) ?>
			</div>
			<?php } // end if empty(multigrade) ?>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number total_male"><?php echo $totalMale; ?></div>
				<div class="table_cell cell_value cell_number total_female"><?php echo $totalFemale; ?></div>
			</div>
		</div>
		
		<?php if($_add) { ?>
		<div class="row">
			<a class="void icon_plus" id="add_multi_teacher" url="Census/teachersAddMultiTeacher/<?php echo $selectedYear; ?>"><?php echo __('Add').' '.__('Multi Grade Teacher'); ?></a>
		</div>
		<?php } ?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	
	<?php } // end display content ?>
	<?php echo $this->Form->end(); ?>
</div>