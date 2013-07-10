<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper edit details">
	<h1><?php echo __('Add User'); ?></h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Security', 'action' => 'usersAdd'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	?>
	
	<fieldset class="section_break">
		<legend><?php echo __('Login'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Username'); ?></div>
			<div class="value"><?php echo $this->Form->input('username'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Password'); ?></div>
			<div class="value"><?php echo $this->Form->input('password'); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Identification No'); ?></div>
			<div class="value"><?php echo $this->Form->input('identification_no'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name'); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email'); ?></div>
		</div>
	</fieldset>
	
	<!--fieldset class="section_break">
		<legend><?php echo __('Roles'); ?></legend>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_checkbox"><input type="checkbox" onchange="jsForm.toggleSelect(this);" /></div>
				<div class="table_cell" style="width: 120px;"><?php echo __('Role'); ?></div>
				<div class="table_cell"><?php echo __('Modules'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($roles as $roleId => $role) { ?>
				<div class="table_row">
					<div class="table_cell"><input type="checkbox" name="data[SecurityRole][]" value="<?php echo $roleId; ?>" /></div>
					<div class="table_cell"><?php echo $role['name']; ?></div>
					<div class="table_cell"><?php echo $role['modulesToString']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset-->
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'users'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>