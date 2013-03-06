<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="permissions" class="content_wrapper">
	<?php
	echo $this->Form->create('Security', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'permissions')
	));
	?>
	<h1>
		<?php
		echo '<span>'.__('Permissions').'</span>';
		if($_edit) {
			echo $this->Html->link(__('Edit'), 
				array('action' => 'permissionsEdit'), 
				array('class' => 'divider', 'onclick' => 'return security.navigate(this)'
			));
		}
		?>
	</h1>
	
	<div class="row input role_select">
		<div class="label"><?php echo __('Roles'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('security_role_id', array(
				'href' => $this->params['controller'] . '/' . $this->params['action'],
				'options' => $roles,
				'default' => $selectedRole,
				'onchange' => 'security.switchRole(this)'
			));
			?>
		</div>
	</div>
	
	<?php 
	$index = 0;
	foreach($permissions as $module => $func) {
	?>
	
	<fieldset class="section_group">
		<legend><?php echo __($module); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_function"><?php echo __('Function'); ?></div>
				<div class="table_cell"><?php echo __('View'); ?></div>
				<div class="table_cell"><?php echo __('Edit'); ?></div>
				<div class="table_cell"><?php echo __('Add'); ?></div>
				<div class="table_cell"><?php echo __('Delete'); ?></div>
			</div>
			
			<div class="table_body">
			<?php foreach($func as $obj) { ?>
				<?php if($obj['visible'] == 1) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo __($obj['name']); ?></div>
					<?php foreach($_operations as $op) { ?>
					<div class="table_cell center"><?php echo $this->Utility->checkOrCrossMarker($obj[$op]==1); ?></div>
					<?php } ?>
				</div>
				<?php } ?> <!-- end if -->
			<?php } ?> <!-- end for -->
			</div>
		</div>
	</fieldset>
	
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>