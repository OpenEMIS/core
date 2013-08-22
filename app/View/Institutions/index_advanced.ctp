<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false); 
?>

<?php echo $this->element('breadcrumb'); ?>

<script type="text/javascript">
$(function() {
	$("#area").autocomplete({
		source: "indexAdvanced",
		minLength: 2,
		select: function(event, ui) {
			$('#area').val(ui.item.label);
			$('#area_id').val(ui.item.value);
			return false;
		}
	});
});
</script>

<?php
$session = $this->Session;
?>

<div id="institutions" class="content_wrapper search">
	<h1>
		<span><?php echo __('Advanced Search'); ?></span>
		<?php echo $this->Html->link(__('Clear'), array('action' => 'indexAdvanced', 0), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('Search', array(
		'url' => array('controller' => 'Institutions', 'action' => 'indexAdvanced'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('Institution.AdvancedSearch.area_id')));
	?>
	<div class="row">
		<div class="label"><?php echo __('Area'); ?></div>
		<div class="value"><?php echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('Institution.AdvancedSearch.area'))); ?></div>
	</div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Search'); ?>" class="btn_save btn_right" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>