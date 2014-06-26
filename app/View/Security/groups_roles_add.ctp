<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="groups" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Add Group Roles'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityGroup', array(
		'url' => array('controller' => 'Security', 'action' => 'groupsAdd'),
		'inputDefaults' => array('label' => false, 'div' => false),
		'onsubmit' => 'return Security.validateGroupName(this);'
	));
	?>
	
	<fieldset class="section_group">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Group Name'); ?></div>
			<div class="value"><?php echo $data['SecurityGroup']['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Role Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('class' => 'default')); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Permissions'); ?></legend>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'groupsEdit', $data['SecurityGroup']['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>