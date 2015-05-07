<?php

echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add && $isSuperUser) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'rolesAdd', 'system_defined'), array('class' => 'divider'));
}

if ($_edit && $isSuperUser && count($systemRoles) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'rolesReorder', 'system_defined'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Security/roles/nav_tabs');
?>

<?php if($isSuperUser): ?>
<table class="table table-striped table-hover table-bordered">
	<thead>
	<td class="cell_visible"><?php echo $this->Label->get('general.visible'); ?></td>
	<td class="cell_visible"><?php echo $this->Label->get('general.editable'); ?></td>
	<td><?php echo $this->Label->get('SecurityRole.name'); ?></td>
	<td class="cell_permissions"><?php echo $this->Label->get('SecurityRole.permissions'); ?></td>
</thead>

<tbody>
			<?php foreach($systemRoles as $obj) { ?>
	<tr>
		<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
		<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['security_group_id'] == 0); ?></td>
		<td>
					<?php
					if ($obj['security_group_id'] == 0) {
						echo $this->Html->link($obj['name'], array('action' => 'rolesView', $obj['id']));
					} else {
						echo __($obj['name']);
					}
					?>
		</td>
		<td class="cell_permissions">
					<?php echo $this->Html->link($this->Label->get('SecurityRole.permissions'), array('action' => 'permissions', $obj['id'])); ?>
		</td>
	</tr>
			<?php }?>
</tbody>
</table>
<?php else: ?>
<div class="alert-dismissible alert alert-warning"><?php echo $this->Label->get('general.no_records') ?></div>
<?php endif; ?>
<?php $this->end(); ?>
