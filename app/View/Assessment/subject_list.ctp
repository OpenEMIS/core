<?php
$i = 0;
$fieldName = 'data[AssessmentItem][%d][%s]';

if(!empty($data)) {
	foreach($data as $item) {
?>

	<tr class="table_row inactive">
		<?php
		echo $this->Form->hidden('education_grade_subject_id', array(
			'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
			'value' => $item['id']
		));
		echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
		echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
		?>
		<td class="">
			<input type="checkbox" name="<?php echo sprintf($fieldName, $i, 'visible'); ?>" value="1" autocomplete="off" onChange="jsList.activate(this, '.table_row')" />
		</td>
		<td class=""><?php echo $item['code']; ?></td>
		<td class=""><?php echo $item['name']; ?></td>
		<td class="input">
			<?php 
				echo $this->Form->input('min', array(
					'label' => false,
					'td' => false,
					'class' => 'form-control',
					'name' => sprintf($fieldName, $i, 'min'),
					'value' => 50,
					'maxlength' => 4,
					'onkeypress' => 'return utility.integerCheck(event)'
				));
			?>
		</td>
		<td class="input">
			<?php 
				echo $this->Form->input('max', array(
					'label' => false,
					'td' => false,
					'class' => 'form-control',
					'name' => sprintf($fieldName, $i++, 'max'),
					'value' => 100,
					'maxlength' => 4,
					'onkeypress' => 'return utility.integerCheck(event)'
				));
			?>
		</td>
	</tr>
	
<?php
	}
} else {
?>

<tr>
	<td colspan="5">
		<span class="alert" type="<?php echo $this->Utility->alertType['warn']; ?>"><?php echo __('No subject available in this grade.'); ?></span>
	</td>
</tr>
<?php } ?>