<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'account'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'accountEdit'));
echo $this->Form->create('SecurityUser', $formOptions);
echo $this->Form->hidden('id', array('value' => $data['SecurityUser']['id']));
?>
<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<?php
	echo $this->Form->input('username', array('value' => $data['SecurityUser']['username'], 'disabled' => 'disabled'));
	echo $this->Form->input('first_name', array('value' => $data['SecurityUser']['first_name']));
	echo $this->Form->input('last_name', array('value' => $data['SecurityUser']['last_name']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell"><?php echo __('Description'); ?></td>
					<td class="table_cell"><?php echo __('Value'); ?></td>
					<td class="table_cell"><?php echo __('Preferred'); ?></td>
				</tr>
			</thead>
			
			<tbody class="table_body">
				<?php foreach ($data['UserContact'] as $key => $value) { ?>
					<tr class="table_row">
						<td class="table_cell"><?php echo $value['ContactType']['name'] . ' - ' . $value['ContactType']['ContactOption']['name']; ?></td>
						<td class="table_cell"><?php echo $value['value']; ?></td>
						<td class="table_cell"><?php echo $this->Utility->checkOrCrossMarker($value['preferred']==1); ?></td>
						
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Groups'); ?></legend>
	<?php
	$tableHeaders = array(__('Group'), __('Role'));
	$tableData = array();
	foreach ($data['SecurityGroupUser'] as $key => $value) {
		$row = array();
		$row[] = $value['SecurityGroup']['name'];
		$row[] = $value['SecurityRole']['name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	?>
</fieldset>
<br />
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'account')));
echo $this->Form->end();
$this->end();
?>
