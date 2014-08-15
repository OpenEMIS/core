<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Student Information'));

$obj = $data['Student'];

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'studentsView', $obj['id']), array('class' => 'divider'));
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
				echo $this->Html->link($obj['identification_no'], array('controller' => 'Students', 'action' => 'viewStudent', $obj['id']), array('class' => 'link_back'));
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

<?php
echo $this->Form->create('InstitutionSiteStudent', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsEdit', $obj['id'])
));
$fieldName = 'data[InstitutionSiteStudent][%s][%s]';
?>
<fieldset class="section_break">
	<legend><?php echo __('Programmes'); ?></legend>
	<table class="table table-striped table-hover table-bordered" style="margin-top: 10px;">
		<thead>
			<tr>
				<th style="width: 200px;"><?php echo __('Programme'); ?></th>
				<th><?php echo __('Period'); ?></th>
				<th style="width: 100px;"><?php echo __('Status'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			$i = 0;
			$fieldName = 'data[InstitutionSiteStudent][%s][%s]';
			foreach ($details as $detail):
				echo $this->Form->hidden($i . '.id', array('value' => $detail['InstitutionSiteStudent']['id']));
				?>
				<tr>
					<td><?php echo $detail['EducationProgramme']['name']; ?></td>
					<td>
						<div class="table_cell_row">
							<div class="col-md-3"><?php echo __('From'); ?></div>
							<?php
							echo $this->Utility->getDatePickerNew($this->Form, $i . 'start_date', array(
								'name' => sprintf($fieldName, $i, 'start_date'),
								'value' => $detail['InstitutionSiteStudent']['start_date'],
								'endDateValidation' => $i . 'end_date'
							));
							?>
						</div>
						<div class="table_cell_row">
							<div class="col-md-3"><?php echo __('To'); ?></div>
							<?php
							echo $this->Utility->getDatePickerNew($this->Form, $i . 'end_date', array(
								'name' => sprintf($fieldName, $i, 'end_date'),
								'value' => $detail['InstitutionSiteStudent']['end_date'],
								'endDateValidation' => $i . 'end_date',
								'yearAdjust' => 1
							));
							?>
						</div>
					</td>
					<td class="table_cell center"><?php echo $this->Form->input($i . '.student_status_id', array('options' => $statusOptions, 'class' => '', 'value' => $detail['StudentStatus']['id'])); ?></td>
				</tr>
				<?php
				$i++;
			endforeach;
			?>
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

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsView', $obj['id']), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>
