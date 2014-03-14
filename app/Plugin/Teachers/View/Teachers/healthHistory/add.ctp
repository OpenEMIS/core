<?php
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Teachers/js/teachers', false);
echo $this->Html->script('config', false);
//$obj = @$data['Student'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		if(!empty($this->data[$modelName]['id'])){
			echo $this->Html->link(__('View'), array('action' => 'healthHistoryView', $this->data[$modelName]['id']), array('class' => 'divider'));
		}
		else{
			echo $this->Html->link(__('List'), array('action' => 'healthHistory'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Teachers', 'action' =>$this->action, 'plugin'=>'Teachers'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Condition'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('health_condition_id', array(
									'options' => $healthConditionsOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Current'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('current', array(
									'options' => $booleanOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=> 'textarea'));?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'healthHistory'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>