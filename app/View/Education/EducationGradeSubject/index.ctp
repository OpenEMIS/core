<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $_condition => $conditionId), array('class' => 'divider'));
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
				<th><?php echo $this->Label->get('general.name') ?></th>
				<th><?php echo $this->Label->get('general.code') ?></th>
				<th class="cell-hours-required"><?php echo $this->Label->get('EducationGradeSubject.hours_required') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['EducationSubject']['name'] ?></td>
				<td><?php echo $obj['EducationSubject']['code'] ?></td>
				<td class="cell-number"><?php echo $obj[$model]['hours_required'] ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
