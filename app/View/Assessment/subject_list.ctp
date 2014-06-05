<?php
$i = 0;
$fieldName = 'data[AssessmentItem][%d][%s]';

if(!empty($data)) {
	foreach($data as $item) {
?>

	<div class="table_row inactive">
		<?php
		echo $this->Form->hidden('education_grade_subject_id', array(
			'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
			'value' => $item['id']
		));
		echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
		echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
		?>
		<div class="table_cell">
			<input type="checkbox" name="<?php echo sprintf($fieldName, $i, 'visible'); ?>" value="1" autocomplete="off" onChange="jsList.activate(this, '.table_row')" />
		</div>
		<div class="table_cell"><?php echo $item['code']; ?></div>
		<div class="table_cell"><?php echo $item['name']; ?></div>
		<div class="table_cell">
			<div class="input_wrapper">
			<?php 
				echo $this->Form->input('min', array(
					'label' => false,
					'div' => false,
					'name' => sprintf($fieldName, $i, 'min'),
					'value' => 50,
					'maxlength' => 4,
					'onkeypress' => 'return utility.integerCheck(event)'
				));
			?>
			</div>
		</div>
		<div class="table_cell">
			<div class="input_wrapper">
			<?php 
				echo $this->Form->input('max', array(
					'label' => false,
					'div' => false,
					'name' => sprintf($fieldName, $i++, 'max'),
					'value' => 100,
					'maxlength' => 4,
					'onkeypress' => 'return utility.integerCheck(event)'
				));
			?>
			</div>
		</div>
	</div>
	
<?php
	}
} else {
?>
	<span class="alert" type="<?php echo $this->Utility->alertType['warn']; ?>"><?php echo __('No subject available in this grade.'); ?></span>
<?php } ?>