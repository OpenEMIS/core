<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Roles'));
$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'rolesEdit', $selectedGroup), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'roles');

$this->start('contentBody');
?>

<?php if(AuthComponent::user('super_admin')==1) : ?>
<fieldset class="section_group">
	<legend><?php echo __('System Defined Roles'); ?></legend>
	
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<td class="cell_visible"><?php echo __('Visible'); ?></td>
			<td><?php echo __('Role'); ?></td>
			<td class="cell_permissions"><?php echo __('Permissions'); ?></td>
		</thead>
		
		<tbody>
			<?php foreach($systemRoles as $obj) { ?>
			<tr class="table_row">
				<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
				<td><?php echo $obj['name']; ?></td>
				<td class="cell_permissions">
					<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
				</td>
			</tr>
			<?php }?>
		</tbody>
	</table>
</fieldset>
<?php endif; ?>

<?php if(!empty($groupOptions)) : ?>
<fieldset class="section_group">
	<legend><?php echo __('User Defined Roles'); ?></legend>
	
	<div class="row page-controls">
		<div class="col-md-4">
			<?php
			echo $this->Form->input('security_group_id', array(
				'label' => false,
				'div' => false,
				'class' => 'form-control',
				'options' => $groupOptions,
				'default' => $selectedGroup,
				'url' => $this->params['controller'] . '/' . $this->params['action'],
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<th class="cell_visible"><?php echo __('Visible'); ?></th>
			<th><?php echo __('Role'); ?></th>
			<th class="cell_permissions"><?php echo __('Permissions'); ?></th>
		</thead>
		
		<tbody>
			<?php foreach($userRoles as $obj) { ?>
			<tr class="<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
				<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
				<td><?php echo $obj['name'] ?></td>
				<td class="cell_permissions">
					<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</fieldset>
<?php endif; ?>
<?php $this->end(); ?>
