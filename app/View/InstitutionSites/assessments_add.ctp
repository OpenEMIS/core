<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);
echo $this->Html->script('institution_site_assessments', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessmentAdd" class="content_wrapper edit">
	<?php
	echo $this->Form->create('AssessmentItemType', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'assessmentsAdd')
	));
	?>
	<h1>
		<span><?php echo __('Add Assessment'); ?></span>
		<?php echo $this->Html->link(__('List'), array('action' => 'assessmentsList'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if(!empty($yearOptions)) { ?>
	<fieldset class="section_group info">
		<legend><?php echo __('Assessment Details'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'class' => 'default',
					'options' => $yearOptions,
					'default' => $selectedYear,
					'onchange' => 'InstitutionSiteAssessments.navigateProgramme(this, false)',
					'url' => 'InstitutionSites/assessmentsAdd'
				));
				?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('class' => 'default')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('class' => 'default')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Description'); ?></div>
			<div class="value"><?php echo $this->Form->input('description', array('type' => 'textarea', 'class' => 'default')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'class' => 'default',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'onchange' => 'Assessment.loadGradeList(this)',
					'url' => 'Assessment/loadGradeList'
				));
				?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Grade'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_grade_id', array(
					'id' => 'EducationGradeId',
					'class' => 'default',
					'options' => $gradeOptions,
					'default' => $selectedGrade,
					'empty' => '-- ' . (empty($gradeOptions) ? __('No Grade Available') : __('Select Grade')) . ' --',
					'onchange' => 'Assessment.loadSubjectList(this)',
					'url' => 'Assessment/loadSubjectList'
				));
				?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group items">
		<legend><?php echo __('Assessment Items'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_checkbox"><input type="checkbox" onchange="jsForm.toggleSelect(this);" /></div>
				<div class="table_cell cell_subject_code"><?php echo __('Subject Code'); ?></div>
				<div class="table_cell"><?php echo __('Subject Name'); ?></div>
				<div class="table_cell cell_number_input"><?php echo __('Minimum'); ?></div>
				<div class="table_cell cell_number_input"><?php echo __('Maximum'); ?></div>
			</div>
			<div class="table_body">
				<?php 
				$fieldName = 'data[AssessmentItem][%d][%s]';
				foreach($items as $i => $item) { 
					$visible = isset($item['visible']) && $item['visible'] == 1;
				?>
				<div class="table_row <?php echo $visible ? '' : 'inactive'; ?>">
					<?php
					echo $this->Form->hidden('education_grade_subject_id', array(
						'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
						'value' => $item['education_grade_subject_id']
					));
					echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
					echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
					?>
					<div class="table_cell">
						<input type="checkbox" name="<?php echo sprintf($fieldName, $i, 'visible'); ?>" value="1" autocomplete="off" onChange="jsList.activate(this, '.table_row')" <?php echo $visible ? 'checked="checked"' : ''; ?>/>
					</div>
					<div class="table_cell"><?php echo $item['code']; ?></div>
					<div class="table_cell"><?php echo $item['name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
							echo $this->Form->input('min', array(
								'name' => sprintf($fieldName, $i, 'min'),
								'value' => $item['min'],
								'maxlength' => 4,
								'onkeypress' => 'return utility.integerCheck(event)'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
							echo $this->Form->input('max', array(
								'name' => sprintf($fieldName, $i, 'max'),
								'value' => $item['max'],
								'maxlength' => 4,
								'onkeypress' => 'return utility.integerCheck(event)'
							));
						?>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'assessmentsList'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php } // end if(yearOptions) ?>
	
	<?php echo $this->Form->end(); ?>
</div>