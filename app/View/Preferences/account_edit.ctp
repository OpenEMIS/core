<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'account'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'accountEdit'));
echo $this->Form->create('SecurityUser', $formOptions);
echo $this->Form->hidden('id', array('value' => $data['id']));
?>
<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<?php
	echo $this->Form->input('username', array('value' => $data['username'], 'disabled' => 'disabled'));
	echo $this->Form->input('first_name', array('value' => $data['first_name']));
	echo $this->Form->input('last_name', array('value' => $data['last_name']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php
	echo $this->Form->input('telephone', array('value' => $data['telephone']));
	echo $this->Form->input('email', array('value' => $data['email']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Groups'); ?></legend>
	<?php
	$tableHeaders = array(__('Group'), __('Role'));
	$tableData = array();
	foreach ($data['groups'] as $group) {
		$row = array();
		$row[] = $group['security_group_name'];
		$row[] = $group['security_role_name'];
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
