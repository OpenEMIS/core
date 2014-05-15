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
<div id="studentsEdit" class="content_wrapper edit">

	<fieldset class="section_break dataDisplay" id="general">
		<legend><?php echo __('General'); ?></legend>
		<?php
		$path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null')) ? "/Students/fetchImage/{$obj['id']}" : "/Students/img/default_student_profile.jpg";
		echo $this->Html->image($path, array('class' => 'profile_image', 'alt' => '90x115'));
		?>
		<div class="row">
			<div class="label"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="value">
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
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value"><?php echo $obj['middle_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value"><?php echo $obj['preferred_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date of Death'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_death']); ?></div>
		</div>
	</fieldset>
	<?php
	
//	$formOptions = $this->FormUtility->getFormOptions('InstitutionSites/studentsEdit/'.$obj['id']);
//	$labelOptions = $formOptions['inputDefaults']['label'];
//	echo $this->Form->create('InstitutionSiteStudent', $formOptions);
	
	echo $this->Form->create('InstitutionSiteStudent', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsEdit', $obj['id'])
	));
	$fieldName = 'data[InstitutionSiteStudent][%s][%s]';
	?>
	<fieldset class="section_break">
		<legend><?php echo __('Programmes'); ?></legend>
		<table class="table table-striped table-hover table-bordered" style="margin-top: 10px;">
			<thead class="table_head">
				<tr>
					<th class="table_cell" style="width: 200px;"><?php echo __('Programme'); ?></th>
					<th class="table_cell"><?php echo __('Period'); ?></th>
					<th class="table_cell" style="width: 100px;"><?php echo __('Status'); ?></th>
				</tr>
			</thead>

			<tbody class="table_body">
				<?php
				$i = 0;
				$fieldName = 'data[InstitutionSiteStudent][%s][%s]';
				foreach ($details as $detail):
					echo $this->Form->hidden($i . '.id', array('value' => $detail['InstitutionSiteStudent']['id']));
					?>
					<tr class="table_row">
						<td class="table_cell"><?php echo $detail['EducationProgramme']['name']; ?></td>
						<td class="table_cell">
							<div class="table_cell_row">
								<div class="label"><?php echo __('From'); ?></div>
								<?php
								echo $this->Utility->getDatePickerNew($this->Form, $i . 'start_date', array(
									'name' => sprintf($fieldName, $i, 'start_date'),
									'value' => $detail['InstitutionSiteStudent']['start_date'],
									'endDateValidation' => $i . 'end_date'
								));
								?>
							</div>
							<div class="table_cell_row">
								<div class="label"><?php echo __('To'); ?></div>
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
			<thead class="table_head">
				<tr>
					<th class="table_cell" style="width: 80px;"><?php echo __('Year'); ?></th>
					<th class="table_cell" style="width: 120px;"><?php echo __('Class'); ?></th>
					<th class="table_cell"><?php echo __('Programme'); ?></th>
					<th class="table_cell" style="width: 120px;"><?php echo __('Grade'); ?></th>
				</tr>
			</thead>

			<tbody class="table_body">
				<?php foreach ($classes as $class) { ?>
					<tr class="table_row">
						<td class="table_cell"><?php echo $class['SchoolYear']['name']; ?></td>
						<td class="table_cell"><?php echo $class['InstitutionSiteClass']['name']; ?></td>
						<td class="table_cell"><?php echo $class['EducationCycle']['name'] . ' - ' . $class['EducationProgramme']['name']; ?></td>
						<td class="table_cell"><?php echo $class['EducationGrade']['name']; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</fieldset>

	<fieldset class="section_break">
		<legend><?php echo __('Assessments'); ?></legend>
		<?php foreach ($results as $gradeId => $result) : ?>
			<fieldset class="section_group" style="margin-top: 15px;">
				<legend><?php echo $result['name']; ?></legend>
				<?php foreach ($result['assessments'] as $id => $assessment) : ?>
					<fieldset class="section_break">
						<legend><?php echo $assessment['name']; ?></legend>
						<table class="table table-striped table-hover table-bordered">
							<thead class="table_head">
								<tr>
									<th class="table_cell"><?php echo __('Code'); ?></th>
									<th class="table_cell"><?php echo __('Subject'); ?></th>
									<th class="table_cell"><?php echo __('Marks'); ?></th>
									<th class="table_cell"><?php echo __('Grading'); ?></th>
								</tr>
							</thead>

							<tbody class="table_body">
								<?php foreach ($assessment['subjects'] as $subject) : ?>
									<tr class="table_row">
										<td class="table_cell"><?php echo $subject['code']; ?></td>
										<td class="table_cell"><?php echo $subject['name']; ?></td>
										<td class="table_cell"><?php echo $subject['marks']; ?></td>
										<td class="table_cell"><?php echo $subject['grading']; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</fieldset>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>
	</fieldset>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsView', $obj['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>

	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>