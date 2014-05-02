<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

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

<div class="row input" style="margin-bottom: 15px;">
	<label class="col-md-3 control-label"><?php echo __('Roles'); ?></label>
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
	<div class="table">
		<div class="table_head">
			<div class="table_cell cell_function"><?php echo __('Function'); ?></div>
			<div class="table_cell"><?php echo __('View'); ?></div>
			<div class="table_cell"><?php echo __('Edit'); ?></div>
			<div class="table_cell"><?php echo __('Add'); ?></div>
			<div class="table_cell"><?php echo __('Delete'); ?></div>
			<div class="table_cell"><?php echo __('Execute'); ?></div>
		</div>
		
		<div class="table_body">
		<?php foreach($func as $obj) { ?>
			<?php if($obj['visible'] == 1) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo __($obj['name']); ?></div>
				<?php foreach($_operations as $op) { ?>
				<div class="table_cell center"><?php echo $this->Utility->checkOrCrossMarker($obj[$op]>=1); ?></div>
				<?php } ?>
			</div>
			<?php } ?> <!-- end if -->
		<?php } ?> <!-- end for -->
		</div>
	</div>
</fieldset>

<?php } ?>
<?php $this->end(); ?>