<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
/*
if($_accessControl->check($this->params['controller'], 'groupsUsers')) {
	echo $this->Html->link(__('Users & Roles'), array('action' => 'groupsUsers', $data[model]['id']), array('class' => 'divider'));
}
*/
$this->end();

$this->start('contentBody');
echo $this->element('view');
?>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Area.title') ?></div>
	<div class="col-md-8">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.level') ?></th>
					<th><?php echo $this->Label->get('general.code') ?></th>
					<th><?php echo $this->Label->get('Area.name') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($areas as $area) : ?>
				<tr>
					<td><?php echo $levels[$area['Area']['area_level_id']] ?></td>
					<td><?php echo $area['Area']['code'] ?></td>
					<td><?php echo $area['Area']['name'] ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Institution.title') ?></div>
	<div class="col-md-8">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.code') ?></th>
					<th><?php echo $this->Label->get('InstitutionSite.name') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($institutions as $site) : ?>
				<tr>
					<td><?php echo $site['InstitutionSite']['code'] ?></td>
					<td><?php echo $site['InstitutionSite']['name'] ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<?php $this->end(); ?>
