<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_enrolment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="enrolment" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusStudent', array(
		'id' => 'submitForm',
		'onsubmit' => 'return false',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'enrolmentEdit')
	));
	?>
	<h1>
		<span><?php echo __('Enrolment'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'enrolment'), array('id' => 'edit-link', 'class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $years,
				'default' => $selectedYear
			));
			?>
		</div>
		
	<?php echo $this->element('census_legend'); ?>
	</div>
	
	<?php foreach($data as $key => $obj) { ?>
	<fieldset class="section_group" url="Census/enrolmentAjax/<?php echo $selectedYear; ?>">
		<legend>
			<span><?php echo $obj['name']; ?></span>
		</legend>
		
		<div class="row" style="margin-bottom: 15px;">
			<div class="label grade">Grade</div>
			<div class="value grade">
				<?php
				echo $this->Form->input('education_grade_id', array(
					'id' => 'EducationGradeId',
					'options' => $obj['grades'],
					'onchange' => 'CensusEnrolment.get(this)',
					'autocomplete' => 'off'
				));
				?>
			</div>
			<div class="label category"><?php echo __('Category'); ?></div>
			<div class="value category">
				<?php
				echo $this->Form->input('student_category_id', array(
					'id' => 'StudentCategoryId',
					'options' => $category,
					'onchange' => 'CensusEnrolment.get(this)',
					'autocomplete' => 'off'
				));
				?>
			</div>
		</div>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Age'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
				<?php if($_delete) { ?>
				<div class="table_cell cell_delete">&nbsp;</div>
				<?php } ?>
			</div>
			
			<div class="table_body">
				<?php
				$total = 0;
				$records = $obj['enrolment'];
				foreach($records as $record) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row" record-id="<?php echo $record['id']; ?>">
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('age', array(
								'type' => 'text',
								'class' => $record_tag,
								'value' => $record['age'],
								'defaultValue' => $record['age'],
								'maxlength' => 2,
								'autocomplete' => 'off',
								'onkeypress' => 'return utility.integerCheck(event);',
								'onblur' => 'CensusEnrolment.checkExistingAge(this);'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('male', array(
								'type' => 'text',
								'class' => $record_tag,
								'value' => $record['male'],
								'defaultValue' => $record['male'],
								'maxlength' => 10, 
								'autocomplete' => 'off',
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'CensusEnrolment.computeSubtotal(this);'
							));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input('female', array(
								'type' => 'text',
								'class' => $record_tag,
								'value' => $record['female'],
								'defaultValue' => $record['female'],
								'maxlength' => 10,
								'autocomplete' => 'off',
								'onkeypress' => 'return utility.integerCheck(event)',
								'onkeyup' => 'CensusEnrolment.computeSubtotal(this);'
							));
						?>
						</div>
					</div>
					<div class="table_cell cell_total cell_number"><?php echo $record['male'] + $record['female']; ?></div>
					<?php if($_delete) { ?>
					<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this)"></span></div>
					<?php } ?>
				</div>
				<?php } // end foreach (records) ?>
			</div> <!-- End Table Body -->
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
				<?php if($_delete) { ?>
				<div class="table_cell"></div>
				<?php } ?>
			</div>
		</div>
		<?php if($_add) { ?>
		<div class="row"><a class="void icon_plus" url="Census/enrolmentAddRow"><?php echo __('Add').' '.__('Age'); ?></a></div>
		<?php } ?>
	</fieldset>
	<?php } // end foreach (enrolment) ?>
	
	<?php if(!empty($data)) { ?>
	<div class="controls">
		<input type="button" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="CensusEnrolment.save()" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>