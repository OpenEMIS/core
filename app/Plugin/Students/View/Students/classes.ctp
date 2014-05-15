<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
?>

<style type="text/css">
.cell_class { width: 120px; }
.cell_year { width: 100px; }
.cell_grade { width: 100px; }
</style>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo __('Classes'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

    <?php foreach($data as $key => $classes){ ?>
		<fieldset class="section_group">
			<legend><?php echo $key; ?></legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_year"><?php echo __('Years'); ?></div>
					<div class="table_cell cell_class"><?php echo __('Classes'); ?></div>
					<div class="table_cell"><?php echo __('Programme'); ?></div>
					<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				</div>
				<div class="table_body">
					<?php foreach($classes as $class){ ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $class['SchoolYear']['name']; ?></div>
						<div class="table_cell"><?php echo $class['InstitutionSiteClass']['name']; ?></div>
						<div class="table_cell"><?php echo $class['EducationProgramme']['name']; ?></div>
						<div class="table_cell"><?php echo $class['EducationGrade']['name']; ?></div>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset>
    <?php } ?>
</div>