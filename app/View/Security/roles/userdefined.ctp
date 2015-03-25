<?php

echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'rolesAdd', 'user_defined'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Security/roles/nav_tabs');
?>

<?php if(!empty($groupOptions)) : ?>

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
	<th class="cell_visible"><?php echo $this->Label->get('general.visible'); ?></th>
	<th><?php echo $this->Label->get('SecurityRole.name'); ?></th>
	<th class="cell_permissions"><?php echo $this->Label->get('SecurityRole.permissions'); ?></th>
</thead>

<tbody>
			<?php foreach($userRoles as $obj) { ?>
	<tr class="<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
		<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
		<td><?php echo $this->Html->link($obj['name'], array('action' => 'rolesView', $obj['id'])); ?></td>
		<td class="cell_permissions">
					<?php echo $this->Html->link($this->Label->get('SecurityRole.permissions'), array('action' => 'permissions', $obj['id'])); ?>
		</td>
	</tr>
			<?php } ?>
</tbody>
</table>
<?php endif; ?>
<?php $this->end(); ?>
