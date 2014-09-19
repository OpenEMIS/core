<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', $_condition => $conditionId), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'reorder', $model, $_condition => $conditionId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../Education/controls');
echo $this->element('../Education/breadcrumbs');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.code'); ?></th>
				<th class="cell-action"><?php echo $this->Label->get('general.action'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'EducationGradeSubject', Inflector::underscore($model.'Id') => $obj[$model]['id'])); ?></td>
				<td><?php echo $obj[$model]['code']; ?></td>
				<td class="center"><?php echo $this->Html->link($this->Icon->get('details'), array('action' => $model, 'view', $_condition => $conditionId, $obj[$model]['id']), array('escape' => false)); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
