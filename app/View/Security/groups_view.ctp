<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Group Details'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Security', 'action' => 'usersAdd'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	?>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend>Group Information</legend>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name'); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend>Group Access</legend>
		<fieldset class="section_break">
			<legend>Areas</legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_area"><?php echo __('Level'); ?></div>
					<div class="table_cell"><?php echo __('Area'); ?></div>
				</div>
				
				<div class="table_body">
					<div class="table_row">
						<div class="table_cell">Province</div>
						<div class="table_cell">Bishan</div>
					</div>
				</div>
			</div>
		</fieldset>
		
		<fieldset class="section_break">
			<legend>Institution Sites</legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_institution"><?php echo __('Institution'); ?></div>
					<div class="table_cell"><?php echo __('Institution Site'); ?></div>
				</div>
				
				<div class="table_body">
					<div class="table_row">
						<div class="table_cell">Bishan Secondary School</div>
						<div class="table_cell">Bishan Campus 1</div>
					</div>
				</div>
			</div>
		</fieldset>
	</fieldset>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend>Group Roles</legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_area"><?php echo __('Roles'); ?></div>
				<div class="table_cell"><?php echo __('Modules'); ?></div>
			</div>
			
			<div class="table_body">
				<div class="table_row">
					<div class="table_cell">Group Administrator</div>
					<div class="table_cell">Institutions, Institution Sites</div>
				</div>
				<div class="table_row">
					<div class="table_cell">Teacher</div>
					<div class="table_cell">Teachers</div>
				</div>
				<div class="table_row">
					<div class="table_cell">Student</div>
					<div class="table_cell">Students</div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend>Group Users</legend>
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
				<div class="table_cell cell_area"><?php echo __('Name'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
				<div class="table_cell"><?php echo __('Status'); ?></div>
			</div>
			
			<div class="table_body">
				<div class="table_row">
					<div class="table_cell">Jeff Zheng</div>
					<div class="table_cell">Group Administrator</div>
					<div class="table_cell">Active</div>
				</div>
				<div class="table_row">
					<div class="table_cell">Adrian Lee</div>
					<div class="table_cell">Teacher</div>
					<div class="table_cell">Active</div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'groups'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>