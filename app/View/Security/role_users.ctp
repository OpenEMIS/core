<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="role_users" class="content_wrapper">
	<?php
	echo $this->Form->create('Security', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'roleUsers')
	));
	?>
	<h1>
		<span><?php echo __('Role Assignment'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'roleUsersEdit', $roleId), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row input role_select">
		<div class="label"><?php echo __('Roles'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('role', array(
				'href' => $this->params['controller'] . '/' . $this->params['action'],
				'options' => $roleOptions,
				'default' => $roleId,
				'onchange' => 'security.switchRole(this)',
				'div' => false,
				'label' => false
			));
			?>
		</div>
	</div>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Username'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Email'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $obj['username']; ?></div>
				<div class="table_cell"><?php echo trim($obj['first_name'] . ' ' . $obj['last_name']); ?></div>
				<div class="table_cell"><?php echo $obj['email']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<div class="row" style="padding-left: 5px;">
		<?php echo $this->Html->link('<span>&laquo;</span> ' . __('Back to Roles'), 
			array('action' => 'roles'),
			array('escape' => false, 'class' => 'link_back')
		); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>