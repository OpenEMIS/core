<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_teachers', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teachersEdit" class="content_wrapper">
	<h1>
		<span><?php echo __('Teacher Information'); ?></span>
		<?php 
		$obj = $data['Teacher'];
		if($_edit) {
			echo $this->Html->link(__('View'), array('action' => 'teachersView', $obj['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_break" id="general">
		<legend><?php echo __('General'); ?></legend>
		<?php
		    $path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null'))? "/Teachers/fetchImage/{$obj['id']}":"/Teachers/img/default_teacher_profile.jpg";
		    echo $this->Html->image($path, array('class' => 'profile_image', 'alt' => '90x115'));
		?>
		<div class="row">
			<div class="label"><?php echo __('Identification No.'); ?></div>
			<div class="value">
				<?php
				if($_view_details) {
					echo $this->Html->link($obj['identification_no'], array('controller' => 'Teachers', 'action' => 'viewTeacher', $obj['id']), array('class' => 'link_back'));
				} else {
					echo $obj['identification_no'];
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
	</fieldset>

	<?php
	echo $this->Form->create('InstitutionSiteTeacher', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'teachersEdit', $obj['id'])
	));
	$fieldName = 'data[InstitutionSiteTeacher][%s][%s]';
	?>
	<fieldset class="section_break" id="employment">
		<legend><?php echo __('Employment'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell" style="width: 150px;"><?php echo __('Position'); ?></div>
				<div class="table_cell" style="width: 280px;"><?php echo __('Period'); ?></div>
				<div class="table_cell"><?php echo __('Hours'); ?></div>
				<div class="table_cell"><?php echo __('Salary'); ?></div>
				<div class="table_cell cell_icon_action"></div>
			</div>
			
			<div class="table_body">
				<?php foreach($positions as $i => $pos) { ?>
				<div class="table_row" row-id="<?php echo $i; ?>">
					<?php
					echo $this->Form->hidden($i.'.id', array('class' => 'key', 'value' => $pos['InstitutionSiteTeacher']['id']));
					?>
					<div class="table_cell">
						<div class="table_cell_row"><?php echo $pos['TeacherCategory']['name']; ?></div>
					</div>
					<div class="table_cell">
						<div class="table_cell_row">
							<div class="label"><?php echo __('From'); ?></div>
							<?php 
							echo $this->Utility->getDatePicker($this->Form, $i . 'start_date', 
								array(
									'name' => sprintf($fieldName, $i, 'start_date'),
									'value' => $pos['InstitutionSiteTeacher']['start_date'],
									'endDateValidation' => $i . 'end_date'
								));
							?>
						</div>
						<div class="table_cell_row">
							<div class="label"><?php echo __('To'); ?></div>
							<?php 
							echo $this->Utility->getDatePicker($this->Form, $i . 'end_date', 
								array(
									'name' => sprintf($fieldName, $i, 'end_date'),
									'emptySelect' => true,
									'value' => $pos['InstitutionSiteTeacher']['end_date'],
									'endDateValidation' => $i . 'end_date',
									'yearAdjust' => 1
								));
							?>
						</div>
					</div>
					<div class="table_cell">
						<div class="table_cell_row input_wrapper">
						<?php
						echo $this->Form->input($i . '.no_of_hours', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'maxlength' => 3,
							'name' => sprintf($fieldName, $i, 'no_of_hours'),
							'value' => $pos['InstitutionSiteTeacher']['no_of_hours'],
							'onkeypress' => 'return utility.floatCheck(event)'
						));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="table_cell_row input_wrapper">
						<?php
						echo $this->Form->input($i . '.salary', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'maxlength' => 10,
							'name' => sprintf($fieldName, $i, 'salary'),
							'value' => $pos['InstitutionSiteTeacher']['salary'],
							'onkeypress' => 'return utility.floatCheck(event)'
						));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="table_cell_row"><span class="icon_delete" onclick="InstitutionSiteTeachers.deletePosition(this);"></span></div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php if($_add) { ?>
		<div class="row" style="margin-left: 0;">
			<a class="void icon_plus" url="InstitutionSites/teachersAddPosition/"><?php echo __('Add').' '.__('Position'); ?></a>
		</div>
		<?php } ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Classes'); ?></legend>
		<div class="table full_width" style="margin-top: 5px;">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Class'); ?></div>
				<div class="table_cell" style="width: 400px;"><?php echo __('Education Level'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($classes as $cls) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $cls['InstitutionSiteClass']['name']; ?></div>
					<div class="table_cell"><?php echo $cls['EducationLevel']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'teachersView', $obj['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
