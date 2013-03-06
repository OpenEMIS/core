<?php
echo $this->Form->create($model, array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Education', 'action' => $action)
	)
);
echo $this->Form->hidden('order', array('value' => ($count+1)));
echo $this->Form->hidden('visible', array('value' => 1));
?>
<div class="field_row">
	<div class="field_name"><?php echo __('System'); ?></div>
	<div class="field_value"><?php echo $systemName; ?></div>
</div>
<div class="field_row">
	<div class="field_name"><?php echo __('Level'); ?></div>
	<div class="field_value"><?php echo $levelName; ?></div>
</div>
<div class="field_row field_input">
	<div class="field_name"><?php echo __('Cycle'); ?></div>
	<div class="field_value"><?php echo $this->Form->select('education_cycle_id', $cycleList, array('empty' => false)); ?></div>
</div>
<div class="field_row field_input">
	<div class="field_name"><?php echo __('Programme'); ?></div>
	<div class="field_value"><?php echo $this->Form->input('name', array('id' => 'EducationProgrammeName')); ?></div>
</div>
<div class="field_row field_input">
	<div class="field_name"><?php echo __('Duration'); ?></div>
	<div class="field_value">
		<?php echo $this->Form->input('duration', array(
				'id' => 'EducationProgrammeDuration',
				'value' => 1,
				'maxlength' => 3,
				'onkeypress' => 'return utility.integerCheck(event)'
		)); ?>
	</div>
</div>
<div class="field_row field_input">
	<div class="field_name"><?php echo __('Field of Study'); ?></div>
	<div class="field_value"><?php echo $this->Form->select('education_field_of_study_id', $fieldList, array('empty' => false)); ?></div>
</div>
<div class="field_row field_input">
	<div class="field_name"><?php echo __('Certification'); ?></div>
	<div class="field_value"><?php echo $this->Form->select('education_certification_id', $certificationList, array('empty' => false));	?></div>
</div>
<?php echo $this->Form->end(); ?>
