<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Group Users'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'groupsView', $group['id']), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'groupsUsers', $group['id'], 'edit'), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'groups');
$this->assign('contentClass', 'edit');

$this->start('contentBody');
?>
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
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered <?php echo count($obj['users']) > 4 ? 'scroll_active' : ''; ?>">
			<thead class="table table_header">
				<tr class="table_head">
					<td class="table_cell cell_id_no"><?php echo __('Identification No'); ?></td>
					<td class="table_cell"><?php echo __('First Name'); ?></td>
					<td class="table_cell"><?php echo __('Last Name'); ?></td>
				</tr>
			</thead>
			<tbody class="table_body">
				<?php foreach($obj['users'] as $user) { ?>
				<tr class="table_row" tags="<?php echo implode(',', array(strtolower($user['identification_no']), strtolower($user['first_name']), strtolower($user['last_name']))); ?>">
					<td class="table_cell cell_id_no"><?php echo $user['identification_no']; ?></td>
					<td class="table_cell"><?php echo $user['first_name']; ?></td>
					<td class="table_cell"><?php echo $user['last_name']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</fieldset>
	<?php } ?>
</fieldset>
<?php $this->end(); ?>