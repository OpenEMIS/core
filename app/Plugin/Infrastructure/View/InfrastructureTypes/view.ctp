<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'index', 'category_id' => $data[$model]['infrastructure_category_id']), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $data[$model]['id']), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();
$this->start('contentBody');
echo $this->element('breadcrumbs');
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Category'); ?></div>
	<div class="col-md-6"><?php echo $category['InfrastructureCategory']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Visible'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['visible'] == 1 ? __('Yes') : __('No'); ?></div>
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
