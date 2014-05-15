<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
?>

<style type="text/css">
.cell_year { width: 100px; }
.cell_salary { width: 100px; }
</style>

<?php echo $this->element('breadcrumb'); ?>

<div id="employment" class="content_wrapper">
    <h1>
        <span><?php echo __('Positions'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
	
    <?php foreach($data as $key => $classes){ ?>
	<fieldset class="section_group">
		<legend><?php echo $key; ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Position'); ?></div>
				<div class="table_cell cell_year"><?php echo __('From'); ?></div>
				<div class="table_cell cell_year"><?php echo __('To'); ?></div>
				<div class="table_cell cell_salary"><?php echo __('Status'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($classes as $class){ ?>
				<div class="table_row">
					<div class="table_cell">
                                                <div class="table_cell_row">Number: <?php echo $class['InstitutionSiteStaff']['position_no'];?></div>
						<div class="table_cell_row">Type: <?php echo $class['StaffCategory']['name']; ?></div>
						<div class="table_cell_row">Title: <?php echo $class['StaffPositionTitle']['name']; ?></div>
                                                <div class="table_cell_row">Grade: <?php echo $class['StaffPositionGrade']['name']; ?></div>
                                                <div class="table_cell_row">Step: <?php echo $class['StaffPositionStep']['name']; ?></div>
					</div>
					<div class="table_cell center"><?php echo $class['InstitutionSiteStaff']['start_date']; ?></div>
					<div class="table_cell center"><?php echo (empty($class['InstitutionSiteStaff']['end_date']))? 'Current':$class['InstitutionSiteStaff']['end_date']; ?></div>
					<div class="table_cell center"><?php echo $class['StaffStatus']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
    <?php } ?>
</div>