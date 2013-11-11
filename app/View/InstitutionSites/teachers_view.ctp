<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teacher" class="content_wrapper">	
	<h1>
		<span><?php echo __('Teacher Information'); ?></span>
		<?php 
		$obj = $data['Teacher'];
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'teachersEdit', $obj['id']), array('class' => 'divider'));
		}
		if($_accessControl->check($this->params['controller'], 'teachersCustFieldYrView')) {
			echo $this->Html->link(__('Academic'), array('action' => 'teachersCustFieldYrView', $obj['id']), array('class' => 'divider'));
		}
		if($_accessControl->check($this->params['controller'], 'teachersAttendance')) {
			echo $this->Html->link(__('Attendance'), array('action' => 'teachersAttendance'), array('class' => 'divider'));
		}
		if($_accessControl->check($this->params['controller'], 'teachersBehaviour')) {
			echo $this->Html->link(__('Behaviour'), array('action' => 'teachersBehaviour', $obj['id']), array('class' => 'divider'));
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
				if($_accessControl->check('Teachers', 'view')) {
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
	
	<fieldset class="section_break">
		<legend><?php echo __('Employment'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Position Number'); ?></div>
				<div class="table_cell"><?php echo __('Position'); ?></div>
				<div class="table_cell" style="width: 80px"><?php echo __('From'); ?></div>
				<div class="table_cell" style="width: 80px"><?php echo __('To'); ?></div>
				<div class="table_cell" style="width: 60px"><?php echo __('Hours'); ?></div>
				<div class="table_cell" style="width: 70px"><?php echo __('Salary'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($positions as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['InstitutionSiteTeacher']['position_no']; ?></div>
					<div class="table_cell"><?php echo $obj['TeacherCategory']['name']; ?></div>
					<div class="table_cell center"><?php echo $this->Utility->formatDate($obj['InstitutionSiteTeacher']['start_date']); ?></div>
					<div class="table_cell center">
						<?php
						$endDate = $obj['InstitutionSiteTeacher']['end_date'];
						echo is_null($endDate) ? __('Current') : $this->Utility->formatDate($endDate);
						?>
					</div>
					<div class="table_cell center"><?php echo $obj['InstitutionSiteTeacher']['no_of_hours']; ?></div>
					<div class="table_cell cell_number"><?php echo $obj['InstitutionSiteTeacher']['salary']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Classes'); ?></legend>
		<div class="table full_width" style="margin-top: 5px;">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Class'); ?></div>
				<div class="table_cell" style="width: 400px;"><?php echo __('Education Level'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($classes as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['InstitutionSiteClass']['name']; ?></div>
					<div class="table_cell"><?php echo $obj['EducationLevel']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<?php echo $this->Form->end(); ?>
</div>
