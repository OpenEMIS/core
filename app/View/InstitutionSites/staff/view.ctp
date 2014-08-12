<?php
echo $this->Html->css('Staff.staff', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$obj = $data['Staff'];
echo $this->Html->link(__('Back'), array('action' => 'staff'), array('class' => 'divider'));
if($_accessControl->check($this->params['controller'], 'staffCustFieldYrView')) {
	echo $this->Html->link(__('Academic'), array('action' => 'staffCustFieldYrView', $obj['id']), array('class' => 'divider'));
}
/*
if($_accessControl->check($this->params['controller'], 'staffAttendance')) {
	echo $this->Html->link(__('Attendance'), array('action' => 'staffAttendance'), array('class' => 'divider'));
}
if($_accessControl->check($this->params['controller'], 'staffBehaviour')) {
	echo $this->Html->link(__('Behaviour'), array('action' => 'staffsBehaviour', $obj['id']), array('class' => 'divider'));
}*/
$this->end();

$this->start('contentBody');
?>
<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<?php
		$src = $this->Image->getBase64($obj['photo_name'], $obj['photo_content']);
		if(is_null($src)) {
			$src = $this->webroot . 'Staff/img/default_staff_profile.jpg';
		}
	?>
	<img src="<?php echo $src ?>" class="profile-image" alt="90x115" />
	<div class="row">
		<div class="col-md-3"><?php echo __('OpenEMIS ID'); ?></div>
		<div class="col-md-6">
			<?php
			if ($_accessControl->check('Staff', 'view')) {
				echo $this->Html->link($obj['identification_no'], array('controller' => 'Staff', 'action' => 'view', $obj['id']), array('class' => 'link_back'));
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
		<div class="col-md-3"><?php echo __('Date Of Birth'); ?></div>
		<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Location'); ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th style="width: 150px;"><?php echo __('Position'); ?></th>
					<th style="width: 220px;"><?php echo __('Details'); ?></th>
					<th><?php echo __('FTE'); ?></th>
					<th><?php echo __('Status'); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($positions as $obj) { ?>
				<tr>
					<td class="table_cell">
						<div class="table_cell_row">Number: <?php echo $obj['InstitutionSitePosition']['position_no']; ?></div>
						<div class="table_cell_row">Type: <?php echo $obj['StaffType']['name']; ?></div>
						<div class="table_cell_row">Title: <?php echo $this->Html->link($obj['StaffPositionTitle']['name'], array('action' => 'positionsHistory', $obj['InstitutionSitePosition']['id']), array('escape' => false)); ?></div>
						<div class="table_cell_row">Grade: <?php echo $obj['StaffPositionGrade']['name']; ?></div>
					</td>
					<td class="table_cell view">
						<div class="table_cell_row">
							<div class="left" style="width: 50px;"><b><?php echo __('From'); ?></b></div>
							<div class="left"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStaff']['start_date']); ?></div>
						</div>
						<div class="table_cell_row">
							<div class="left" style="width: 50px;"><b><?php echo __('To'); ?></b></div>
							<div class="left">
								<?php
								$endDate = $obj['InstitutionSiteStaff']['end_date'];
								echo empty($endDate) ? __('Current') : $this->Utility->formatDate($endDate);
								?>
							</div>
						</div>
					</td>
					<td class="center"><?php echo ($obj['InstitutionSiteStaff']['FTE']*100); ?></td>
					<td class="center"><?php echo $obj['StaffStatus']['name']; ?></td>
				</tr>
			<?php } ?>
			</tbody>
			</table>
	</div>
</fieldset>

<?php
$this->end();
?>
