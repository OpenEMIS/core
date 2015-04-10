<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Details'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'usersView', $data['SecurityUser']['id']), array('class' => 'divider'));
echo $this->Html->link(__('Access'), array('action' => 'SecurityUserAccess', 'view'), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' =>'Security','action' => 'usersEdit', $data['SecurityUser']['id']));
echo $this->Form->create('SecurityUser', $formOptions);

echo $this->Form->hidden('id', array('value' => $data['SecurityUser']['id']));
?>
	
<fieldset class="section_break">
	<legend><?php echo __('Login'); ?></legend>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Username'); ?></label>
		<div class="col-md-4 vcenter"><?php echo $data['SecurityUser']['username']; ?></div>
	</div>
	<?php echo $this->Form->input('new_password', array('type' => 'password', 'autocomplete' => 'off')); ?>
	<?php echo $this->Form->input('retype_password', array('type' => 'password')); ?>

	<?php if($data['SecurityUser']['super_admin'] == 0) { ?>
	<?php echo $this->Form->input('status', array('options' => $statusOptions, 'value' => $data['SecurityUser']['status'])); ?>
	<?php } ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Last Login'); ?></label>
		<div class="col-md-4 vcenter">
		<?php 
			if(!is_null($data['SecurityUser']['last_login'])) {
				echo $this->Utility->formatDate($data['SecurityUser']['last_login']) . ' ' . date('H:i:s', strtotime($data['SecurityUser']['last_login']));
			} else {
				echo '<i>' . __('Not login yet') . '</i>';
			}
		?>
		</div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<?php echo $this->Form->input('openemis_no', array('value' => $data['SecurityUser']['openemis_no'])); ?>
	<?php echo $this->Form->input('first_name', array('value' => $data['SecurityUser']['first_name'])); ?>
	<?php echo $this->Form->input('last_name', array('value' => $data['SecurityUser']['last_name'])); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
		<?php echo $this->element('contact/userContactEdit'); ?>
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
			<?php foreach($data['SecurityGroupUser'] as $group) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $group['SecurityGroup']['name']; ?></td>
					<td class="table_cell"><?php echo $group['SecurityRole']['name']; ?></td>
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
			<?php foreach($data['SecurityUserAccess'] as $key => $value) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $value['SecurityUser']['openemis_no']; ?></td>
					<td class="table_cell"><?php echo $this->Model->getName($value['SecurityUser']) ?></td>
					<td class="table_cell"><?php echo $value['table_name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
		</table>
	</div>
</fieldset>

<?php echo $this->FormUtility->getFormButtons(array('reloadBtn' => true, 'cancelURL' => array('action' => 'usersView', $data['SecurityUser']['id']))); ?>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>