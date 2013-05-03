<?php
$total = 0;
$count = 0;

if(!empty($enrolment)) { ?>

<div class="table_body">
	<?php
	foreach($enrolment as $record) {
		$count++;
		$total += $record['male'] + $record['female'];
		$record_tag="";
		switch ($record['source']) {
			case 1:
				$record_tag.="row_external";break;
			case 2:
				$record_tag.="row_estimate";break;
		}
	?>
	
	<div class="table_row <?php echo $count%2==0 ? 'even' : ''; ?>" record-id="<?php echo $record['id']; ?>">
		<div class="table_cell">
			<div class="input_wrapper">
			<?php echo $this->Form->input('age', array(
					'id' => 'CensusStudentAge',
					'class' => $record_tag,
					'label' => false,
					'div' => false,
					'value' => $record['age'],
					'defaultValue' => $record['age'],
					'maxlength' => 2,
					'autocomplete' => 'off',
					'onkeyup' => 'CensusEnrolment.checkEdited()'
				)
			);
			?>
			</div>
		</div>
		<div class="table_cell">
			<div class="input_wrapper">
			<?php echo $this->Form->input('male', array(
					'id' => 'CensusStudentMale',
					'class' => $record_tag,
					'label' => false,
					'div' => false,
					'value' => $record['male'],
					'defaultValue' => $record['male'],
					'maxlength' => 10, 
					'autocomplete' => 'off',
					'onkeyup' => 'CensusEnrolment.computeSubtotal(this); CensusEnrolment.checkEdited()'
				)
			);
			?>
			</div>
		</div>
		<div class="table_cell">
			<div class="input_wrapper">
			<?php echo $this->Form->input('female', array(
					'id' => 'CensusStudentFemale',
					'class' => $record_tag,
					'label' => false,
					'div' => false,
					'value' => $record['female'],
					'defaultValue' => $record['female'],
					'maxlength' => 10,
					'autocomplete' => 'off',
					'onkeyup' => 'CensusEnrolment.computeSubtotal(this); CensusEnrolment.checkEdited()'
				)
			);
			?>
			</div>
		</div>
		<div class="table_cell cell_total cell_number"><?php echo $record['male'] + $record['female']; ?></div>
		<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this)"></span></div>
	</div>
	<?php } ?>
</div>
<?php } ?>

<div class="table_foot">
	<div class="table_cell"></div>
	<div class="table_cell"></div>
	<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
	<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
	<div class="table_cell"></div>
</div>