<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->script('education', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper">
	<h1>
		<span><?php echo __('Users'); ?></span>
		<?php echo $this->Html->link(__('Add'), array('action' => 'usersAdd'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="table full_width allow_hover" action="<?php echo $this->params['controller'] . DS . 'usersView' . DS; ?>">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Username'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Roles'); ?></div>
			<div class="table_cell cell_status"><?php echo __('Status'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row <?php echo $obj['status']==0 ? 'inactive' : ''; ?>" row-id="<?php echo $obj['id']; ?>">
				<div class="table_cell"><?php echo $obj['username']; ?></div>
				<div class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?></div>
				<div class="table_cell">
					<?php echo $obj['super_admin'] == 1 ? 'Super Administrator' : $obj['roles']; ?>
				</div>
				<div class="table_cell cell_status"><?php echo $this->Utility->getStatus($obj['status']); ?></div>
			</div>
			<?php } ?>
		</div>
	</div>

</div>