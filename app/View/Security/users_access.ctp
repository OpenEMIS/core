<?php
echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Access'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'usersEdit', $data['id']), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'usersAccessAdd'));
echo $this->Form->create('SecurityUserAccess', $formOptions);

echo $this->Form->input('table_name', array(
	'class' => 'form-control',
	'options' => array('Student' => 'Student', 'Staff' => 'Staff'),
	'value' => 'Student',
	'onchange' => 'Security.updateModelForSearch(this)'
));

$labelOptions = $formOptions['inputDefaults']['label'];
$labelOptions['text'] = $this->Label->get('general.search');
$autocompleteArr = array(
	'labelOptions' => $labelOptions,
	'placeholder' => 'OpenEMIS ID or Name',
	'url' => $this->params['controller'] . '/autocomplete?model=Student'
);
echo $this->element('autocomplete_field', $autocompleteArr);
echo $this->Form->hidden('table_id', array(
	'class' => 'table_id',
	'id' => 'hiddenAutocompleteId'
));
echo $this->Form->hidden('security_user_id', array('value' => $data['id']));
?>
<div class="form-group">
	<div class="col-md-offset-4">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	</div>
</div>
<?php
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
					<?php echo $this->Html->link('<span class="icon_delete"></span>', array('action' => 'usersAccessDelete', $userId, $id, $name), array('escape' => false, 'onclick' => 'return jsForm.confirmDelete(this)'));?>

				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
<?php $this->end(); ?>