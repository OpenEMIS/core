<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="role_areas" class="content_wrapper">
	<?php
	echo $this->Form->create('SecurityRoleArea', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'roleAreas')
	));
	?>
	<h1>
		<span><?php echo __('Role - Area Restricted'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'roleAreasEdit', $roleId), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php echo $this->element('alert'); ?>
	
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
	
	<fieldset class="section_group">
		<legend><?php echo __('Areas'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_area"><?php echo __('Level'); ?></div>
				<div class="table_cell"><?php echo __('Area'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($areaList as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['area_level_name']; ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Institutions'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_institution"><?php echo __('Institution'); ?></div>
				<div class="table_cell"><?php echo __('Institution Site'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($siteList as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['institution_name']; ?></div>
					<div class="table_cell"><?php echo $obj['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<div class="row">
		<?php echo $this->Html->link('<span>&laquo;</span> ' . __('Back to Roles'), 
			array('action' => 'roles'),
			array('escape' => false, 'class' => 'link_back')
		); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>