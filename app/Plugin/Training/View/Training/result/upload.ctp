<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>


<div id="training_session" class="content_wrapper edit add" url="Training/ajax_find_session/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'resultEdit', $id), array('class' => 'divider'));
          	echo $this->Html->link(__('Download Template'), array('action' => 'resultDownloadTemplate'), array('class' => 'divider'));
          	echo $this->Html->link(__('Upload Results'), array('action' => 'resultUpload'), array('class' => 'divider', 'style'=>'color:#000;'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Training', 'action' => 'resultUpload', 'plugin'=>'Training'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['error'])){ echo $error; } ?>
	<div class="row">
		<div class="label"><?php echo __('File'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('upload_file', array('type'=>'file')); 
		?>
        </div>
    </div>
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Upload"); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'resultEdit', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>