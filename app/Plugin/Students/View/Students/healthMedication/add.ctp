<?php
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Students/js/students', false);
echo $this->Html->script('config', false);
//$obj = @$data['Student'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		if(!empty($this->data[$modelName]['id'])){
			echo $this->Html->link(__('View'), array('action' => 'healthMedicationView', $this->data[$modelName]['id']), array('class' => 'divider'));
		}
		else{
			echo $this->Html->link(__('List'), array('action' => 'healthMedication'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Students', 'action' =>  $this->action, 'plugin'=>'Students'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	
 	<div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('name'); ?> </div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Dosage'); ?></div>
        <div class="value"><?php echo $this->Form->input('dosage'); ?> </div>
    </div>
	
    <div class="row">
        <div class="label"><?php echo __('Commenced Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Ended Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('end_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'healthMedication'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>