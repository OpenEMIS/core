<?php if(!empty($subjects)) { ?>

<tr subject-id="0">
    <td class="table_cell" attr="code"></td>
	<td class="table_cell" attr="name">
		<select class="subject_select full_width form-control" onchange="InstitutionSiteClasses.selectSubject(this)">
			<option value="">-- <?php echo __('Select Subject'); ?> --</option>
			<?php foreach($subjects as $subject) {
			$obj = $subject;
			echo '<option value="'.$obj['id'].'" id="'.$obj['id'].'" name="'.$obj['code'] . ' - ' . $obj['name'] . ' - ' . $obj['grade'].'">'.$obj['code'] . ' - ' . $obj['name'] . ' - ' . $obj['grade'].'</option>';
			} ?>
		</select>
	</td>
	<td class="table_cell" attr="grade"></td>
	<td class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteSubject(this)', 'onDelete' => false)); ?>
	</td>
</tr>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No subjects available.'); ?></span>

<?php } ?>