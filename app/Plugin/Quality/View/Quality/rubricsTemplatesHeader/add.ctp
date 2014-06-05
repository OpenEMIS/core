<?php 

//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_edit) {
    echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesHeader'), array('class' => 'divider', 'id'=>'back'));
}
$this->end();
$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action), 'file');
echo $this->Form->create($modelName, $formOptions);
?>
<?php echo $this->Form->hidden('rubric_template_id', array('value'=> $rubric_template_id)); ?>
<?php //echo $this->Form->hidden('order'); ?>
<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->hidden('id');} ?>

<?php if(!empty($this->data[$modelName]['order'])){ echo $this->Form->hidden('order');} ?>
<?php echo $this->Form->input('title'); ?>
<div class="controls view_controls">
	<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
    <?php 
	if(!empty($this->data[$modelName]['id'])){ 
		$redirectURL = array('action' => 'rubricsTemplatesHeaderView',$rubric_template_id,$this->data[$modelName]['id'] );
	}
	else{
		$redirectURL = array('action' => 'rubricsTemplatesHeader',$rubric_template_id);
	}
	?>
    
	<?php echo $this->Html->link(__('Cancel'), $redirectURL, array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>  