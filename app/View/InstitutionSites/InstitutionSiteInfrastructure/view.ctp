<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $commonFieldsData['InstitutionSiteInfrastructure']['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteInfrastructure', 'index', $categoryId), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'InstitutionSiteInfrastructure', 'edit', $id), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'InstitutionSiteInfrastructure', 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.category'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InfrastructureCategory']['name']; ?></div>
</div>
<?php foreach($parentsInOrder AS $record): ?>
	<div class="row">
		<div class="col-md-3"><?php echo $record['parentCategory']; ?></div>
		<div class="col-md-6"><?php echo $record['parent']; ?></div>
	</div>
<?php endforeach; ?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.code'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.type'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InfrastructureType']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.infrastructure_ownership_id'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InfrastructureOwnership']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.year_acquired'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['year_acquired']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.year_disposed'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['year_disposed']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteInfrastructure.infrastructure_condition_id'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InfrastructureCondition']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.comment'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['comment']; ?></div>
</div>

<?php echo $this->element('customfields/index', compact('model', 'modelOption', 'modelRow', 'modelColumn', 'action')); ?>

<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($commonFieldsData['ModifiedUser']['first_name'] . ' ' . $commonFieldsData['ModifiedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($commonFieldsData['CreatedUser']['first_name'] . ' ' . $commonFieldsData['CreatedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $commonFieldsData['InstitutionSiteInfrastructure']['created']; ?></div>
</div>
<?php
$this->end();
?>
