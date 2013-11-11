<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_student_behaviour', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teacherBehaviourAdd" class="content_wrapper add">
    <h1>
        <span><?php echo __('Add Behaviour'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSiteTeacherBehaviour', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'teachersBehaviourAdd'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	?>

	<div class="row edit">
        <div class="label"><?php echo __('Institution Site'); ?></div>
        <div class="value">
        <?php echo $institutionSiteOptions[$institution_site_id]; ?>
        </div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Category'); ?></div>
		<div class="value">
		<?php 
		echo $this->Form->input('teacher_behaviour_category_id', array(
			'id' => 'teacher_behaviour_category_id', 
			'options' => $categoryOptions
		));
		?>
		</div>
	</div>

	<div class="row edit">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_of_behaviour', array('desc' => true,'value' => date("Y-m-d"))); ?></div>
    </div>
	
	<div class="row edit">
		<div class="label"><?php echo __('Title'); ?></div>
		<div class="value"><?php echo $this->Form->input('title', array('id' => 'title', 'class' => 'default')); ?></div>
	</div>
	
	<div class="row edit">
		<div class="label"><?php echo __('Description'); ?></div>
		<div class="value"><?php echo $this->Form->input('description', array('class' => 'default', 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)')); ?></div>
	</div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Action'); ?></div>
		<div class="value"><?php echo $this->Form->input('action', array('class' => 'default', 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)')); ?></div>
	</div>
	
	<div class="controls">
    	<input type="hidden" name="data[InstitutionSiteTeacherBehaviour][teacher_id]" id="teacher_id" value="<?php echo $id; ?>" />
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return InstitutionSiteStudentBehaviour.validateBehaviourAdd()"  />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'teachersBehaviour', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
