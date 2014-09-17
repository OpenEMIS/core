<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
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

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'usersAccess'));
echo $this->Form->create('SecurityUserAccess', $formOptions);

echo $this->Form->hidden('security_user_id', array('value' => $data['id']));
echo $this->Form->hidden('table_id', array('id' => 'TableId', 'value' => 0));

echo $this->Form->input('table_name', array(
	'id' => 'module',
	'class' => 'module',
	'options' => $moduleOptions,
	'onchange' => '$("#search .table_body").empty()'
));

$labelOptions = $formOptions['inputDefaults']['label'];
$labelOptions['text'] = $this->Label->get('general.search');
$autocompleteArr = array(
	'labelOptions' => $labelOptions, 
	'placeholder' => 'OpenEMIS ID or Name', 
	'url' => $this->params['controller'] . '/Security/autocomplete'
);
echo $this->element('autocomplete_field', $autocompleteArr);

echo $this->Form->end();
?>

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
<?php $this->end(); ?>