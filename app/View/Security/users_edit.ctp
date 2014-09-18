<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Details'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'usersView', $data['id']), array('class' => 'divider'));
echo $this->Html->link(__('Access'), array('action' => 'SecurityUserAccess', 'edit'), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' =>'Security','action' => 'usersEdit', $data['id']));
echo $this->Form->create('SecurityUser', $formOptions);

echo $this->Form->hidden('id', array('value' => $data['id']));
?>
	
<fieldset class="section_break">
	<legend><?php echo __('Login'); ?></legend>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Username'); ?></label>
		<div class="col-md-4 vcenter"><?php echo $data['username']; ?></div>
	</div>
	<?php echo $this->Form->input('new_password', array('type' => 'password', 'autocomplete' => 'off')); ?>
	<?php echo $this->Form->input('password'); ?>
	<?php echo $this->Form->input('retype_password', array('type' => 'password')); ?>

	<?php if($data['super_admin'] == 0) { ?>
	<?php echo $this->Form->input('status', array('options' => $statusOptions, 'value' => $data['status'])); ?>
	<?php } ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Last Login'); ?></label>
		<div class="col-md-4 vcenter">
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
	<?php echo $this->Form->input('identification_no', array('value' => $data['identification_no'])); ?>
	<?php echo $this->Form->input('first_name', array('value' => $data['first_name'])); ?>
	<?php echo $this->Form->input('last_name', array('value' => $data['last_name'])); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php echo $this->Form->input('telephone', array('value' => $data['telephone'])); ?>
	<?php echo $this->Form->input('email', array('value' => $data['email'])); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Groups'); ?></legend>
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell" style="width: 200px;"><?php echo __('Group'); ?></td>
				<td class="table_cell"><?php echo __('Role'); ?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php foreach($data['groups'] as $group) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $group['security_group_name']; ?></td>
					<td class="table_cell"><?php echo $group['security_role_name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Access'); ?></legend>
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell" style="width: 200px;"><?php echo __('OpenEMIS ID'); ?></td>
				<td class="table_cell"><?php echo __('Name'); ?></td>
				<td class="table_cell cell_module"><?php echo __('Module'); ?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php foreach($data['access'] as $obj) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $obj['SecurityUserAccess']['identification_no']; ?></td>
					<td class="table_cell"><?php echo $obj['SecurityUserAccess']['name']; ?></td>
					<td class="table_cell"><?php echo $obj['SecurityUserAccess']['table_name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	</div>
</fieldset>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'usersView'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>