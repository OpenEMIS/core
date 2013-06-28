<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Group Users'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'groupsEdit', $data['SecurityGroup']['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('InstitutionSiteStudent', array(
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'programmesEdit')
	));
	?>
	
	<!--fieldset class="section_group" style="padding-bottom: 10px;">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Group Name'); ?></div>
			<div class="value"></div>
		</div>
	</fieldset-->
	
	<fieldset class="section_group" id="search_user">
		<legend><?php echo __('Search'); ?></legend>
		
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'value' => '',
						'class' => 'default',
						//'onkeypress' => 'InstitutionSiteProgrammes.doSearch(event)',
						'placeholder' => __('Identification No, First Name or Last Name')
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="Security/usersSearch/1/<?php echo $data['SecurityGroup']['id']; ?>" onClick="Security.usersSearch(this)"></span>
		</div>
		
		<div class="table_scrollable" url="Security/groupsAddUser/">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_role"><?php echo __('Role'); ?></div>
					<div class="table_cell cell_icon_action"></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="3">
				<div class="table">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Users'); ?></legend>
		
		<fieldset class="section_break">
			<legend>Group Admin</legend>
			
			<div class="table_scrollable scroll_active" url="InstitutionSites/programmesAddStudent/">
				<div class="table table_header">
					<div class="table_head">
						<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
						<div class="table_cell"><?php echo __('Name'); ?></div>
						<div class="table_cell cell_icon_action"></div>
					</div>
				</div>
				<div class="list_wrapper" style="height: 120px;" limit="4">
					<div class="table">
						<div class="table_body">
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
							<div class="table_row">
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
								<div class="table_cell">asd</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</fieldset>
	
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
	<?php echo $this->Form->end(); ?>
</div>