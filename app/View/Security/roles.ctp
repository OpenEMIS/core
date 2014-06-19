<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
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
<?php echo $this->element('alert'); ?>

<?php if(AuthComponent::user('super_admin')==1) { ?>
<fieldset class="section_group">
	<legend><?php echo __('System Defined Roles'); ?></legend>
	
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<td class="table_cell cell_visible"><?php echo __('Visible'); ?></td>
			<td class="table_cell"><?php echo __('Role'); ?></td>
			<td class="table_cell cell_permissions"><?php echo __('Permissions'); ?></td>
		</thead>
		
		<tbody class="table_body">
			<?php foreach($systemRoles as $obj) { ?>
			<tr class="table_row">
				<td class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
				<td class="table_cell"><?php echo $obj['name']; ?></td>
				<td class="table_cell cell_permissions">
					<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
				</td>
			</tr>
			<?php }?>
		</tbody>
	</table>
	</div>
</fieldset>
<?php } ?>

<?php if(!empty($groupOptions)) { ?>
<fieldset class="section_group">
	<legend><?php echo __('User Defined Roles'); ?></legend>
	
	<div class="row" style="margin: 0 0 10px 10px; line-height: 25px;">
		<div class="label" style="width: 60px;"><?php echo __('Group'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('security_group_id', array(
				'label' => false,
				'div' => false,
				'options' => $groupOptions,
				'default' => $selectedGroup,
				'url' => $this->params['controller'] . '/' . $this->params['action'],
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Role'); ?></div>
			<div class="table_cell cell_permissions"><?php echo __('Permissions'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($userRoles as $obj) { ?>
			<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></div>
				<div class="table_cell"><?php echo $obj['name'] ?></div>
				<div class="table_cell cell_permissions">
					<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</fieldset>
<?php } // end if ?>
<?php $this->end(); ?>