<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="special_need" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            echo $this->Html->link(__('Back'), array('action' => 'specialNeed'), array('class' => 'divider'));
        
		?>
	</h1>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Students', 'action' => 'specialNeedAdd', 'plugin'=>'Students'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('special_need_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('special_need_type_id', array(
									'options' => $specialNeedTypeOptions,
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
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'specialNeed'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>