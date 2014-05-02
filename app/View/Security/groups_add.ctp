<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Group'));
$this->start('contentActions');
$this->end();
$this->assign('contentId', 'groups');
$this->assign('contentClass', 'edit');

$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' =>'Security','action' => 'groupsAdd'));
echo $this->Form->create('SecurityGroup', array_merge($formOptions, array('onsubmit' => 'return Security.validateGroupAdd(this)')));
?>
<fieldset class="section_group" style="padding-bottom: 10px; position: relative;" id="group_info">
	<legend><?php echo __('Information'); ?></legend>
	<?php echo $this->Form->input('name'); ?>
</fieldset>

<fieldset class="section_group" style="padding-bottom: 10px;" id="group_admin">
	<legend><?php echo __('Administrator'); ?></legend>
	
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
	<legend><?php echo __('Access Control'); ?></legend>
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
		
		<div class="row icon_add_row">
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

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>