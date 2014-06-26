<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'resultEdit', $id), array('class' => 'divider'));
echo $this->Html->link(__('Download Template'), array('action' => 'resultDownloadTemplate'), array('class' => 'divider'));
echo $this->Html->link(__('Upload Results'), array('action' => 'resultUpload'), array('class' => 'divider', 'style'=>'color:#000;'));

$this->end();
$this->start('contentBody');
?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'resultUpload'), 'file');
echo $this->Form->create($modelName, $formOptions);
?>

<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>

<?php 
	echo $this->Form->input('upload_file', array('type'=>'file')); 
	?>
 
<div class="controls view_controls">
	<input type="submit" value="<?php echo __("Upload"); ?>" name='save' class="btn_save btn_right"/>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'result'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
