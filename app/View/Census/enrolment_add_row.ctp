<?php



?>
	
<div class="table_row <?php echo $rowNum%2==1 ? 'even' : '' ?>" record-id="0">
	<div class="table_cell">
		<div class="input_wrapper">
			<?php echo $this->Form->input('age', array(
					'id' => 'CensusStudentAge',
					'label' => false,
					'div' => false,
					'type' => 'text',
					'value' => $age,
					'defaultValue' => $age,
					'maxlength' => 2,
					'autocomplete' => 'off',
					'onkeypress' => 'return utility.integerCheck(event)',
					'onblur' => 'CensusEnrolment.checkExistingAge(this);'
				));
			?>
		</div>
	</div>
	
	<div class="table_cell">
		<div class="input_wrapper">
			<?php echo $this->Form->input('female', array(
					'id' => 'CensusStudentMale',
					'label' => false,
					'div' => false,
					'type' => 'text',
					'value' => 0,
					'defaultValue' => 0,
					'maxlength' => 10, 
					'autocomplete' => 'off',
					'onkeypress' => 'return utility.integerCheck(event)',
					'onkeyup' => 'CensusEnrolment.computeSubtotal(this);'
				));
			?>
		</div>
	</div>
	
	<div class="table_cell">
		<div class="input_wrapper">
			<?php echo $this->Form->input('female', array(
					'id' => 'CensusStudentFemale',
					'label' => false,
					'div' => false,
					'type' => 'text',
					'value' => 0,
					'defaultValue' => 0,
					'maxlength' => 10, 
					'autocomplete' => 'off',
					'onkeypress' => 'return utility.integerCheck(event)',
					'onkeyup' => 'CensusEnrolment.computeSubtotal(this);'
				));
			?>
		</div>
	</div>
	
	<div class="table_cell cell_total cell_number">0</div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __('Delete'); ?>" onclick="CensusEnrolment.removeRow(this)"></span></div>
</div>