<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo __('List of Classes'); ?></span>
		<?php
		if($_add_class) {
			echo $this->Html->link(__('Add'), array('action' => 'classesAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesList'),
		'inputDefaults' => array('label' => false, 'div' => false)
	)); 
	?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'default' => $selectedYear,
				'onchange' => 'InstitutionSiteClasses.navigate()'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width allow_hover" action="InstitutionSites/classesView/">
		<div class="table_head">
			<div class="table_cell cell_class"><?php echo __('Class'); ?></div>
			<div class="table_cell"><?php echo __('Grade'); ?></div>
			<div class="table_cell cell_gender"><?php echo __('Male'); ?></div>
			<div class="table_cell cell_gender"><?php echo __('Female'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $id => $obj) { $i=0; ?>
			<div class="table_row" row-id="<?php echo $id; ?>">
				<div class="table_cell"><?php echo $obj['name']; ?></div>
				
				<div class="table_cell">
					<?php foreach($obj['grades'] as $gradeId => $name) { $i++; ?>
					<div class="table_cell_row <?php echo $i==sizeof($obj['grades']) ? 'last' : ''; ?>"><?php echo $name; ?></div>
					<?php } ?>
				</div>
				
				<div class="table_cell cell_number"><?php echo $obj['gender']['M']; ?></div>
				<div class="table_cell cell_number"><?php echo $obj['gender']['F']; ?></div>
			</div>
			<?php } // end for (multigrade) ?>
		</div>
	</div>
</div>
