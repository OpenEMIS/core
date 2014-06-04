<?php if(!empty($data)) { ?>

<tr staff-id="0" subject-id="">
	<td class="table_cell" attr="id"></td>
	<td class="table_cell" attr="name">
		<select class="staff_select full_width form-control" onchange="InstitutionSiteClasses.selectStaff(this)">
			<option value="">-- <?php echo __('Select Staff'); ?> --</option>
			<?php foreach($data as $staff) {
			$obj = $staff['Staff'];
			$fullname = $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name'];
			$option = '<option value="%d" id="%s" name="%s">%s - %s</option>';
			echo sprintf($option, $obj['id'], $obj['identification_no'], $fullname, $obj['identification_no'], $fullname);
			} ?>
		</select>
	</td>
	<td class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteStaff(this)', 'onDelete' => false)); ?>
	</td>
</tr>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No staff available.'); ?></span>

<?php } ?>