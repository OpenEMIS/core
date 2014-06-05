<?php if(!empty($data)) { ?>

<tr student-id="0">
	<td class="table_cell" attr="id"></td>
	<td class="table_cell" attr="name">
		<select class="full_width form-control" onchange="InstitutionSiteClasses.selectStudent(this)">
			<option value="">-- <?php echo __('Select Student'); ?> --</option>
			<?php foreach($data as $student) {
			$obj = $student['Student'];
			$fullname = $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name'];
			$option = '<option value="%d" id="%s" name="%s">%s - %s</option>';
			echo sprintf($option, $obj['id'], $obj['identification_no'], $fullname, $obj['identification_no'], $fullname);
			} ?>
		</select>
	</td>
	<td class="table_cell" attr="category">
		<?php
		echo $this->Form->input('student_category_id', array(
			'label' => false,
			'td' => false,
			'class' => 'full_width form-control',
			'empty' => '-- ' . __('Select Category') . ' --',
			'options' => $categoryOptions,
			'onchange' => 'InstitutionSiteClasses.selectStudent(this)'
		));
		?>
	</td>
	<td class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteStudent(this)', 'onDelete' => false)); ?>
	</td>
</tr>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No students available.'); ?></span>

<?php } ?>