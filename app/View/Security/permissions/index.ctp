<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
$params = array('action' => 'roles');
if (isset($selectedGroup)) {
	$params[] = $selectedGroup;
	$params['action'] = 'roles_user_defined';
}
echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
if($_edit && $allowEdit) {
	echo $this->Html->link(__('Edit'), array('action' => 'permissionsEdit', $selectedRole, $selectedModule), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'permissions');

$this->start('contentBody');
echo $this->element('../Security/permissions/controls');

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
