<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

 echo $this->Html->charset();

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));

$this->start('contentActions');
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index', $type) , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody'); ?>

<?php echo $this->element('alert'); ?>
<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action));
	echo $this->Form->create('ConfigItem', $formOptions);

	echo $this->Form->input('id', array('type' => 'hidden'));
	echo $this->Form->input('name', array('type' => 'hidden'));
	echo $this->Form->input('field_type', array('type' => 'hidden'));
	echo $this->Form->input('option_type', array('type' => 'hidden'));

	echo $this->Form->input('type', array('value'=> $type, 'readonly' => 'readonly'));
	echo $this->Form->input('label', array('value'=> $type, 'readonly' => 'readonly'));

	if($fieldType=='Dropdown'){
		echo $this->Form->input('value', array('options'=>$options));
		echo $this->Form->input('default_value', array('value' => $options[$this->request->data['ConfigItem']['default_value']],'readonly' => 'readonly'));
	}else if($fieldType=='File'){
		echo $this->Form->input('ConfigItem.file_value', array('type' => 'file', 'class' => 'form-error'));
		echo $this->Form->hidden('ConfigItem.value', array('value'=> (empty($item['value']))?$item['default_value']:$item['value'] ));
		echo $this->Form->hidden('ConfigItem.reset_yearbook_logo', array('value'=>'0'));
		echo "<span id=\"resetDefault\" class=\"icon_delete\"></span>";
        echo isset($imageUploadError) ? '<div class="error-message">'.$imageUploadError.'</div>' : '';
        echo "<br/>";
        echo "<div id=\"image_upload_info\"><em>";
        echo sprintf(__("Max Resolution: %s pixels"), '400 x 514')."<br/>";
        echo __("Max File Size:"). ' 200 KB' ."<br/>";
        echo __("Format Supported:"). " .jpg, .jpeg, .png, .gif".
        "</em>";
        echo '</div>';
	}else if($fieldType=='Datepicker'){
		echo $this->FormUtility->datepicker('value');
		echo $this->Form->input('default_value', array('readonly' => 'readonly'));
	}else{
		echo $this->Form->input('value');
		echo $this->Form->input('default_value', array('readonly' => 'readonly'));
	}
	
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
        var photoContent = $('input[id^="ConfigItemYearbook"][id$="FileValue"]');
        var resetImage= $('input[id^="ConfigItemYearbook"][id$="ResetYearbookLogo"]');
        

        if (photoContent.attr('disabled')){
            photoContent.removeAttr('disabled');
            resetImage.attr('value', '0');
        }else {
            photoContent.attr('disabled', 'disabled');
            resetImage.attr('value', '1');
        }
    });
});
</script>
<?php $this->end(); ?>