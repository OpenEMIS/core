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
			$totalShifts = array();

			foreach($singleGradeData as $name => $programme) { 
				foreach($programme['education_grades'] as $gradeId => $grade) {
					//$totalShifts[$grade['shift_id']] += $grade['value'];
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
					for($s=1;$s<=intval($no_of_shifts);$s++){
						$value = null;
						if(isset($grade['shift_' . $s])){
							$value = $grade['shift_' . $s];
						}
						echo '<div class="table_cell cell_number '. $record_tag . '">' . $value . '</div>';
					}?>
					
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
					for($i=1;$i<=intval($no_of_shifts);$i++){
						echo '<div class="table_cell cell_value cell_number">' .  '0</div>';
					}
				?>
			</div>
		</div>
	</fieldset>

	
	<?php } ?>
</div>