<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper edit details">
	<h1>
		<span><?php echo __('User Details'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'usersView', $data['id']), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Security', 'action' => 'usersEdit', $data['id']),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	?>
		
	<fieldset class="section_break">
		<legend><?php echo __('Login'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Username'); ?></div>
			<div class="value"><?php echo $data['username']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('New Password'); ?></div>
			<div class="value"><?php echo $this->Form->input('new_password', array('type' => 'password')); ?></div>
			<?php echo $this->Form->input('password', array('class' => 'none')); ?>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Retype Password'); ?></div>
			<div class="value"><?php echo $this->Form->input('retype_password', array('type' => 'password')); ?></div>
		</div>
		<?php if($data['super_admin'] == 0) { ?>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('status', array('options' => $statusOptions, 'value' => $data['status'])); ?></div>
		</div>
		<?php } ?>
		<div class="row">
			<div class="label"><?php echo __('Last Login'); ?></div>
			<div class="value">
			<?php 
				if(!is_null($data['last_login'])) {
					echo $this->Utility->formatDate($data['last_login']) . ' ' . date('H:i:s', strtotime($data['last_login']));
				} else {
					echo '<i>' . __('Not login yet') . '</i>';
				}
			?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name', array('value' => $data['first_name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name', array('value' => $data['last_name'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone', array('value' => $data['telephone'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email', array('value' => $data['email'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Roles'); ?></legend>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_checkbox"><input type="checkbox" onchange="jsForm.toggleSelect(this);" /></div>
				<div class="table_cell" style="width: 120px;"><?php echo __('Role'); ?></div>
				<div class="table_cell"><?php echo __('Modules'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				if($data['super_admin']==0) {
					foreach($roles as $key => $list) {
						foreach($list as $roleId => $role) {
				?>
				<div class="table_row <?php echo $key==1 ? 'inactive' : ''; ?>">
					<div class="table_cell">
						<input type="checkbox" name="data[SecurityRole][<?php echo $roleId; ?>]" <?php echo $key==0 ? 'checked="checked"' : ''; ?> autocomplete="off" onChange="jsList.activate(this, '.table_row')" />
					</div>
					<div class="table_cell"><?php echo $role['name']; ?></div>
					<div class="table_cell"><?php echo $role['modulesToString']; ?></div>
				</div>
				<?php
						}
					}
				} else {
				?>
				
				<div class="table_row">
					<div class="table_cell"><input type="checkbox" checked="checked" disabled="disabled" /></div>
					<div class="table_cell"><?php echo __('Super Administrator'); ?></div>
					<div class="table_cell"><?php echo __('Full access on all modules'); ?></div>
				</div>
				
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'usersView'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>