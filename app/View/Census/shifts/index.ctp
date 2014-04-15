<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="shifts" class="content_wrapper">
	<h1>
		<span><?php echo __('Shifts'); ?></span>
		<?php
		if($_edit && $isEditable) {
			echo $this->Html->link(__('Edit'), array('action' => 'shiftsEdit', $selectedYear), array('class' => 'divider'));
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
				'options' => $yearList,
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
				for($i=1;$i<=intval($noOfShifts);$i++){
					echo '<div class="table_cell cell_shifts">' . __('Shift')  . ' ' . $i . '</div>';
				}?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
			<?php 
			$totalClasses = 0;
			foreach($singleGradeData as $name => $value) { 
				$record_tag="";
				foreach ($source_type as $k => $v) {
					if ($value['source']==$v) {
						$record_tag = "row_" . $k;
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
					for($s=1;$s<=intval($noOfShifts);$s++){
						$shift = null;
						if(isset($value['shift_' . $s])){
							$shift = $value['shift_' . $s];
							$totalShifts += $shift;
						}
						echo '<div class="table_cell cell_number '. $record_tag . '">' . $shift . '</div>';
					}?>
					
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
	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Classes'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Classes'); ?></div>
				<?php 
				for($i=1;$i<=intval($noOfShifts);$i++){
					echo '<div class="table_cell cell_shifts">' . __('Shift')  . ' ' . $i . '</div>';
				}?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<?php 
		
			$totalClasses = 0;
			if(!empty($multiGradeData)) {
			?>
			<div class="table_body">
			<?php
		    foreach($multiGradeData as $name => $value) { 
				$record_tag="";
				foreach ($source_type as $k => $v) {
					if ($value['source']==$v) {
						$record_tag = "row_" . $k;
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
					for($s=1;$s<=intval($noOfShifts);$s++){
						$shift = null;
						if(isset($value['shift_' . $s])){
							$shift = $value['shift_' . $s];
							$totalShifts += $shift;
						}
						echo '<div class="table_cell cell_number '. $record_tag . '">' . $shift . '</div>';
					}?>
					
					<div class="table_cell cell_number cell_subtotal"><?php echo $totalShifts; ?></div>
				</div>
				
			<?php 
			}
			?>
			</div>
			<?php } ?>
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></div>
			</div>
		</div>
	</fieldset>
	
	<?php } ?>
</div>