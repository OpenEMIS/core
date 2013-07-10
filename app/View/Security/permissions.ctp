<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="permissions" class="content_wrapper">
	<h1>
		<span><?php echo __('Permissions'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'roles', $group['id']), array('class' => 'divider'));
		if($_edit && $allowEdit) {
			echo $this->Html->link(__('Edit'), array('action' => 'permissionsEdit', $selectedRole), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php if(!empty($group)) { ?>
	<div class="row">
		<div class="label" style="width: 100px;"><?php echo __('Group Name'); ?></div>
		<div class="value"><?php echo $group['name']; ?></div>
	</div>
	<?php } ?>
	
	<div class="row input" style="margin-bottom: 15px;">
		<div class="label" style="width: 100px;"><?php echo __('Roles'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('security_role_id', array(
				'label' => false,
				'div' => false,
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
</div>