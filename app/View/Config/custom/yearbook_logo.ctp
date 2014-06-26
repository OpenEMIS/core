<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));
?>
<?php if($action == 'view') { ?>
	<?php $this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index', $type), array('class' => 'divider'));
	if($_edit && $editable) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $id), array('class' => 'divider'));
	}
	$this->end();

	$this->start('contentBody'); ?>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.type');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['type'];?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.label');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['label'];?></div>
	</div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.value');?></div>
	<div class="col-md-6"><?php 
	if (!empty($attachment['ConfigAttachment']['file_content'])) {
		echo $this->Html->image("/Config/fetchYearbookImage/{$data['ConfigItem']['value']}", array('class' => 'profile_image', 'alt' => '90x115')); 
	}
	?>
	</div>
	</div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.default');?></div>
	<div class="col-md-6"><?php
	if (!empty($defaultAttachment['ConfigAttachment']['file_content'])) {
		echo $this->Html->image("/Config/fetchYearbookImage/{$data['ConfigItem']['default_value']}", array('class' => 'profile_image', 'alt' => '90x115')); 
	}
	?></div>
	</div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.modified_by');?></div><div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name'];?></div></div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.modified');?></div><div class="col-md-6"><?php echo $data['ConfigItem']['modified'];?>
	</div></div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.created_by');?></div><div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name'];?></div></div>
	<div class="row"><div class="col-md-3"><?php echo $this->Label->get('general.created');?></div><div class="col-md-6"><?php echo $data['ConfigItem']['created'];?>
	</div></div>

	<?php $this->end(); ?> 
<?php } ?>
<?php if($action == 'edit') { ?>
<?php
$this->start('contentActions');
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'view', $id) , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody'); ?>

<?php echo $this->element('alert'); ?>

<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'yearbookEdit', $id), 'File');
	echo $this->Form->create('ConfigItem', $formOptions);


	echo $this->Form->input('type', array('disabled' => 'disabled'));
	echo $this->Form->input('label', array('disabled' => 'disabled'));

	echo $this->Form->input('ConfigItem.file_value', array('type' => 'file', 'class' => 'form-error'));
	echo $this->Form->hidden('ConfigItem.value', array('value'=> (empty($this->request->data['ConfigItem']['value']))?$this->request->data['ConfigItem']['default_value']:$this->request->data['ConfigItem']['value'] ));
    echo "<div class='form-group'>";
    echo "<label class='col-md-3 control-label'></label>";
    echo "<div class='col-md-4' id=\"image_upload_info\">";
    echo '<p>';

	echo $this->Form->hidden('ConfigItem.reset_yearbook_logo', array('value'=>'0'));
	echo "<span id=\"resetDefault\" class=\"icon_delete\"></span>";
    echo '<div id="divPhoto">';
	if (!empty($attachment['ConfigAttachment']['file_content'])) {
		echo $this->Html->image("/Config/fetchYearbookImage/{$this->data['ConfigItem']['value']}", array('class' => 'profile_image', 'alt' => '90x115')); 
	}
	echo '</div>';

    echo isset($imageUploadError) ? '<div class="error-message">'.$imageUploadError.'</div>' : '';
    echo '</p>';
    echo "<em>";
    echo sprintf(__("Max Resolution: %s pixels"), '400 x 514')."<br/>";
    echo __("Max File Size:"). ' 200 KB' ."<br/>";
    echo __("Format Supported:"). " .jpg, .jpeg, .png, .gif".
    "</em>";

    echo '</div>';
    echo '</div>';
	
	?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'view', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>

<script type="text/javascript">
$(document).ready(function() {

    $('#resetDefault').click(function(e){
        e.preventDefault();
        var photoContent = $('input[id^="ConfigItem"][id$="FileValue"]');
        var resetImage= $('input[id^="ConfigItem"][id$="ResetYearbookLogo"]');
        
        if (photoContent.attr('disabled')){
    	 	$('#divPhoto').show();
            photoContent.removeAttr('disabled');
            resetImage.attr('value', '0');
        }else {
        	$('#divPhoto').hide();
            photoContent.attr('disabled', 'disabled');
            resetImage.attr('value', '1');
        }
    });
});
</script>
<?php $this->end(); ?> 
 <?php } ?>