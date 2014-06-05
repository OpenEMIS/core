<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/training_needs', false);
?>

<?php echo $this->element('breadcrumb'); ?>


<div id="training_need" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'trainingNeed'), array('class' => 'divider'));
        
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Staff', 'action' => 'trainingNeedAdd', 'plugin'=>'Staff'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
	<div class="row">
		<div class="label"><?php echo __('Course Title'); ?></div>
        <div class="value">
		<?php
            echo $this->Form->input('training_course_id', array(
                'options' => $trainingCourseOptions,
                'default' => $selectedCourse,
                'label' => false,
                'empty' => __('--Select--'),
                'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
                'onchange' => 'objTrainingNeeds.getDetailsAfterChangeCourse(this)'
            ));
        ?>
        </div>
    </div>
    <div class="row">
		<div class="label"><?php echo __('Course Code'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('code', array('disabled' => 'disabled', 'class' => 'default training_course_code')); 
		?>
        </div>
    </div>
    <div class="row">
		<div class="label"><?php echo __('Description'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('description', array('disabled' => 'disabled', 'class' => 'default training_course_description', 'type'=>'textarea')); 
		?>
        </div>
    </div>
    <div class="row">
		<div class="label"><?php echo __('Training Requirement'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('requirement', array('disabled' => 'disabled', 'class' => 'default training_course_requirement')); 
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
		<?php if(!isset($this->request->data['StaffTrainingNeed']['training_status_id'])|| $this->request->data['StaffTrainingNeed']['training_status_id']==1){ ?>
		<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'trainingNeed'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
 * 
 */ ?>
 
<?php
echo $this->Html->script('Staff.training_needs', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'trainingNeedView', $this->data[$model]['id']);
}
else{
	$redirectAction = array('action' => 'trainingNeed');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->hidden('training_status_id');
echo $this->Form->input('training_course_id', array(
	'options' => $trainingCourseOptions,
	'label' => array('text' => $this->Label->get('StaffTraining.course_title'), 'class' => 'col-md-3 control-label'),
	'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
	'onchange' => 'objTrainingNeeds.getDetailsAfterChangeCourse(this)'
));
echo $this->Form->input('code', array('disabled' => 'disabled', 'class' => 'form-control training_course_code', 'label' => array('text' => $this->Label->get('StaffTraining.code'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('description', array('disabled' => 'disabled', 'class' => 'form-control training_course_description', 'type' => 'textarea'));
echo $this->Form->input('requirement', array('disabled' => 'disabled', 'class' => 'form-control training_course_requirement', 'label' => array('text' => $this->Label->get('StaffTraining.requirement'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('training_priority_id', array('options' => $trainingPriorityOptions));
echo $this->Form->input('comments');

//echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
?>
<div class="controls view_controls">
<?php if (!isset($this->request->data['StaffTrainingNeed']['training_status_id']) || $this->request->data['StaffTrainingNeed']['training_status_id'] == 1) { ?>
			<input type="submit" value="<?php echo $this->Label->get('general.save'); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
			<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
<?php } ?>
<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'trainingNeed'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php
echo $this->Form->end();
$this->end();
?>
