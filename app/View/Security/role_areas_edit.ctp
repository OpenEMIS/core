<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="role_areas" class="content_wrapper edit">
	<?php
	echo $this->Form->create('SecurityRoleArea', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'roleAreasEdit', $roleId)
	));
	?>
	<h1>
		<span>Role - Area Restricted</span>
		<?php echo $this->Html->link(__('View'), array('action' => 'roleAreas', $roleId), array('class' => 'divider')); ?>
	</h1>
	<span class="none" id="url">rolesAdd</span>
	<span class="none" id="roleId"><?php echo $roleId; ?></span>
	<div class="row input role_select">
		<div class="label">Roles</div>
		<div class="value">		
			<?php
			echo $this->Form->input('role', array(
				'href' => $this->params['controller'] . '/' . $this->params['action'],
				'name' => 'role',
				'options' => $roleOptions,
				'default' => $roleId,
				'onchange' => 'security.switchRole(this)',
				'div' => false,
				'label' => false
			));
			?>
		</div>
	</div>
	
	<fieldset class="section_group" type="areas">
		<legend>Areas</legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_area">Level</div>
				<div class="table_cell">Area</div>
				<div class="table_cell cell_delete">&nbsp;</div>
			</div>
			
			<div class="table_body">
				<?php
				foreach($areaList as $i => $obj) {
					$fieldName = sprintf('data[SecurityRoleArea][%s][%%s]', ($i+1));
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden('order', array('id' => 'order', 'name' => 'order', 'value' => $i+1));
					echo $this->Form->hidden('role_id', array('name' => sprintf($fieldName, 'security_role_id'), 'value' => $roleId));
					echo $this->Form->hidden('area_id', array('class' => 'area_id', 'name' => sprintf($fieldName, 'area_id'), 'value' => $obj['area_id']));
					?>
					<div class="table_cell"><?php echo $obj['area_level_name']; ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
					<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php 
		if($_add) {
			echo $this->Utility->getAddRow('Area');
		} 
		?>
	</fieldset>
	
	<fieldset class="section_group" type="institutions">
		<legend>Institutions</legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_institution">Institution</div>
				<div class="table_cell">Institution Site</div>
				<div class="table_cell cell_delete">&nbsp;</div>
			</div>
			
			<div class="table_body">
				<?php
				foreach($siteList as $i => $obj) {
					$fieldName = sprintf('data[SecurityRoleInstitutionSite][%s][%%s]', ($i+1));
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden('order', array('id' => 'order', 'name' => 'order', 'value' => $i+1));
					echo $this->Form->hidden('role_id', array('name' => sprintf($fieldName, 'security_role_id'), 'value' => $roleId));
					echo $this->Form->hidden('institution_site_id', array(
						'class' => 'institution_site_id', 
						'name' => sprintf($fieldName, 'institution_site_id'), 
						'value' => $obj['institution_site_id']
					));
					?>
					<div class="table_cell"><?php echo $obj['institution_name']; ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
					<div class="table_cell"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></div>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php 
		if($_add) {
			echo $this->Utility->getAddRow('Institution');
		} 
		?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'roleAreas', $roleId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>