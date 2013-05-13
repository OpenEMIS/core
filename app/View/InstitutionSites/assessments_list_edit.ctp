<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_assessments', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment" class="content_wrapper">
	<?php
	echo $this->Form->create('Assessment', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'assessmentsListEdit', $selectedYear, $selectedProgramme)
	));
	?>
	<h1>
		<span><?php echo __('Assessments'); ?></span>
		<?php
		if($_edit && !empty($data)) {
			echo $this->Html->link(__('List'), array('action' => 'assessmentsList', $selectedYear, $selectedProgramme), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="filter_wrapper">
		<div class="row edit">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'label' => false,
					'div' => false,
					'options' => $yearOptions,
					'default' => $selectedYear,
					'onchange' => 'InstitutionSiteAssessments.navigateProgramme(this, false)',
					'url' => 'InstitutionSites/assessmentsListEdit'
				));
				?>
			</div>
		</div>
		<div class="row edit">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'class' => 'default',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'url' => 'Assessment/indexEdit/',
					'onchange' => 'InstitutionSiteAssessments.navigateProgramme(this, true)',
					'url' => 'InstitutionSites/assessmentsListEdit/'
				));
				?>
			</div>
		</div>
	</div>
	
	<?php 
	$i = 0;
	foreach($data as $key => $obj) {
	?>
		<fieldset class="section_group">
			<legend><?php echo $obj['name']; ?></legend>
			<div class="table full_width" style="margin-bottom: 0">
				<div class="table_head">
					<div class="table_cell cell_visible"><?php echo __('Status'); ?></div>
					<div class="table_cell cell_code"><?php echo __('Code'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
				</div>
			</div>
		<?php
			echo $this->Utility->getListStart();
			foreach($obj['assessment'][$type] as $item) {
				$isVisible = $item['visible']==1;
				$fieldName = sprintf('data[AssessmentItemType][%s][%%s]', $i);
				
				echo $this->Utility->getListRowStart($i, $isVisible);
				echo $this->Utility->getIdInput($this->Form, $fieldName, $item['id']);
				echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
				echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
				echo '<div class="cell cell_code"><span>' . $item['code'] . '</span></div>';
				echo $this->Utility->getNameInput($this->Form, $fieldName, $item['name'], false);
				echo $this->Utility->getOrderControls();
				echo $this->Utility->getListRowEnd();
				$i++;
			}
			echo $this->Utility->getListEnd();
		?>
		</fieldset>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'assessmentsList', $selectedYear, $selectedProgramme), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
