<?php
echo $this->Html->css('/Quality/css/colorpicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/colorpicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $subheader);
$this->start('contentBody');
$formOptions = array_merge(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Quality'), $this->params['pass']);
$formOptions = $this->FormUtility->getFormOptions($formOptions);
echo $this->Form->create($model, $formOptions);


echo $this->Form->hidden('rubric_template_id', array('value' => $id));
if (!empty($this->data['RubricsTemplateColumnInfo']['id'])) {
	echo $this->Form->hidden('id');
}
if (!empty($this->data['RubricsTemplateColumnInfo']['order'])) {
	echo $this->Form->hidden('order');
}
$selectedColor = empty($this->data['RubricsTemplateColumnInfo']['color']) ? 'ffffff' : $this->data['RubricsTemplateColumnInfo']['color'];

echo $this->Form->input('name');
echo $this->Form->input('weighting');
?>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Color'); ?></label>
	<div class="col-md-6"><div id="colorSelector"><div style="background-color:#<?php echo $selectedColor; ?>"></div></div><?php echo $this->Form->hidden('color', array('id' => 'newColorPick')); ?> </div>

</div>

<?php
if (!empty($this->data['RubricsTemplateColumnInfo']['id'])) {
	$redirectAction = array('action' => 'rubricsTemplatesCriteriaView', $id, $rubricTemplateHeaderId, $this->data['RubricsTemplateColumnInfo']['id']);
} else {
	$redirectAction = array('action' => 'rubricsTemplatesCriteria', $id, $rubricTemplateHeaderId);
}
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));

echo $this->Form->end();
?>


<script type="text/javascript">
	$('#colorSelector').ColorPicker({
		//flat: true
		color: '#<?php echo $selectedColor; ?>',
		onShow: function(colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onHide: function(colpkr) {
			$(colpkr).fadeOut(500);
			return false;
		},
		onChange: function(hsb, hex, rgb) {
			$('#colorSelector div').css('backgroundColor', '#' + hex);
			$('#newColorPick').val(hex);
		}
	});
</script>

<?php $this->end(); ?>