<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$groupId = $data['SecurityGroup']['id'];
$groupName = $data['SecurityGroup']['name'];

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Group Details'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'groupsView', $groupId), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'groups');
$this->assign('contentClass', 'edit');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' =>'Security','action' => 'groupsEdit', $groupId));
echo $this->Form->create('SecurityGroup', array_merge($formOptions, array('onsubmit' => 'return Security.validateGroupAdd(this)')));

echo $this->Form->hidden('id', array('value' => $groupId));
?>

<fieldset class="section_group" style="padding-bottom: 10px; position: relative;" id="group_info">
	<legend><?php echo __('Information'); ?></legend>
	<?php echo $this->Form->input('name', array('default' => $groupName, 'value' => $groupName)); ?>
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
			
			<div class="table_body">
				<?php foreach($data['SecurityGroup']['areas'] as $index => $areaObj) { ?>
				<div class="table_row">
					<?php echo $this->Form->hidden('SecurityGroupArea.'.$index.'.area_id', array('class' => 'value_id', 'value' => $areaObj['area_id'])); ?>
					<div class="table_cell"><?php echo $areaObj['area_level_name']; ?></div>
					<div class="table_cell"><?php echo $areaObj['area_name']; ?></div>
					<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
				</div>
				<?php } ?>
			</div>
		</div>
		
		<div class="row" style="margin-left: 0;">
			<a class="void icon_plus" url="Security/groupsAddAccessOptions/areas" onclick="Security.addGroupAccessOptions(this)"><?php echo __('Add').' '.__('Area'); ?></a>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Institutions'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Institution'); ?></div>
				<div class="table_cell cell_delete"></div>
			</div>
			
			<div class="table_body">
				<?php foreach($data['SecurityGroup']['sites'] as $index => $siteObj) { ?>
				<div class="table_row">
					<?php echo $this->Form->hidden('SecurityGroupInstitutionSite.'.$index.'.institution_site_id', array('class' => 'value_id', 'value' => $siteObj['institution_site_id'])); ?>
					<div class="table_cell"><?php echo $siteObj['institution_site_name']; ?></div>
					<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
				</div>
				<?php } ?>
			</div>				
		</div>
		
		<div class="row" style="margin-left: 0;">
			<a class="void icon_plus" url="Security/groupsAddAccessOptions/sites" onclick="Security.addGroupAccessOptions(this)"><?php echo __('Add').' '.__('Institution'); ?></a>
		</div>
	</fieldset>
</fieldset>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'groupsView', $data['SecurityGroup']['id']), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
