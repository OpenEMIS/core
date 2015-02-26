<?php
echo $this->Html->script('security', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('User Access'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'usersEdit', $data['SecurityUser']['id']), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'SecurityUserAccess', 'add'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('SecurityUserAccess', $formOptions);

$labelOptions['text'] = $this->Label->get('general.model');
echo $this->Form->input('table_name', array(
	'class' => 'form-control',
	'label' => $labelOptions,
	'options' => array('Student' => 'Student', 'Staff' => 'Staff'),
	'value' => 'Student',
	'onchange' => 'Security.updateModelForSearch(this)'
));

$labelOptions['text'] = $this->Label->get('general.search');
$autocompleteArr = array(
	'labelOptions' => $labelOptions,
	'placeholder' => 'OpenEMIS ID or Name',
	'url' => $this->params['controller'] . '/SecurityUserAccess/autocomplete?model=Student'
);
echo $this->element('autocomplete_field', $autocompleteArr);
echo $this->Form->hidden('table_id', array(
	'autocomplete' => 'table_id',
	'id' => 'hiddenAutocompleteId'
));
echo $this->Form->hidden('security_user_id', array('value' => $data['SecurityUser']['id']));
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
		
		foreach ($data['SecurityUserAccess'] as $key => $value) {
			$userObj = $value['SecurityUser'];
			$userId = $value['security_user_id'];
			$id = $value['table_id'];
			$tableName = $value['table_name'];
			$params = sprintf('%s/%s/%s/', $userId, $id, $tableName);
			?>
			<tr>
				<td><?php echo $userObj['openemis_no']; ?></td>
				<td><?php echo $this->Model->getName($userObj); ?></td>
				<td><?php echo $tableName; ?></td>
				<td>
					<?php echo $this->Html->link('<span class="icon_delete"></span>', array('action' => 'SecurityUserAccess', 'delete',$userId, $id, $tableName), array('escape' => false, 'onclick' => 'return jsForm.confirmDelete(this)')); ?>

				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
<?php $this->end(); ?>