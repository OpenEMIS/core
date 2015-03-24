<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedTemplate)) {
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', 'template' => $selectedTemplate), array('class' => 'divider'));
	}
	if ($_edit) {
	    echo $this->Html->link($this->Label->get('general.reorder'), array('action' => $model, 'reorder', 'template' => $selectedTemplate), array('class' => 'divider'));
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
				<th><?php echo $this->Label->get('RubricSection.rubric_template_id'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Html->link($obj['RubricSection']['name'], array('action' => $model, 'view', $obj['RubricSection']['id'])); ?></td>
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
