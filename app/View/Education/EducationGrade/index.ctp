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
				<th class="center"><?php echo $this->Label->get('general.code'); ?></th>
				<th class="center">Number of Subjects</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<?php 
				$subjects = array();
				foreach($obj['EducationGradeSubject'] as $o){
					$subjects[] = $o['EducationSubject']['name'];
				}
				$subjects = implode(', ', $subjects);
			?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => $model, 'view', $_condition => $conditionId, $obj[$model]['id'])); ?></td>
				<td class="center"><?php echo $obj[$model]['code']; ?></td>
				<td class="center toolTip"><?php echo $this->Html->link(count($obj['EducationGradeSubject']), array('action' => $model, 'view', $_condition => $conditionId, $obj[$model]['id']), array('title' => $subjects, 'data-toggle' => 'tooltip')); ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
