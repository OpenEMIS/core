<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusShift', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'Census', 'action' => 'shiftsEdit')
	));
	?>
	<h1>
		<span><?php echo __('Shifts'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'shifts', $selectedYear), array('class' => 'divider')); ?>
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
				<?php 
				for($i=1;$i<=intval($no_of_shifts);$i++){
					echo '<div class="table_cell cell_shifts">' . __('Shift')  . ' ' . $i . '</div>';
				}?>
			</div>
			
			<div class="table_body">
			<?php 
			$totalShift = array();
			$i = 0;
			//pr($singleGradeData);
			foreach($singleGradeData as $name => $programme) {
				foreach($programme['education_grades'] as $gradeId => $grade) {
					//$totalShift[$grade['shift_id']] += $grade['value'];
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
					<?php 
					for($s=1;$s<=intval($no_of_shifts);$s++){ ?>
						<?php 
						$value = "";
						if(isset($grade['shift_' . $s])){
							$value = $grade['shift_' . $s];
						}?>
					 	<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($grade['class_id'] . '.' . $s . '.shift_value', array(
								'type' => 'text',
								'class' => $record_tag,
								'computeType' => 'total_shifts_' . $s,
								'value' => $value,
								'maxlength' => 5,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeTotal(this)'
							)); 
						?>
						</div>
						</div>
					<?php
						}
					?>
				</div>
				
			<?php 
				}
			}
			?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<?php 
					for($s=1;$s<=intval($no_of_shifts);$s++){ ?>
						<div class="table_cell cell_value cell_number total_shifts_<?php echo $s;?>"><?php echo '0'; ?></div>
				<?php
					}
				?>
			</div>
		</div>
	</fieldset>
	
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classes', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } // end display content ?>
	<?php echo $this->Form->end(); ?>
</div>

