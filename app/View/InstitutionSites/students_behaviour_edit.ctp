<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo __('Overview'); ?></span>
		<?php
		echo $this->Html->link(__('List'), array('action' => 'studentsBehaviour', $studentBehaviourObj[0]['StudentBehaviour']['student_id']), array('class' => 'divider'));
		echo $this->Html->link(__('View'), array('action' => 'studentsBehaviourView', $studentBehaviourObj[0]['StudentBehaviour']['id']), array('class' => 'divider'));
		
		echo '<a href="#" class="divider" onclick=\'$("#delButton").click();\'>Delete</a>';
		
		?>
        
        <?php
            echo $this->Form->create('DeleteBehaviour', array(
                'id' => 'delbehaviour',
                'inputDefaults' => array('label' => false, 'div' => false),	
                'url' => array('controller' => 'InstitutionSites','action' => 'studentsBehaviourDelete')
            ));
        ?>
        <input type="hidden" name="data[DeleteBehaviour][student_id]" id="student_id" value="<?php echo $studentBehaviourObj[0]['StudentBehaviour']['student_id']; ?>" />
        <input type="hidden" name="data[DeleteBehaviour][id]" id="id" value="<?php echo $studentBehaviourObj[0]['StudentBehaviour']['id']; ?>" />
        <?php
            echo $this->Form->input('delButton', array(
                'id' => 'delButton',
                'type' => 'submit',
                'style' => 'visibility:hidden; display:none;'
            ));
        ?>
        <?php echo $this->Form->end(); ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <?php 
	echo $this->Form->create('InstitutionSiteStudentBehaviour', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviourEdit'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	?>
    
    <div class="row edit">
		<div class="labelbehaviour"><?php echo __('Category'); ?></div>
		<div class="value">
		<?php 
		echo $this->Form->input('student_behaviour_category_id', array(
			'id' => 'student_behaviour_category_id',
			'label' => false, 
			'options' => $categoryOptions,
			'default' => $studentBehaviourObj[0]['StudentBehaviour']['student_behaviour_category_id']
		));
		?>
		</div>
	</div>
	
	<div class="row edit">
		<div class="labelbehaviour"><?php echo __('Title'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('title', array('id' => 'title', 'class' => 'default', 'label'=>false, 'default' => $studentBehaviourObj[0]['StudentBehaviour']['title'])); ?>
		</div>
	</div>
	
	<div class="row edit">
		<div class="labelbehaviour"><?php echo __('Description'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('description', array('class' => 'default', 'label'=>false, 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)',
		'default' => $studentBehaviourObj[0]['StudentBehaviour']['description'])); ?>
		</div>
	</div>
    
    <div class="row edit">
		<div class="labelbehaviour"><?php echo __('Action'); ?></div>
		<div class="value">
		<?php echo $this->Form->input('action', array('class' => 'default', 'label'=>false, 'type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)',
		'default'=>$studentBehaviourObj[0]['StudentBehaviour']['action'])); ?>
		</div>
	</div>
    
    <div class="row edit">
		<div class="labelbehaviour"><?php echo __('Date'); ?></div>
		<div class="value">
		<?php echo $this->Utility->getDatePicker($this->Form, 'date_of_behaviour',array('desc' => true,
		'value' =>$studentBehaviourObj[0]['StudentBehaviour']['date_of_behaviour'])); ?></div>
	</div>
    
    <div class="controls">
    	<input type="hidden" name="data[InstitutionSiteStudentBehaviour][student_id]" id="student_id" value="<?php echo $studentBehaviourObj[0]['StudentBehaviour']['student_id']; ?>" />
        <input type="hidden" name="data[InstitutionSiteStudentBehaviour][id]" id="id" value="<?php echo $studentBehaviourObj[0]['StudentBehaviour']['id']; ?>" />
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsBehaviourView', $studentBehaviourObj[0]['StudentBehaviour']['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
