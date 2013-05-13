<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_results', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment_item" class="content_wrapper">
	<?php
	$itemId = $data['AssessmentItem']['id'];
	echo $this->Form->create('AssessmentItemResult', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'resultsItemEdit', $itemId, $selectedYear, $selectedClass)
	));
	?>
	<h1>
		<span><?php echo $data['AssessmentItemType']['name']; ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'resultsItem', $itemId, $selectedYear, $selectedClass), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="filter_wrapper">
		<div class="row edit">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'name' => 'school_year_id',
					'options' => $yearOptions,
					'default' => $selectedYear,
					'onchange' => 'InstitutionSiteResults.navigateClass(this, true)',
					'url' => 'InstitutionSites/resultsItemEdit/' . $itemId
				));
				?>
			</div>
		</div>
		
		<div class="row edit">
			<div class="label"><?php echo __('Classes'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('institution_site_class_id', array(
					'id' => 'InstitutionSiteClassId',
					'name' => 'institution_site_class_id',
					'class' => 'default',
					'options' => $classOptions,
					'default' => $selectedClass,
					'onchange' => 'InstitutionSiteResults.navigateClass(this, true)',
					'url' => 'InstitutionSites/resultsItemEdit/' . $itemId
				));
				?>
			</div>
		</div>
	</div>
	
	<fieldset class="section_group">
		<legend><?php echo $data['EducationSubject']['name']; ?></legend>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
				<div class="table_cell"><?php echo __('Student Name'); ?></div>
				<div class="table_cell cell_marks"><?php echo __('Marks'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($students as $i => $obj) { ?>
				<div class="table_row">
					<?php
					$result = $obj['AssessmentItemResult'];
					echo $this->Form->hidden($i.'.id', array('value' => $result['id']));
					echo $this->Form->hidden($i.'.student_id', array('value' => $obj['Student']['id']));
					?>
					<div class="table_cell middle"><?php echo $obj['Student']['identification_no']; ?></div>
					<div class="table_cell middle"><?php echo sprintf('%s %s', $obj['Student']['first_name'], $obj['Student']['last_name']); ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
							echo $this->Form->input($i.'.marks', array(
								'value' => $obj['AssessmentItemResult']['marks'],
								'maxlength' => 4,
								'min' => $data['AssessmentItem']['min'],
								'max' => $data['AssessmentItem']['max']//,
								//'onkeypress' => 'return utility.integerCheck(event)'
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
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'resultsItem', $itemId, $selectedYear, $selectedClass), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
