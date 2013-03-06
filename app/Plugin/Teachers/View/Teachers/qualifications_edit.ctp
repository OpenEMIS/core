<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/qualifications', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<script>

</script>
<div id="qualification" class="content_wrapper edit">

	<h1>
		<span><?php echo __('Qualifications'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'qualifications'), array('class' => 'divider')); ?>
	</h1>

		<?php
		echo $this->Form->create('TeacherQualification', array(
				'url' => array('controller' => 'Teachers', 'action' => 'qualificationsEdit'),
				'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
			)
		);
		?>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_date"><?php echo __('Date of Issue'); ?></div>
				<div class="table_cell"><?php echo __('Certificate'); ?></div>
				<div class="table_cell"><?php echo __('Certificate No.'); ?></div>
				<div class="table_cell"><?php echo __('Issued By'); ?></div>
				<div class="table_cell cell_delete">&nbsp;</div>
			</div>
			
			<div class="table_body">
			<?php
			if(count($list) > 0){
				$ctr = 1;
				// pr($list);
				foreach($list as $arrVal){
				   	// echo '<div class="table_row" id="qualifications_row_'.$arrVal['id'].'">';
					// echo '<div class="table_row" data-id="'.$arrVal['id'].'">
				   	echo '<div class="table_row" id="qualifications_row_'.$arrVal['id'].'" data-id="'.$arrVal['id'].'">
				   			<input type="hidden" value="'.$arrVal['id'].'" name="data[TeacherQualification]['.$ctr.'][id]" />
				   			<div class="table_cell">'.$this->Utility->getDatePicker($this->Form, $ctr.']['.'issue_date', array('desc' => true,'value' => $arrVal['issue_date'])) .'</div>';

						echo '<div class="table_cell">
							<select class="full_width" name="data[TeacherQualification]['.$ctr.'][teacher_qualification_certificate_id]">
								<option value="0">--Select--</option>';
								foreach($certificates as $arrCertificates) {
									$selectedCertificate = 0;
									echo '<option value="'.$arrCertificates['TeacherQualificationCertificate']['id'].'" '.($arrCertificates['TeacherQualificationCertificate']['id'] == $arrVal['certificate_id']?' selected="selected" ':"").'>'.$arrCertificates['TeacherQualificationCertificate']['name'].'</option>';
								}
								/*foreach($certificateData as $arrCertificates) {
									echo '<option value="'.$arrCertificates['id'].'" '.($arrCertificates['id'] == $arrVal['certificate_id']?' selected="selected" ':"").'>'.$arrCertificates['name'].'</option>';
								}*/
						echo '</select>
							</div>
						<div class="table_cell">
							<div class="input_wrapper">
								<input class="full_width" type="text" value="'.$arrVal['certificate_no'].'" name="data[TeacherQualification]['.$ctr.'][certificate_no]" />
							</div>
						</div>
						<div class="table_cell"><select class="full_width" name="data[TeacherQualification]['.$ctr.'][teacher_training_institution_id]">
							<option value="0">--Select--</option>
						';

						foreach($institutes as $institute){
							$selected = ($arrVal['institute_id'] === $institute['TeacherQualificationInstitution']['id']) ? "selected" : "";
							echo '<option value="'.$institute['TeacherQualificationInstitution']['id'].'"'.$selected.'>'.$institute['TeacherQualificationInstitution']['name'].'</option>';
						}
					echo '</select>
						</div>
					<div class="table_cell"><span class="icon_delete" title="'.__("Delete").'" onClick="objTeacherQualifications.confirmDeletedlg('.$arrVal['id'].')"></span></div></div>';
				   $ctr++;
				}
			}
			?>
			</div>
		</div>

		<?php if($_add) { ?>
		<div class="row"><a class="void icon_plus link_add"><?php echo __('Add') . ' ' . __('Certifications'); ?></a></div>
		<?php } ?>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return objTeacherQualifications.validateAdd();" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'qualifications'), array('class' => 'btn_cancel btn_left')); ?>
		</div>
	<?php echo $this->Form->end(); ?>

</div>