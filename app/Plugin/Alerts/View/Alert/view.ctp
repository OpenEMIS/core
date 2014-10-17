<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => $model), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $id), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();
$this->start('contentBody');
//echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Threshold'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['threshold']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['status'] == 1 ? __('Active') : __('Inactive'); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Method'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['method']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Subject'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['subject']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Message'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['message']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Destination') . ' ' . __('Roles'); ?></div>
	<div class="col-md-6">
		<?php foreach ($roles AS $role):
			?>
			<p><?php echo $role; ?></p>
			<?php
		endforeach;
		?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['created']; ?></div>
</div>
<?php
$this->end();
?>
