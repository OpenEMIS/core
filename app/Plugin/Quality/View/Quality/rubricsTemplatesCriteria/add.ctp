<?php 
echo $this->Html->css('/Quality/css/colorpicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/colorpicker', false);
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		//pr($this->data);
		if($_add) {
		//	echo $this->Html->link(__('Add'), array('action' => 'rubrics_add'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
	echo $this->Form->create($modelName, array(
		//'url' => array('controller' => 'Quality', 'action' => 'RubricsTemplatesSetupCriteriaAdd', 'plugin'=>'Quality'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php echo $this->Form->hidden('rubric_template_id', array('value'=> $id)); ?>
    <?php if(!empty($this->data['RubricsTemplateColumnInfo']['id'])){ echo $this->Form->hidden('id');} ?>
    <?php if(!empty($this->data['RubricsTemplateColumnInfo']['order'])){ echo $this->Form->hidden('order');} ?>
    <?php $selectedColor = empty($this->data['RubricsTemplateColumnInfo']['color'])? 'ffffff':$this->data['RubricsTemplateColumnInfo']['color']; ?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('name'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Weighting'); ?></div>
        <div class="value"><?php echo $this->Form->input('weighting'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Color'); ?></div>
        <div class="value"><div id="colorSelector"><div style="background-color:#<?php echo $selectedColor;?>"></div></div><?php echo $this->Form->hidden('color', array('id'=> 'newColorPick')); ?> </div>
      
    </div>
    
    <div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php 
		if(!empty($this->data['RubricsTemplateColumnInfo']['id'])){ 
			$redirectURL = array('action' => 'rubricsTemplatesCriteriaView',$id,$rubricTemplateHeaderId,$this->data['RubricsTemplateColumnInfo']['id'] );
		}
		else{
			$redirectURL = array('action' => 'rubricsTemplatesCriteria',$id,$rubricTemplateHeaderId);
		}
		?>
        
		<?php echo $this->Html->link(__('Cancel'), $redirectURL, array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
    <?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
$('#colorSelector').ColorPicker({
	//flat: true
	color: '#<?php echo $selectedColor;?>',
	onShow: function (colpkr) {
		$(colpkr).fadeIn(500);
		return false;
	},
	onHide: function (colpkr) {
		$(colpkr).fadeOut(500);
		return false;
	},
	onChange: function (hsb, hex, rgb) {
		$('#colorSelector div').css('backgroundColor', '#' + hex);
		$('#newColorPick').val(hex);
	}
});
	  </script>