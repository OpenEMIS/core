<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<?php
$session = $this->Session;
?>

<script type="text/javascript">
$(document).ready(function() {
	objSearch.attachAutoComplete();
});
</script>

<div id="students" class="content_wrapper search">
	<h1>
		<span><?php echo __('Advanced Search'); ?></span>
		<?php 
		echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
		echo $this->Html->link(__('Clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('Search', array(
		'url' => array('controller' => 'Students', 'action' => 'advanced'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('Student.AdvancedSearch.area_id')));
	?>
	<div class="row">
		<div class="label"><?php echo __('Area'); ?></div>
		<div class="value"><?php echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('Student.AdvancedSearch.area'))); ?></div>
	</div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Search'); ?>" class="btn_save btn_right" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>