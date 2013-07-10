<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="groups" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Group Details'); ?></span>
		<?php
		$groupId = $data['SecurityGroup']['id'];
		$groupName = $data['SecurityGroup']['name'];
		echo $this->Html->link(__('View'), array('action' => 'groupsView', $groupId), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityGroup', array(
		'url' => array('controller' => 'Security', 'action' => 'groupsEdit', $groupId),
		'inputDefaults' => array('label' => false, 'div' => false),
		'onsubmit' => 'return Security.validateGroupAdd(this)'
	));
	echo $this->Form->hidden('id', array('value' => $groupId));
	?>
	
	<fieldset class="section_group" style="padding-bottom: 10px; position: relative;" id="group_info">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('class' => 'default', 'default' => $groupName, 'value' => $groupName)); ?></div>
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
			<legend><?php echo __('Institution Sites'); ?></legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_institution"><?php echo __('Institution'); ?></div>
					<div class="table_cell"><?php echo __('Institution Site'); ?></div>
					<div class="table_cell cell_delete"></div>
				</div>
				
				<div class="table_body">
					<?php foreach($data['SecurityGroup']['sites'] as $index => $siteObj) { ?>
					<div class="table_row">
						<?php echo $this->Form->hidden('SecurityGroupInstitutionSite.'.$index.'.institution_site_id', array('class' => 'value_id', 'value' => $siteObj['institution_site_id'])); ?>
						<div class="table_cell"><?php echo $siteObj['institution_name']; ?></div>
						<div class="table_cell"><?php echo $siteObj['institution_site_name']; ?></div>
						<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
					</div>
					<?php } ?>
				</div>				
			</div>
			
			<div class="row" style="margin-left: 0;">
				<a class="void icon_plus" url="Security/groupsAddAccessOptions/sites" onclick="Security.addGroupAccessOptions(this)"><?php echo __('Add').' '.__('Institution Site'); ?></a>
			</div>
		</fieldset>
	</fieldset>
	
	<!--fieldset class="section_group" style="padding-bottom: 10px;">
		<legend>
			<span><?php echo __('Roles'); ?></span>
		</legend>
		
		<fieldset class="section_break">
			<legend><?php echo __('System Defined Roles'); ?></legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell"><?php echo __('Role'); ?></div>
					<div class="table_cell cell_users"><?php echo __('Users'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($data['SecurityRole']['system'] as $obj) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $obj['name']; ?></div>
						<div class="table_cell cell_users"><?php echo $obj['count']; ?></div>
					</div>
					<?php }?>
				</div>
			</div>
		</fieldset>
		
		<fieldset class="section_break">
			<legend>
				<span><?php echo __('User Defined Roles'); ?></span>
				<?php
				echo $this->Html->link(__('Manage'), array('action' => 'roles', $data['SecurityGroup']['id']), array('class' => 'divider'));
				?>
			</legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell"><?php echo __('Role'); ?></div>
					<div class="table_cell cell_users"><?php echo __('Users'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($data['SecurityRole']['user'] as $obj) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $obj['name']; ?></div>
						<div class="table_cell cell_users"><?php echo $obj['count']; ?></div>
					</div>
					<?php }?>
				</div>
			</div>
		</fieldset>
	</fieldset-->
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'groupsView', $data['SecurityGroup']['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>