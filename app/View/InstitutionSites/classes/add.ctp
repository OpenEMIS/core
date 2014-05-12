<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Class'));

$this->start('contentBody');

?>

<div id="classes" class="content_wrapper add">
	<?php if($displayContent) { ?>
	<?php 
	echo $this->Form->create('InstitutionSiteClass', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesAdd'),
		'inputDefaults' => array('label' => false, 'div' => false),
		'class' => 'form-horizontal'
	));
	?>
	
	<div class="form-group edit">
		<label class="control-label col-md-3"><?php echo __('Year'); ?></label>
		<div class=" col-md-4">
		<?php 
		echo $this->Form->input('school_year_id', array(
			'id' => 'SchoolYearId', 
			'class' => 'form-control',
			'options' => $yearOptions,
			'onchange' => 'InstitutionSiteClasses.switchYear()'
		));
		?>
		</div>
	</div>
	
	<div class="form-group edit">
		<label class="control-label col-md-3"><?php echo __('Class'); ?></label>
		<div class="value col-md-4"><?php echo $this->Form->input('name', array('id' => 'ClassName', 'class' => 'default form-control')); ?></div>
	</div>
    
        <div class="form-group edit">
		<label class="control-label col-md-3"><?php echo __('Seats'); ?></label>
		<div class="value col-md-4"><?php echo $this->Form->input('no_of_seats', array('id' => 'NoOfSeats', 'class' => 'default form-control')); ?></div>
	</div>
    
        <div class="form-group edit">
		<label class="control-label col-md-3"><?php echo __('Shift'); ?></label>
		<div class="value col-md-4"><?php echo $this->Form->input('no_of_shifts', array('id' => 'NoOfShifts', 'options' => $shiftOptions, 'class' => 'default form-control')); ?></div>
	</div>
	
	<table class="table table-striped table-hover table-bordered" id="grade_list">
		<thead>
			<tr>
			<td class="table_cell"><?php echo __('Programme'); ?></td>
			<td class="table_cell cell_grade"><?php echo __('Grade'); ?></td>
			<td class="table_cell cell_delete"></td>
			</tr>
		</thead>
		
		<?php
		$gradeModel = 'InstitutionSiteClassGrade';
		?>
		
		<tbody>
			<tr>
				<td class="table_cell">
					<?php
					echo $this->Form->input('institution_site_programme_id', array(
						'name' => 'institution_site_programme_id',
						'url' => 'InstitutionSites/programmesGradeList',
						'class' => 'form-control',
						'options' => $programmeOptions,
						'default' => $selectedProgramme,
						'onchange' => 'objInstitutionSite.getGradeList(this)'
					));
					?>
				</td>
				<td class="table_cell">
					<?php 
					echo $this->Form->input($gradeModel.'.0.education_grade_id', array(
						'class' => 'grades form-control',
						'options' => $gradeOptions
					)); 
					?>
				</td>
				<td class="table_cell"><?php echo $this->Utility->getDeleteControl(); ?></td>
			</tr>
		</tbody>
	</table>
	
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
<?php $this->end(); ?>
