<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Group Details'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'groupsEdit', $data['SecurityGroup']['id']), array('class' => 'divider'));
		}
		if($_accessControl->check($this->params['controller'], 'groupsUsers')) {
			echo $this->Html->link(__('Users & Roles'), array('action' => 'groupsUsers', $data['SecurityGroup']['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $data['SecurityGroup']['name']; ?></div>
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
				</div>
				
				<div class="table_body">
					<?php foreach($data['SecurityGroup']['areas'] as $areaObj) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $areaObj['area_level_name']; ?></div>
						<div class="table_cell"><?php echo $areaObj['area_name']; ?></div>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset>
		
		<fieldset class="section_break">
			<legend><?php echo __('Institution Sites'); ?></legend>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_institution"><?php echo __('Institution'); ?></div>
					<div class="table_cell"><?php echo __('Institution Site'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($data['SecurityGroup']['sites'] as $siteObj) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $siteObj['institution_name']; ?></div>
						<div class="table_cell"><?php echo $siteObj['institution_site_name']; ?></div>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset>
	</fieldset>
	
	<!--fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Roles'); ?></legend>
		
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
			<legend><?php echo __('User Defined Roles'); ?></legend>
			
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
	
	<!--
	<fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Users'); ?></legend>
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
	-->
</div>