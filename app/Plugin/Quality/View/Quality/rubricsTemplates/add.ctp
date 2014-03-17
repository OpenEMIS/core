<?php 
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		/*if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'rubrics_add'), array('class' => 'divider'));
		}*/
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    $formOptions = array('controller' => 'Quality', 'action' => $this->action, 'plugin' => 'Quality');
	echo $this->Form->create($modelName, array(
		'url' => $formOptions,
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php echo $this->Form->input('institution_id', array('type'=> 'hidden'));  ?>
    <?php
    if (!empty($this->data[$modelName]['id'])) {
        echo $this->Form->input('id', array('type' => 'hidden'));
    }
    ?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('name'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value"><?php echo $this->Form->input('description'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Weighting'); ?></div>
        <div class="value"><?php echo $this->Form->input('weighting', array('options' => $weightingOptions)); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Pass Mark'); ?></div>
        <div class="value"><?php echo $this->Form->input('pass_mark'); ?> </div>
    </div>
    <div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesView',$id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
    <?php echo $this->Form->end(); ?>
</div>