<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $categoryId), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $id), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.category'); ?></div>
	<div class="col-md-6"><?php echo $data['InfrastructureCategory']['name']; ?></div>
</div>
<?php foreach($parentsInOrder AS $record): ?>
	<div class="row">
		<div class="col-md-3"><?php echo $record['parentCategory']; ?></div>
		<div class="col-md-6"><?php echo $record['parent']; ?></div>
	</div>
<?php endforeach; ?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.code'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.type'); ?></div>
	<div class="col-md-6"><?php echo $data['InfrastructureType']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.infrastructure_ownership_id'); ?></div>
	<div class="col-md-6"><?php echo $data['InfrastructureOwnership']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.year_acquired'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['year_acquired']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.year_disposed'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['year_disposed']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.comment'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['comment']; ?></div>
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
