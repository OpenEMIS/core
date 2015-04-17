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
		<div class="col-md-4"><?php echo $data['SecurityUser']['username']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('First Name'); ?></div>
		<div class="col-md-4"><?php echo $data['SecurityUser']['first_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Name'); ?></div>
		<div class="col-md-4"><?php echo $data['SecurityUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Last Login'); ?></div>
		<div class="col-md-4"><?php echo $this->Utility->formatDate($data['SecurityUser']['last_login']) . ' ' . date('H:i:s', strtotime($data['SecurityUser']['last_login'])); ?></div>
	</div>
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

<?php
$this->end();
?>