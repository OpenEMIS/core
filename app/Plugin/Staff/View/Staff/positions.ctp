<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'employment');
$this->assign('contentHeader', $header);
?>

<style type="text/css">
	.cell_year { width: 100px; }
	.cell_salary { width: 100px; }
</style>

<?php $this->start('contentBody'); ?>
<?php foreach ($data as $key => $classes) { ?>
	<fieldset class="section_group">
		<legend><?php echo $key; ?></legend>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="table_cell"><?php echo __('Position'); ?></th>
						<th class="table_cell cell_year"><?php echo __('From'); ?></th>
						<th class="table_cell cell_year"><?php echo __('To'); ?></th>
						<th class="table_cell cell_salary"><?php echo __('Status'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php foreach ($classes as $class) { ?>
						<tr class="table_row">
							<td class="table_cell">
								<div class="table_cell_row">Number: <?php echo $class['InstitutionSitePosition']['position_no']; ?></div>
								<div class="table_cell_row">Type: <?php echo $class['StaffType']['name']; ?></div>
								<div class="table_cell_row">Title: <?php echo $class['StaffPositionTitle']['name']; ?></div>
								<div class="table_cell_row">Grade: <?php echo $class['StaffPositionGrade']['name']; ?></div>
							</td>
							<td class="table_cell center"><?php echo $class['InstitutionSiteStaff']['start_date']; ?></td>
							<td class="table_cell center"><?php echo (empty($class['InstitutionSiteStaff']['end_date'])) ? 'Current' : $class['InstitutionSiteStaff']['end_date']; ?></td>
							<td class="table_cell center"><?php echo $class['StaffStatus']['name']; ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</fieldset>
<?php } ?>
<?php $this->end(); ?>
