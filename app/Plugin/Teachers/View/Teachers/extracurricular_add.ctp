<?php
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('/Teachers/js/teachers', false);

echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('extracurricular', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="extracurricular" class="content_wrapper edit add">
	
    <h1>
        <span><?php echo __('Extracurricular'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'extracurricular'), array('class' => 'divider'));
        }
        ?>
    </h1>
	
    <?php

    echo $this->Form->create('TeacherExtracurricular', array(
        'url' => array('controller' => 'Teachers', 'action' => 'extracurricularAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php echo $this->element('alert'); ?>
    
    
    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('school_year_id', array(
									'options' => $years,
									'selected' => $selectedYear,
									'label' => false)
									); 
		?>
        </div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('extracurricular_type_id', array(
									'options' => $types,
									//'selected' => !empty($data['TimetableEntry']['education_grade_subject_id'])? $data['TimetableEntry']['education_grade_subject_id'] : '0',
									'label' => false)
									); 
		?>
        </div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Title'); ?></div>
        <div class="value"><?php echo $this->Form->input('name', array('class'=> 'default autoComplete', 'url'=> 'searchAutoComplete')); ?></div>
    </div>
    
    <div class="row">
    <?php //pr($this->data); ?>
        <div class="label"><?php echo __('Start Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'start_date'); ?></div>
    </div> 
    
    <div class="row">
    <?php //pr($this->data); ?>
        <div class="label"><?php echo __('End Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'end_date', array('value'=> date('Y-m-d', time()+86400))); ?></div>
    </div> 
    
    <div class="row">
        <div class="label"><?php echo __('Hours'); ?></div>
        <div class="value"><?php echo $this->Form->input('hours', array( 'type' => 'number' )); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Points'); ?></div>
        <div class="value"><?php echo $this->Form->input('points', array( 'type' => 'number' )); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value"><?php echo $this->Form->input('location'); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('comment', array('type'=>'textarea')); ?>
        </div>
    </div>
    <!--
    <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"></div>
    </div>
    
    -->
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'extracurricular'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
   <?php echo $this->Form->end(); ?>
</div>