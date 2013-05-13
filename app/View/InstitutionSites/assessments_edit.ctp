<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessmentAdd" class="content_wrapper edit">
	<?php
	echo $this->Form->create('AssessmentItemType', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'assessmentsEdit', $data['id'])
	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	?>
	<h1>
		<span><?php echo __('Edit Assessment Details'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'assessmentsView', $data['id']), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group info">
		<legend><?php echo __('Assessment Details'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value"><?php echo $data['school_year_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('class' => 'default', 'value' => $data['code'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('class' => 'default', 'value' => $data['name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Description'); ?></div>
			<div class="value"><?php echo $this->Form->input('description', array('type' => 'textarea', 'class' => 'default', 'value' => $data['description'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Level'); ?></div>
			<div class="value"><?php echo $data['education_level_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value"><?php echo $data['education_programme_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Education Grade'); ?></div>
			<div class="value"><?php echo $data['education_grade_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('visible', array(
					'class' => 'default',
					'options' => array(1 => __('Active'), 0 => __('Inactive')),
					'default' => $data['visible'] 
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
				foreach($data['AssessmentItem'] as $i => $item) {
					$visible = isset($item['visible']) && $item['visible'] == 1;
				?>
				<div class="table_row <?php echo $visible ? '' : 'inactive'; ?>">
					<?php
					echo $this->Form->hidden('education_grade_subject_id', array(
						'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
						'value' => $item['education_grade_subject_id']
					));
					echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $i, 'id'), 'value' => $item['id'] > 0 ? $item['id'] : 0));
					echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
					echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
					echo $this->Form->hidden('assessment_item_type_id', array('name' => sprintf($fieldName, $i, 'assessment_item_type_id'), 'value' => $data['id']));
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
								'value' => strlen($item['min'])==0 ? 50 : $item['min'],
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
								'value' => strlen($item['max'])==0 ? 100 : $item['max'],
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
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'assessmentsView', $data['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>