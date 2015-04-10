<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Details'));
$this->start('contentActions');
if($_edit && $allowEdit) {
			echo $this->Html->link(__('Edit'), array('action' => 'usersEdit', $data['SecurityUser']['id']), array('class' => 'divider'));
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
		<div class="col-md-4"><?php echo $data['SecurityUser']['username']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Password'); ?></div>
		<div class="col-md-4"><?php echo '************'; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Status'); ?></div>
		<div class="col-md-4"><?php echo $this->Utility->getStatus($data['SecurityUser']['status']); ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Login'); ?></div>
		<div class="col-md-4">
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
	<div class="row">
		<div class="col-md-3"><?php echo __('Identification No'); ?></div>
		<div class="col-md-4"><?php echo $data['SecurityUser']['openemis_no']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-4"><?php echo $data['SecurityUser']['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-4"><?php echo $data['SecurityUser']['last_name']; ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell"><?php echo __('Description'); ?></td>
					<td class="table_cell"><?php echo __('Value'); ?></td>
					<td class="table_cell"><?php echo __('Preferred'); ?></td>
				</tr>
			</thead>
			
			<tbody class="table_body">
				<?php foreach ($data['UserContact'] as $key => $value) { ?>
					<tr class="table_row">
						<td class="table_cell"><?php echo $value['ContactType']['name'] . ' - ' . $value['ContactType']['ContactOption']['name']; ?></td>
						<td class="table_cell"><?php echo $value['value']; ?></td>
						<td class="table_cell"><?php echo $this->Utility->checkOrCrossMarker($value['preferred']==1); ?></td>
						
					</tr>
				<?php } ?>
			</tbody>
		</table>
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
<?php $this->end(); ?>