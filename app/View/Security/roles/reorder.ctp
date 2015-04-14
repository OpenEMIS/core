<?php 
echo $this->Html->script('field.option', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($contentHeader) . ' - ' . __('Reorder'));
$this->start('contentActions');
$params = array('action' => 'roles');
if (!empty($selectedGroup)) {
	$params[] = $selectedGroup;
	$params['action'] = 'rolesUserDefined';
}
echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('../Security/roles/nav_tabs');

$formOptions = array('controller' => 'Security', 'action' => 'rolesMove');
if (isset($selectedGroup)) {
	$formOptions['security_group_id'] = $selectedGroup;
}
echo $this->Form->create($model, array('id' => 'OptionMoveForm', 'url' => $formOptions));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
echo $this->Form->end();
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
				'url' => $this->params['controller'] . '/' . $this->params['action'] . '/user_defined',
				'onchange' => 'jsForm.change(this)'
			));
			?>
	</div>
</div>
<?php endif; ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th class="cell-order"><?php echo $this->Label->get('general.order'); ?></th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php 
			$index = 1;
			//pr($roles);
			foreach ($roles as $obj) : 
			?>
			<tr row-id="<?php echo $obj['id']; ?>">
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
				<td><?php echo $obj['name']; ?></td>
				<td class="action">
					<?php
					$size = count($roles);
					echo $this->element('layout/reorder', compact('index', 'size'));
					$index++;
					?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>