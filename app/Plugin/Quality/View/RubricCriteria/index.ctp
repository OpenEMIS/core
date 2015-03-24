<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedSection)) {
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', 'template' => $selectedTemplate, 'section' => $selectedSection), array('class' => 'divider'));
	}
	if ($_edit) {
	    echo $this->Html->link($this->Label->get('general.reorder'), array('action' => $model, 'reorder', 'template' => $selectedTemplate, 'section' => $selectedSection), array('class' => 'divider'));
	}
	echo $this->Html->link($this->Label->get('general.preview'), array('action' => $model, 'preview', 'template' => $selectedTemplate, 'section' => $selectedSection), array('class' => 'divider'));
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
				<th><?php echo $this->Label->get('RubricCriteria.type'); ?></th>
				<th><?php echo $this->Label->get('RubricCriteria.rubric_section_id'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Html->link($obj['RubricCriteria']['name'], array('action' => $model, 'view', $obj['RubricCriteria']['id'])); ?></td>
						<td><?php echo $criteriaTypeOptions[$obj['RubricCriteria']['type']]; ?></td>
						<td><?php echo $obj['RubricSection']['name']; ?></td>
					</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
