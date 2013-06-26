<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

echo $this->Html->script('Security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="groups" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Group Details'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'groupsView', $data['SecurityGroup']['id']), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityGroup', array(
		'url' => array('controller' => 'Security', 'action' => 'groupsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	?>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Group Name'); ?></div>
			<div class="value">
				<?php 
				echo $this->Form->input('name', array(
					'class' => 'default',
					'value' => $data['SecurityGroup']['name']
				));
				?>
			</div>
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
					<?php foreach($data['SecurityGroup']['sites'] as $siteObj) { ?>
					<div class="table_row">
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
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
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
						<div class="table_cell cell_users">
							<?php echo $this->Html->link(__('Users'), array('action' => 'roleUsers', $data['SecurityGroup']['id'], $obj['id'])); ?>
						</div>
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
						<div class="table_cell cell_users">
							<?php echo $this->Html->link(__('Users'), array('action' => 'roleUsers', $data['SecurityGroup']['id'], $obj['id'])); ?>
						</div>
					</div>
					<?php }?>
				</div>
			</div>
		</fieldset>
	</fieldset>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend>
			<span><?php echo __('Users'); ?></span>
			<?php
			echo $this->Html->link(__('Manage'), array('action' => 'groupsView', $data['SecurityGroup']['id']), array('class' => 'divider'));
			?>
		</legend>
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'label' => false,
						'div' => false,
						'value' => '',
						'class' => 'default',
						'onkeypress' => 'InstitutionSiteProgrammes.doSearch(event)',
						'placeholder' => __('Search User')
					));
				?>
				<span class="icon_clear" onClick="InstitutionSiteProgrammes.clearSearch(this)">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/studentsSearch?master" onClick="InstitutionSiteProgrammes.search(this)"></span>
		</div>
		
		<div class="table allow_hover">
			<div class="table_head">
				<div class="table_cell" style="width: 180px;"><?php echo __('Name'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
				<div class="table_cell" style="width: 90px;"><?php echo __('Status'); ?></div>
			</div>
			
			<div class="table_body">
				<div class="table_row">
					<div class="table_cell">Jeff Zheng</div>
					<div class="table_cell">Group Administrator</div>
					<div class="table_cell center">Active</div>
				</div>
				<div class="table_row">
					<div class="table_cell">Adrian Lee</div>
					<div class="table_cell">Teacher</div>
					<div class="table_cell center">Active</div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'groups'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>