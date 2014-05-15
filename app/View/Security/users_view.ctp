<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Details'));
$this->start('contentActions');
if($_edit && $allowEdit) {
			echo $this->Html->link(__('Edit'), array('action' => 'usersEdit', $data['id']), array('class' => 'divider'));
		}
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'details');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<fieldset class="section_break">
	<legend><?php echo __('Login'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Username'); ?></div>
		<div class="col-md-4"><?php echo $data['username']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Password'); ?></div>
		<div class="col-md-4"><?php echo '************'; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Status'); ?></div>
		<div class="col-md-4"><?php echo $this->Utility->getStatus($data['status']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Login'); ?></div>
		<div class="col-md-4">
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
		<div class="col-md-3"><?php echo __('Identification No'); ?></div>
		<div class="col-md-4"><?php echo $data['identification_no']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-4"><?php echo $data['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-4"><?php echo $data['last_name']; ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Telephone'); ?></div>
		<div class="col-md-4"><?php echo $data['telephone']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Email'); ?></div>
		<div class="col-md-4"><?php echo $data['email']; ?></div>
	</div>
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
<?php $this->end(); ?>