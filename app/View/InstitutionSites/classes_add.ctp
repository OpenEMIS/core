<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper add">
    <h1>
        <span><?php echo __('Add Class'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php if($displayContent) { ?>
	<?php 
	echo $this->Form->create('InstitutionSiteClass', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesAdd'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	?>
	
	<div class="row edit">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
		<?php 
		echo $this->Form->input('school_year_id', array(
			'id' => 'SchoolYearId', 
			'options' => $yearOptions,
			'onchange' => 'InstitutionSiteClasses.switchYear()'
		));
		?>
		</div>
	</div>
	
	<div class="row edit">
		<div class="label"><?php echo __('Class'); ?></div>
		<div class="value"><?php echo $this->Form->input('name', array('id' => 'ClassName', 'class' => 'default')); ?></div>
	</div>
    
        <div class="row edit">
		<div class="label"><?php echo __('Seats'); ?></div>
		<div class="value"><?php echo $this->Form->input('no_of_seats', array('id' => 'NoOfSeats', 'class' => 'default')); ?></div>
	</div>
    
        <div class="row edit">
		<div class="label"><?php echo __('Shift'); ?></div>
		<div class="value"><?php echo $this->Form->input('no_of_shifts', array('id' => 'NoOfShifts', 'options' => $shiftOptions, 'class' => 'default')); ?></div>
	</div>
	
	<div class="table full_width" id="grade_list">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Programme'); ?></div>
			<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
			<div class="table_cell cell_delete"></div>
		</div>
		
		<?php
		$gradeModel = 'InstitutionSiteClassGrade';
		?>
		
		<div class="table_body">
			<div class="table_row">
				<div class="table_cell">
					<?php
					echo $this->Form->input('institution_site_programme_id', array(
						'name' => 'institution_site_programme_id',
						'url' => 'InstitutionSites/programmesGradeList',
						'options' => $programmeOptions,
						'default' => $selectedProgramme,
						'onchange' => 'objInstitutionSite.getGradeList(this)'
					));
					?>
				</div>
				<div class="table_cell">
					<?php 
					echo $this->Form->input($gradeModel.'.0.education_grade_id', array(
						'class' => 'grades',
						'options' => $gradeOptions
					)); 
					?>
				</div>
				<div class="table_cell"><?php echo $this->Utility->getDeleteControl(); ?></div>
			</div>
		</div>
	</div>
	
	<div class="row" style="margin-left: 3px;">
		<a class="void icon_plus" url="InstitutionSites/classesAddGrade"><?php echo __('Add').' '.__('Grade'); ?></a>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return InstitutionSiteClasses.validateClassAdd()" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classes'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	
	<?php } // end if displayContent ?>
</div>
