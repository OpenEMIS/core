<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>


<div id="training_need" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'training_need'), array('class' => 'divider'));
        
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Teachers', 'action' => 'trainingNeedAdd', 'plugin'=>'Teachers'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
	<div class="row">
		<div class="label"><?php echo __('Course'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('training_course_id', array('options' => $trainingCourseOptions)); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Priority'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('training_priority_id', array('options'=>$trainingPriorityOptions));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('comments', array('type'=>'textarea'));?>
        </div>
    </div>
	<div class="controls view_controls">
		<?php if(!isset($this->request->data['TeacherTrainingNeed']['training_status_id'])|| $this->request->data['TeacherTrainingNeed']['training_status_id']==1){ ?>
		<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'trainingNeed'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>