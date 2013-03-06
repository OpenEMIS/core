<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="permissions" class="content_wrapper edit">
	<?php
	echo $this->Form->create('Security', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'permissionsEdit', $selectedRole)
	));
	?>
	<h1>
		<?php
		echo '<span>'.__('Permissions').'</span>';
		echo $this->Html->link(__('View'), 
				array('action' => 'permissions'), 
				array('class' => 'divider', 'onclick' => 'return security.navigate(this)'
			));
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
		$enabled = $func['enabled'] ? 'checked="checked"' : '';
		unset($func['enabled']);
	?>
	
	<fieldset class="section_group">
		<legend><input type="checkbox" class="module_checkbox" autocomplete="off" <?php echo $enabled; ?> /><?php echo __($module); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_function"><?php echo __('Function'); ?></div>
				<div class="table_cell"><?php echo __('View'); ?></div>
				<div class="table_cell"><?php echo __('Edit'); ?></div>
				<div class="table_cell"><?php echo __('Add'); ?></div>
				<div class="table_cell"><?php echo __('Delete'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($func as $obj) { $fieldName = sprintf('data[SecurityRoleFunction][%s][%%s]', $index++); ?>
				<div class="table_row <?php echo $obj['visible'] == 0 ? 'none' : ''; ?>" parent-id="<?php echo $obj['parent_id']; ?>" function-id="<?php echo $obj['security_function_id']; ?>">
					<?php
					echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
					echo $this->Form->hidden('security_function_id', array('name' => sprintf($fieldName, 'security_function_id'), 'value' => $obj['security_function_id']));
					echo $this->Form->hidden('security_role_id', array('name' => sprintf($fieldName, 'security_role_id'), 'value' => $selectedRole)); 
					?>
					<div class="table_cell"><?php echo __($obj['name']); ?></div>
					<?php
					foreach($_operations as $op) {
						echo $this->Utility->getPermissionInput($this->Form, $fieldName, $op, $obj[$op]);
					}
					?>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'permissions', $selectedRole), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>