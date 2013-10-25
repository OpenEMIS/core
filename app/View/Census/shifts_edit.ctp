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
				<div class="table_cell"><?php echo __('Classes'); ?></div>
				<?php 
				for($i=1;$i<=intval($no_of_shifts);$i++){
					echo '<div class="table_cell cell_shifts">' . __('Shift')  . ' ' . $i . '</div>';
				}?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
			<?php 
			$totalClasses = 0;
			$i = 0;

			foreach($singleGradeData as $name => $value) {
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if(isset($value['shift_source'])){
							if ($value['shift_source']==$v) {
								$record_tag = "row_" . $k;
							}
						}
					}
					$totalClasses += $value['classes'];
			?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $value['education_programme_name']; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $value['education_grade_name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></div>

					<?php
					$totalShifts = 0;
					$pk = $value['id'];

					

					for($s=1;$s<=intval($no_of_shifts);$s++){ ?>
						<?php 
						$shift = null;
						if(isset($this->request->data['CensusShift'][$pk])){
							$shift = $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
							$totalShifts += $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
						}else{
							if(isset($value['shift_' . $s])){
								$shift = $value['shift_' . $s];
								$totalShifts += $shift;
							}
						}?>
					 	<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						if(isset($value['shift_pk_' . $s])){
							echo $this->Form->hidden($pk  . '_shift_pk_' . $s, array(
									'value' => $value['shift_pk_' . $s]
								));
						}
						?>
						<?php echo $this->Form->input($pk  . '.shift_value_' . $s, array(
								'type' => 'text',
								'class' => $record_tag,
								'computeType' => 'cell_subtotal',
								'default' => $shift,
								'maxlength' => 5,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							)); 
						?>
						</div>
						</div>
					<?php
						}
						echo $this->Form->hidden($pk . '.shift_class_total', array(
							'value' => $value['classes']
						));
					?>
					<div class="table_cell cell_number cell_subtotal"><?php echo $totalShifts; ?></div>
				</div>	
			<?php 
			}
			?>
			</div>
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></div>
				
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Multi Grade Classes Only'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Classes'); ?></div>
				<?php 
				for($i=1;$i<=intval($no_of_shifts);$i++){
					echo '<div class="table_cell cell_shifts">' . __('Shift')  . ' ' . $i . '</div>';
				}?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
			<?php 
			$totalClasses = 0;
			$i = 0;

			foreach($multiGradeData as $name => $value) {
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if(isset($value['shift_source'])){
							if ($value['shift_source']==$v) {
								$record_tag = "row_" . $k;
							}
						}
					}
					$totalClasses += $value['classes'];
			?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>">
						<?php foreach($value['programmes'] as $programmeId => $programmeName) { ?>
						<div class="table_cell_row"><?php echo $programmeName; ?></div>
						<?php } ?>
					</div>
					
					<div class="table_cell <?php echo $record_tag; ?>">
						<?php foreach($value['grades'] as $gradeId => $gradeName) { ?>
						<div class="table_cell_row"><?php echo $gradeName; ?></div>
						<?php } ?>
					</div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></div>

					<?php
					$totalShifts = 0;
					$pk = $name;
					for($s=1;$s<=intval($no_of_shifts);$s++){ ?>
						<?php 
						$shift = null;
						if(isset($this->request->data['CensusShift'][$pk])){
							$shift = $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
							$totalShifts += $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
						}else{
							if(isset($value['shift_' . $s])){
								$shift = $value['shift_' . $s];
								$totalShifts += $shift;
							}
						}?>
					 	<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						if(isset($value['shift_pk_' . $s])){
							echo $this->Form->hidden($pk . '_shift_pk_' . $s, array(
									'value' => $value['shift_pk_' . $s]
								));
						}
						?>
						<?php echo $this->Form->input($pk  . '.shift_value_' . $s, array(
								'type' => 'text',
								'class' => $record_tag,
								'computeType' => 'cell_subtotal',
								'default' => $shift,
								'maxlength' => 5,
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'jsTable.computeSubtotal(this)'
							)); 
						?>
						</div>
						</div>
					<?php
						}
						echo $this->Form->hidden($pk . '.shift_class_total', array(
							'value' => $value['classes']
						));
					?>
					<div class="table_cell cell_number cell_subtotal"><?php echo $totalShifts; ?></div>
				</div>	
			<?php 
			}
			?>
			</div>
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></div>
				
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

