<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Access'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'usersEdit', $data['id']), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php
echo $this->Form->create('SecurityUserAccess', array(
	'url' => array('controller' => 'Security', 'action' => 'usersAccess'),
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
));
echo $this->Form->hidden('security_user_id', array('value' => $data['id']));
echo $this->Form->hidden('table_id', array('id' => 'TableId', 'value' => 0));
?>

<fieldset class="section_group" id="search">
	<legend><?php echo __('Search'); ?></legend>

	<div class="row">
		<?php
		echo $this->Form->input('table_name', array(
			'id' => 'module',
			'class' => 'module',
			'options' => $moduleOptions,
			'onchange' => '$("#search .table_body").empty()'
		));
		?>
	</div>

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
		<span class="left icon_search" url="Security/usersSearch/2" onClick="Security.usersSearch(this)"></span>
	</div>

	<div class="table_scrollable">
		<div class="list_wrapper hidden" limit="4" style="height: 109px;">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
				<th class="cell_id_no"><?php echo __('Identification No'); ?></th>
				<th><?php echo __('First Name'); ?></th>
				<th><?php echo __('Last Name'); ?></th>
				<th class="cell_icon_action"></th>
				</tr>
			</thead>
		
			<tbody class="table_body"></tbody>
		
		</table>
			</div>
	</div>
</fieldset>

<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th style="width: 200px;"><?php echo __('OpenEMIS ID'); ?></th>
			<th><?php echo __('Name'); ?></th>
			<th class="cell_module"><?php echo __('Module'); ?></th>
			<th class="cell_icon_action"></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($data['access'] as $row) {
			$obj = $row['SecurityUserAccess'];
			$userId = $obj['security_user_id'];
			$id = $obj['table_id'];
			$name = $obj['table_name'];
			$params = sprintf('%s/%s/%s/', $userId, $id, $name);
			?>
			<tr>
				<td><?php echo $obj['identification_no']; ?></td>
				<td><?php echo $obj['name']; ?></td>
				<td><?php echo $obj['table_name']; ?></td>
				<td>
					<span class="icon_delete" url="Security/usersDeleteAccess/<?php echo $params; ?>" onclick="Security.removeAccessUser(this)"></span>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>