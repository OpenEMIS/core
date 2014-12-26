<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alerts'));

$this->start('contentActions');
$model = 'Alert';
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'index'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $data[$model]['id']), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');
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
		<?php
		if (isset($data['SecurityRole'])) {
			foreach ($data['SecurityRole'] as $role) {
				echo '<p>' . $role['name'] . '</p>';
			}
		}
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
