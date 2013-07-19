<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusClass', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'Census', 'action' => 'classesEdit')
	));
	?>
	<h1>
		<span><?php echo __('Classes'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'classes', $selectedYear), array('class' => 'divider')); ?>
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
			$i = 0;
			
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
					<?php
					echo $this->Form->hidden('education_grade_id', array(
						'name' => sprintf('data[CensusClass][%d][CensusClassGrade][0]', $i),
						'value' => $gradeId
					));
					?>
					<div class="table_cell" <?php echo $record_tag; ?>><?php echo $name; ?></div>
					<div class="table_cell" <?php echo $record_tag; ?>><?php echo $grade['name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($i . '.classes', array(
								'type' => 'text',
								'class' => $record_tag,
								'computeType' => 'total_classes',
								'value' => $grade['classes'],
								'maxlength' => 5,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							)); 
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($i++ . '.seats', array(
								'type' => 'text',
								'class' => $record_tag,
								'computeType' => 'total_seats',
								'allowNull' => true,
								'value' => $grade['seats'],
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
				<div class="table_cell cell_value cell_number total_classes"><?php echo $totalClasses; ?></div>
				<div class="table_cell cell_value cell_number total_seats"><?php echo $totalSeats; ?></div>
			</div>
		</div>
	</fieldset>
	
	<?php
	$totalClasses = 0;
	$totalSeats = 0;
	?>
	
	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Classes'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Classes'); ?></div>
				<div class="table_cell cell_classes"><?php echo __('Seats'); ?></div>
				<div class="table_cell cell_delete"></div>
			</div>
			
			<?php if(!empty($multiGradeData)) { ?>
			<div class="table_body">
				<?php foreach($multiGradeData as $obj) { ?>
				<div class="table_row">
					<?php
					$totalClasses += $obj['classes'];
					$totalSeats += $obj['seats'];
					$gradeIndex = 0;
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
						<div class="table_cell_row">
							<?php 
							echo $gradeName;
							echo $this->Form->hidden('education_grade_id', array(
								'name' => sprintf('data[CensusClass][%d][CensusClassGrade][%d]', $i, $gradeIndex++),
								'value' => $gradeId
							));
							?>
						</div>
						<?php } ?>
					</div>
					
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($i . '.classes', array(
								'type' => 'text',
								'class'=>$record_tag,
								'computeType' => 'total_classes',
								'value' => $obj['classes'],
								'maxlength' => 5,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							)); 
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($i++ . '.seats', array(
								'type' => 'text',
								'class'=>$record_tag,
								'computeType' => 'total_seats',
								'allowNull' => true,
								'value' => $obj['seats'],
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
				<div class="table_cell cell_value cell_number total_classes"><?php echo $totalClasses; ?></div>
				<div class="table_cell cell_value cell_number total_seats"><?php echo $totalSeats; ?></div>
			</div>
		</div>
		
		<?php if($_add) { ?>
		<div class="row">
			<a class="void icon_plus" id="add_multi_class" url="Census/classesAddMultiClass/<?php echo $selectedYear; ?>">
			<?php echo __('Add').' '.__('Multi Grade Class'); ?>
			</a>
		</div>
		<?php } ?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classes', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } // end display content ?>
	<?php echo $this->Form->end(); ?>
</div>

