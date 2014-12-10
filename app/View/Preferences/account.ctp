<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'accountEdit'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<fieldset class="section_break">
	<legend><?php echo __('General'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Username'); ?></div>
		<div class="col-md-4"><?php echo $obj['SecurityUser']['username']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-4"><?php echo $obj['SecurityUser']['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-4"><?php echo $obj['SecurityUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Login'); ?></div>
		<div class="col-md-4"><?php echo $this->Utility->formatDate($obj['SecurityUser']['last_login']) . ' ' . date('H:i:s', strtotime($obj['SecurityUser']['last_login'])); ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Telephone'); ?></div>
		<div class="col-md-4"><?php echo!is_null($obj['SecurityUser']['telephone']) ? $obj['SecurityUser']['telephone'] : ''; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Email'); ?></div>
		<div class="col-md-4"><?php echo!is_null($obj['SecurityUser']['email']) ? $obj['SecurityUser']['email'] : ''; ?></div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Groups'); ?></legend>
	<?php
	$tableHeaders = array(__('Group'), __('Role'));
	$tableData = array();
	foreach ($obj['groups'] as $group) {
		$row = array();
		$row[] = $group['security_group_name'];
		$row[] = $group['security_role_name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	?>
</fieldset>

<?php
$this->end();
?>