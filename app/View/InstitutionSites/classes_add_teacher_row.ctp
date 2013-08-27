<?php if(!empty($data)) { ?>

<div class="table_row" teacher-id="0" subject-id="">
	<div class="table_cell" attr="id"></div>
	<div class="table_cell" attr="name">
		<select class="teacher_select full_width" onchange="InstitutionSiteClasses.selectTeacher(this)">
			<option value="">-- <?php echo __('Select Teacher'); ?> --</option>
			<?php foreach($data as $teacher) {
			$obj = $teacher['Teacher'];
			$fullname = $obj['first_name'] . ' ' . $obj['last_name'];
			$option = '<option value="%d" id="%s" name="%s">%s - %s</option>';
			echo sprintf($option, $obj['id'], $obj['identification_no'], $fullname, $obj['identification_no'], $fullname);
			} ?>
		</select>
	</div>
	<div class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteTeacher(this)', 'onDelete' => false)); ?>
	</div>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No teachers available.'); ?></span>

<?php } ?>