<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper details">
	<h1>
		<span><?php echo __('My Details'); ?></span>
		<?php echo $this->Html->link(__('Edit'), array('action' => 'detailsEdit'), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Username'); ?></div>
			<div class="value"><?php echo $obj['username']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Login'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['last_login']) . ' ' . date('H:i:s', strtotime($obj['last_login'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo !is_null($obj['telephone']) ? $obj['telephone'] : ''; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo !is_null($obj['email']) ? $obj['email'] : ''; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Roles'); ?></legend>
		
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell" style="width: 120px;"><?php echo __('Role'); ?></div>
				<div class="table_cell"><?php echo __('Modules'); ?></div>
			</div>
			
			<div class="table_body">
				<?php if($obj['super_admin']==0) { ?>
				
				<?php foreach($obj['roles'] as $roleId => $role) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $role['name']; ?></div>
					<div class="table_cell"><?php echo $role['modulesToString']; ?></div>
				</div>
				<?php } ?>
				
				<?php } else { ?>
				
				<div class="table_row">
					<div class="table_cell">Super Administrator</div>
					<div class="table_cell">Full access on all modules</div>
				</div>
				
				<?php } ?>
			</div>
		</div>
	</fieldset>
</div>
