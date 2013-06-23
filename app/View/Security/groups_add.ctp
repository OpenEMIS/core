<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('Security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="groups" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Add Group'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityGroup', array(
		'url' => array('controller' => 'Security', 'action' => 'groupsAdd'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	?>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Group Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('class' => 'default')); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_group" style="padding-bottom: 10px;" id="group_admin">
		<legend><?php echo __('Group Administrator'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_area"><?php echo __('Identification No'); ?></div>
				<div class="table_cell"><?php echo __('Name'); ?></div>
				<div class="table_cell cell_delete"></div>
			</div>
			<div class="table_body"></div>
		</div>
		
		<div class="row">
			<a class="void icon_plus" url="Security/usersAddAdmin/" onclick="Security.addGroupAdmin(this)"><?php echo __('Add').' '.__('Administrator'); ?></a>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Group Access'); ?></legend>
		<fieldset class="section_break">
			<legend><?php echo __('Areas'); ?></legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_area"><?php echo __('Level'); ?></div>
					<div class="table_cell"><?php echo __('Area'); ?></div>
					<div class="table_cell cell_delete"></div>
				</div>
				
				<div class="table_body"></div>
			</div>
			
			<div class="row" style="margin-left: 0;">
				<a class="void icon_plus" url="Security/groupsAddAccessOptions/areas" onclick="Security.addGroupAccessOptions(this)"><?php echo __('Add').' '.__('Area'); ?></a>
			</div>
		</fieldset>
		
		<fieldset class="section_break">
			<legend><?php echo __('Institution Sites'); ?></legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_institution"><?php echo __('Institution'); ?></div>
					<div class="table_cell"><?php echo __('Institution Site'); ?></div>
					<div class="table_cell cell_delete"></div>
				</div>
				
				<div class="table_body"></div>
			</div>
			
			<div class="row" style="margin-left: 0;">
				<a class="void icon_plus" url="Security/groupsAddAccessOptions/sites" onclick="Security.addGroupAccessOptions(this)"><?php echo __('Add').' '.__('Institution Site'); ?></a>
			</div>
		</fieldset>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'groups'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>