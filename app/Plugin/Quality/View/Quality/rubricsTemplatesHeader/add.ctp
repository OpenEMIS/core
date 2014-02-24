<?php 

//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
	echo $this->Form->create($modelName, array(
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php echo $this->Form->hidden('rubric_template_id', array('value'=> $rubric_template_id)); ?>
    <?php //echo $this->Form->hidden('order'); ?>
    <?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->hidden('id');} ?>
    
    <?php if(!empty($this->data[$modelName]['order'])){ echo $this->Form->hidden('order');} ?>
    <div class="row">
        <div class="label"><?php echo __('Header'); ?></div>
        <div class="value"><?php echo $this->Form->input('title'); ?> </div>
    </div>
    <div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php 
		if(!empty($this->data[$modelName]['id'])){ 
			$redirectURL = array('action' => 'RubricsTemplatesHeaderView',$rubric_template_id,$this->data[$modelName]['id'] );
		}
		else{
			$redirectURL = array('action' => 'RubricsTemplatesHeader',$rubric_template_id);
		}
		?>
        
		<?php echo $this->Html->link(__('Cancel'), $redirectURL, array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
    <?php echo $this->Form->end(); ?>
</div>