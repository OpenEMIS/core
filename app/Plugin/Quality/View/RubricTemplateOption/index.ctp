<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedRubricTemplate)) {
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', 'template' => $selectedRubricTemplate), array('class' => 'divider'));
	}
}
$this->end();

$this->start('contentBody');
echo $this->element('/../../Plugin/Quality/View/QualityRubrics/nav_tabs');
echo $this->element('/../../Plugin/Quality/View/QualityRubrics/controls');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplateOption.weighting'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplateOption.color'); ?></th>
				<th><?php echo $this->Label->get('RubricTemplateOption.rubric_template_id'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Html->link($obj['RubricTemplateOption']['name'], array('action' => $model, 'view', $obj['RubricTemplateOption']['id'])); ?></td>
						<td><?php echo $obj['RubricTemplateOption']['weighting']; ?></td>
						<td><?php echo $obj['RubricTemplateOption']['color']; ?></td>
						<td><?php echo $obj['RubricTemplate']['name']; ?></td>
					</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
