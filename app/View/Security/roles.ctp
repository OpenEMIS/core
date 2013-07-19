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
			echo $this->Html->link(__('Edit'), array('action' => 'rolesEdit', $selectedGroup), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php if(AuthComponent::user('super_admin')==1) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('System Defined Roles'); ?></legend>
		
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
				<div class="table_cell cell_permissions"><?php echo __('Permissions'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($systemRoles as $obj) { ?>
				<div class="table_row">
					<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
					<div class="table_cell cell_permissions">
						<?php echo $this->Html->link(__('Permissions'), array('action' => 'permissions', $obj['id'])); ?>
					</div>
				</div>
				<?php }?>
			</div>
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
</div>