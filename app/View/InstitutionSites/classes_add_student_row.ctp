<?php if(!empty($data)) { ?>

<div class="table_row" student-id="0">
	<div class="table_cell" attr="id"></div>
	<div class="table_cell" attr="name">
		<select class="full_width" onchange="InstitutionSiteClasses.selectStudent(this)">
			<option value="">-- <?php echo __('Select Student'); ?> --</option>
			<?php foreach($data as $student) {
			$obj = $student['Student'];
			$fullname = $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name'];
			$option = '<option value="%d" id="%s" name="%s">%s - %s</option>';
			echo sprintf($option, $obj['id'], $obj['identification_no'], $fullname, $obj['identification_no'], $fullname);
			} ?>
		</select>
	</div>
	<div class="table_cell" attr="category">
		<?php
		echo $this->Form->input('student_category_id', array(
			'label' => false,
			'div' => false,
			'class' => 'full_width',
			'empty' => '-- ' . __('Select Category') . ' --',
			'options' => $categoryOptions,
			'onchange' => 'InstitutionSiteClasses.selectStudent(this)'
		));
		?>
	</div>
	<div class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteStudent(this)', 'onDelete' => false)); ?>
	</div>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No students available.'); ?></span>

<?php } ?>