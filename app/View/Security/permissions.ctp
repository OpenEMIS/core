<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Roles'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'roles', $selectedRole), array('class' => 'divider'));
if($_edit && $allowEdit) {
	echo $this->Html->link(__('Edit'), array('action' => 'permissionsEdit', $selectedRole), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'permissions');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php if(!empty($group)) { ?>
<div class="row">
	<label class="col-md-3 control-label"><?php echo __('Group Name'); ?></label>
	<div class="col-md-4"><?php echo $group['name']; ?></div>
</div>
<?php } ?>

<div class="row page-controls">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('security_role_id', array(
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $roles,
			'default' => $selectedRole,
			'url' => $this->params['controller'] . '/' . $this->params['action'],
			'onchange' => 'jsForm.change(this)'
		));
		?>
	</div>
</div>

<?php 
$index = 0;
foreach($permissions as $module => $func) {
?>

<fieldset class="section_group">
	<legend><?php echo __($module); ?></legend>
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<th class="cell_function"><?php echo __('Function'); ?></th>
			<th><?php echo __('View'); ?></th>
			<th><?php echo __('Edit'); ?></th>
			<th><?php echo __('Add'); ?></th>
			<th><?php echo __('Delete'); ?></th>
			<th><?php echo __('Execute'); ?></th>
		</thead>
		
		<tbody>
		<?php foreach($func as $obj) : ?>
			<?php if($obj['visible'] == 1) : ?>
			<tr>
				<td><?php echo __($obj['name']); ?></td>
				<?php foreach($_operations as $op) : ?>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$op]>=1); ?></td>
				<?php endforeach; ?>
			</tr>
			<?php endif; ?>
		<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>

<?php } ?>
<?php $this->end(); ?>
