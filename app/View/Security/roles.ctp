<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper">
	<h1>
		<span><?php echo __('Roles'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'rolesEdit'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Role'); ?></div>
			<div class="table_cell cell_permissions"><?php echo __('Permissions'); ?></div>
			<div class="table_cell cell_users"><?php echo __('Users'); ?></div>
			<div class="table_cell cell_users"><?php echo __('Areas'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj) { ?>
			<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></div>
				<div class="table_cell"><?php echo $obj['name'] ?></div>
				<div class="table_cell cell_permissions">
					<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
				</div>
				<div class="table_cell cell_users">
					<?php echo $this->Html->link(__('Users'), array('action' => 'roleUsers', $obj['id'])); ?>
				</div>
				<div class="table_cell cell_users">
					<?php echo $this->Html->link(__('Areas'), array('action' => 'roleAreas', $obj['id'])); ?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>