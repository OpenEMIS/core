<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Student Information'));

$obj = $data['Student'];

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'studentsEdit'), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'studentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
/*
if ($_accessControl->check($this->params['controller'], 'studentsCustFieldYrView')) {
	echo $this->Html->link(__('Academic'), array('action' => 'studentsCustFieldYrView', $obj['id']), array('class' => 'divider'));
}
if ($_accessControl->check($this->params['controller'], 'studentsBehaviour')) {
	echo $this->Html->link(__('Behaviour'), array('action' => 'studentsBehaviour', $obj['id']), array('class' => 'divider'));
}*/
$this->end();

$this->start('contentBody');
?>

<fieldset class="section_break dataDisplay" id="general">
	<legend><?php echo __('General'); ?></legend>
	<?php
		$src = $this->Image->getBase64($obj['photo_name'], $obj['photo_content']);
		if(is_null($src)) {
			$src = $this->webroot . 'Students/img/default_student_profile.jpg';
		}
	?>
	<img src="<?php echo $src ?>" class="profile-image" alt="90x115" />
	<div class="row">
		<div class="col-md-3"><?php echo __('OpenEMIS ID'); ?></div>
		<div class="col-md-6">
			<?php
			if ($_view_details) {
				echo $this->Html->link($obj['identification_no'], array('controller' => 'Students', 'action' => 'view', $obj['id']), array('class' => 'link_back'));
			} else {
				echo $obj['identification_no'];
			}
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-6"><?php echo $obj['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Middle Name'); ?></div>
		<div class="col-md-6"><?php echo $obj['middle_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-6"><?php echo $obj['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Preferred Name'); ?></div>
		<div class="col-md-6"><?php echo $obj['preferred_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Gender'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
	</div>

	<div class="row">
		<div class="col-md-3"><?php echo __('Date of Birth'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Date of Death'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_of_death']); ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Programmes'); ?></legend>
	<table class="table table-striped table-hover table-bordered" style="margin-top: 10px;">
		<thead>
			<tr>
				<th style="width: 220px;"><?php echo __('Programme'); ?></th>
				<th><?php echo __('From'); ?></th>
				<th><?php echo __('To'); ?></th>
				<th style="width: 100px;"><?php echo __('Status'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($details as $detail) { ?>
				<tr>
					<td><?php echo $detail['EducationProgramme']['name']; ?></td>
					<td class="center"><?php echo $this->Utility->formatDate($detail['InstitutionSiteStudent']['start_date']); ?></td>
					<td class="center"><?php echo $this->Utility->formatDate($detail['InstitutionSiteStudent']['end_date']); ?></td>
					<td class="center"><?php echo $detail['StudentStatus']['name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Classes'); ?></legend>
	<table class="table table-striped table-hover table-bordered" style="margin-top: 10px;">
		<thead>
			<tr>
				<th style="width: 80px;"><?php echo __('Year'); ?></th>
				<th style="width: 120px;"><?php echo __('Class'); ?></th>
				<th><?php echo __('Programme'); ?></th>
				<th style="width: 120px;"><?php echo __('Grade'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($classes as $class) { ?>
				<tr>
					<td><?php echo $class['SchoolYear']['name']; ?></td>
					<td><?php echo $class['InstitutionSiteClass']['name']; ?></td>
					<td><?php echo $class['EducationCycle']['name'] . ' - ' . $class['EducationProgramme']['name']; ?></td>
					<td><?php echo $class['EducationGrade']['name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</fieldset>
<?php /*
<fieldset class="section_break">
	<legend><?php echo __('Assessments'); ?></legend>
	<?php foreach ($results as $gradeId => $result) { ?>
		<fieldset class="section_group" style="margin-top: 15px;">
			<legend><?php echo $result['name']; ?></legend>
			<?php foreach ($result['assessments'] as $id => $assessment) { ?>
				<fieldset class="section_break">
					<legend><?php echo $assessment['name']; ?></legend>
					<table class="table table-striped table-hover table-bordered">
						<thead>
							<tr>
								<th><?php echo __('Code'); ?></th>
								<th><?php echo __('Subject'); ?></th>
								<th><?php echo __('Marks'); ?></th>
								<th><?php echo __('Grading'); ?></th>
							</tr>

						</thead>

						<tbody>
							<?php foreach ($assessment['subjects'] as $subject) { ?>
								<tr>
									<td><?php echo $subject['code']; ?></td>
									<td><?php echo $subject['name']; ?></td>
									<td><?php echo $subject['marks']; ?></td>
									<td><?php echo $subject['grading']; ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</fieldset>
			<?php } ?>
		</fieldset>
	<?php } ?>
</fieldset> */ ?>
<?php $this->end(); ?>