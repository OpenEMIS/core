<?php if(!empty($data)) { ?>

<div class="table_row" student-id="0">
	<div class="table_cell" attr="id"></div>
	<div class="table_cell" attr="name">
		<select class="student_select" onchange="InstitutionSiteClasses.selectStudent(this)">
			<option value="">-- <?php echo __('Select Student'); ?> --</option>
			<?php foreach($data as $student) {
			$obj = $student['Student'];
			$fullname = $obj['first_name'] . ' ' . $obj['last_name'];
			$gender = $obj['gender']==='M' ? __('Male') : __('Female');
			$option = '<option value="%d" id="%s" gender="%s" name="%s">%s - %s</option>';
			echo sprintf($option, $obj['id'], $obj['identification_no'], $gender, $fullname, $obj['identification_no'], $fullname);
			} ?>
		</select>
	</div>
	<div class="table_cell" attr="gender"></div>
	<div class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteStudent(this)', 'onDelete' => false)); ?>
	</div>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No students available.'); ?></span>

<?php } ?>