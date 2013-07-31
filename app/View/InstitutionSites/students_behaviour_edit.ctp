<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo __('Edit Behaviour Details'); ?></span>
		<?php
		$data = $studentBehaviourObj[0]['StudentBehaviour'];
		echo $this->Html->link(__('View'), array('action' => 'studentsBehaviourView', $data['id']), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <?php 
	echo $this->Form->create('InstitutionSiteStudentBehaviour', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviourEdit'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	echo $this->Form->hidden('student_id', array('value' => $data['student_id']));
	?>
	
    <div class="row edit">
		<div class="labelbehaviour"><?php echo __('Category'); ?></div>
		<div class="value">
		<?php 
		echo $this->Form->input('student_behaviour_category_id', array(
			'id' => 'student_behaviour_category_id',
			'options' => $categoryOptions,
			'default' => $data['student_behaviour_category_id']
		));
		?>
		</div>
	</div>

	<div class="row edit">
        <div class="labelbehaviour"><?php echo __('Date'); ?></div>
        <div class="value">
        <?php echo $this->Utility->getDatePicker($this->Form, 'date_of_behaviour',array('desc' => true,	'value' =>$data['date_of_behaviour'])); ?></div>
    </div>
	
	<div class="row edit">
		<div class="labelbehaviour"><?php echo __('Title'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('title', array('id' => 'title', 'class' => 'default', 'default' => $data['title'])); ?>
		</div>
	</div>
	
	<div class="row edit">
		<div class="labelbehaviour"><?php echo __('Description'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('description', array('class' => 'default', 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)',
		'default' => $data['description'])); ?>
		</div>
	</div>
    
    <div class="row edit">
		<div class="labelbehaviour"><?php echo __('Action'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('action', array('class' => 'default', 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)',
		'default'=>$data['action'])); ?>
		</div>
	</div>
    
    <div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsBehaviourView', $data['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
