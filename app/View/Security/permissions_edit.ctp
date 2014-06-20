<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->script('security', false);
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Permissions'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'permissions', $selectedRole), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'permissions');
$this->assign('contentClass', 'edit');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php
echo $this->Form->create('Security', array(
	'inputDefaults' => array('label' => false, 'div' => false),	
	'url' => array('controller' => 'Security', 'action' => 'permissionsEdit', $selectedRole)
));
?>


<?php if(!empty($group)) { ?>
<div class="row">
	<label class="col-md-3 control-label"><?php echo __('Group Name'); ?></label>
	<div class="col-md-4"><?php echo $group['name']; ?></div>
</div>
<?php } ?>

<div class="row page-controls" style="margin-bottom: 15px;">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('security_role_id', array(
			'label' => false,
			'div' => false,
			'options' => $roles,
			'default' => $selectedRole,
			'class' => 'form-control',
			'url' => $this->params['controller'] . '/' . $this->params['action'],
			'onchange' => 'jsForm.change(this)'
		));
		?>
	</div>
</div>

<?php 
$index = 0;
foreach($permissions as $module => $func) {
	$enabled = $func['enabled'] ? 'checked="checked"' : '';
	unset($func['enabled']);
?>

<fieldset class="section_group">
	<legend><input type="checkbox" class="module_checkbox" autocomplete="off" <?php echo $enabled; ?> /><?php echo __($module); ?></legend>
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
			<?php foreach($func as $obj) { $fieldName = sprintf('data[SecurityRoleFunction][%s][%%s]', $index++); ?>
			<tr class="<?php echo $obj['visible'] == 0 ? 'none' : ''; ?>" parent-id="<?php echo $obj['parent_id']; ?>" function-id="<?php echo $obj['security_function_id']; ?>">
				<?php
				echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
				echo $this->Form->hidden('security_function_id', array('name' => sprintf($fieldName, 'security_function_id'), 'value' => $obj['security_function_id']));
				echo $this->Form->hidden('security_role_id', array('name' => sprintf($fieldName, 'security_role_id'), 'value' => $selectedRole)); 
				?>
				<td><?php echo __($obj['name']); ?></td>
				<?php
				foreach($_operations as $op) {
					echo $this->FormUtility->getPermissionInput($this->Form, $fieldName, $op, $obj[$op]);
				}
				?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</fieldset>

<?php } ?>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'permissions', $selectedRole)));
echo $this->Form->end();
$this->end();
?>
