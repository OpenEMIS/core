<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="role_users" class="content_wrapper">
	<?php
	echo $this->Form->create('Security', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'roleUsersEdit', $roleId)
	));
	?>
	<h1>
		<span><?php echo __('Role Assignment'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'roleUsers', $roleId), array('class' => 'divider')); ?>
	</h1>
	
	<div class="row input role_select">
		<div class="label"><?php echo __('Roles'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('role', array(
				'href' => $this->params['controller'] . '/' . $this->params['action'],
				'options' => $roleOptions,
				'default' => $roleId,
				'onchange' => 'security.switchRole(this)',
				'div' => false,
				'label' => false
			));
			?>
		</div>
	</div>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_checkbox"><input type="checkbox" onchange="jsForm.toggleSelect(this);" /></div>
			<div class="table_cell"><?php echo __('Username'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Email'); ?></div>
		</div>
		
		<div class="table_body">
			<?php 
			foreach($data as $key => $userList) {
				foreach($userList as $obj) {
			?>
				<div class="table_row <?php echo $key==1 ? 'inactive' : ''; ?>">
					<div class="table_cell">
						<input type="checkbox" name="data[SecurityUser][<?php echo $obj['id']; ?>]" <?php echo $key==0 ? 'checked="checked"' : ''; ?> autocomplete="off" onChange="jsList.activate(this, '.table_row')" />
					</div>
					<div class="table_cell"><?php echo $obj['username']; ?></div>
					<div class="table_cell"><?php echo trim($obj['first_name'] . ' ' . $obj['last_name']); ?></div>
					<div class="table_cell"><?php echo $obj['email']; ?></div>
				</div>
			<?php 
				}
			}
			?>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'roleUsers', $roleId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>