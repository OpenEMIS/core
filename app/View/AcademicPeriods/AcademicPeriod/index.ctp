<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', 'parent' => $parentId), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => $model, 'reorder', 'parent' => $parentId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../AcademicPeriods/controls');
echo $this->element('../AcademicPeriods/breadcrumbs');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.code'); ?></th>
				<th><?php echo $this->Label->get('general.period'); ?></th>
				<th><?php echo $this->Label->get('AcademicPeriodLevel.name'); ?></th>
				<th><?php echo $this->Label->get('general.action'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td>
					<?php
					if($obj['AcademicPeriodLevel']['level'] == $maxLevel) {
						echo $obj[$model]['name'];
					} else {
						echo $this->Html->link($obj[$model]['name'], array('action' => $model, 'parent' => $obj[$model]['id']));
					}
					?>
				</td>
				<td><?php echo $obj[$model]['code']; ?></td>
				<td><?php echo $obj['AcademicPeriod']['start_date'] . ' TO ' . $obj['AcademicPeriod']['end_date']; ?></td>
				<td><?php echo $obj['AcademicPeriodLevel']['name']; ?></td>
				<td class="center"><?php echo $this->Html->link($this->Icon->get('details'), array('action' => $model, 'view', 'parent' => $parentId, $obj[$model]['id']), array('escape' => false)); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
