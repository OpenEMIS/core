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
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'groupsUsers', $group['id'], 'edit'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
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
				<span class="icon_clear" onClick="Security.cancelUsersSearchOnPage(this)">X</span>
			</div>
			<span class="left icon_search" onClick="Security.usersSearchOnPage(this)"></span>
		</div>
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
					</div>
				</div>
				<div class="list_wrapper" limit="4" style="max-height: 98px">
					<div class="table">
						<div class="table_body">
							<?php foreach($obj['users'] as $user) { ?>
							<div class="table_row" tags="<?php echo implode(',', array(strtolower($user['identification_no']), strtolower($user['first_name']), strtolower($user['last_name']))); ?>">
								<div class="table_cell cell_id_no"><?php echo $user['identification_no']; ?></div>
								<div class="table_cell"><?php echo $user['first_name']; ?></div>
								<div class="table_cell"><?php echo $user['last_name']; ?></div>
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