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
		echo $this->Html->link(__('Back'), array('action' => 'groupsView', $group['id']), array('class' => 'divider'));
		echo $this->Html->link(__('View'), array('action' => 'groupsUsers', $group['id']), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	
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
						'label' => false,
						'div' => false,
						'class' => 'default',
						'placeholder' => __('Identification No, First Name or Last Name')
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="Security/usersSearch/1/<?php echo $group['id']; ?>" onClick="Security.usersSearch(this)"></span>
		</div>
		
		<?php
		echo $this->Form->create('SecurityGroupUser', array(
			'inputDefaults' => array('label' => false, 'div' => false),
			'url' => array('controller' => 'Security', 'action' => 'groupsUserAdd')
		));
		echo $this->Form->hidden('security_group_id', array('value' => $group['id']));
		echo $this->Form->hidden('security_role_id', array('id' => 'SecurityRoleId'));
		echo $this->Form->hidden('security_user_id', array('id' => 'SecurityUserId'));
		?>
		
		<div class="table_scrollable" url="Security/groupsAddUser/">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_role"><?php echo __('Role'); ?></div>
					<div class="table_cell cell_icon_action"></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="2">
				<div class="table">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
		
		<?php echo $this->Form->end(); ?>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Users & Roles'); ?></legend>
		
		<?php foreach($data as $roleId => $obj) { ?>
		<fieldset class="section_break">
			<legend><?php echo $obj['name']; ?> (<span class="user_count"><?php echo count($obj['users']) ; ?></span>)</legend>
			
			<div class="table_scrollable group_user_list <?php echo count($obj['users']) > 4 ? 'scroll_active' : ''; ?>">
				<div class="table table_header">
					<div class="table_head">
						<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
						<div class="table_cell"><?php echo __('First Name'); ?></div>
						<div class="table_cell"><?php echo __('Last Name'); ?></div>
						<div class="table_cell cell_icon_action"></div>
					</div>
				</div>
				<div class="list_wrapper" limit="4" style="max-height: 109px;">
					<div class="table">
						<div class="table_body">
							<?php foreach($obj['users'] as $user) { ?>
							<div class="table_row">
								<div class="table_cell cell_id_no"><?php echo $user['identification_no']; ?></div>
								<div class="table_cell"><?php echo $user['first_name']; ?></div>
								<div class="table_cell"><?php echo $user['last_name']; ?></div>
								<div class="table_cell cell_icon_action">
									<span class="icon_delete" url="<?php echo sprintf('Security/groupsUserRemove/%d/%d/%d', $group['id'], $roleId, $user['id']); ?>" onClick="Security.removeGroupUser(this)"></span>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
		<?php } ?>
	</fieldset>
</div>