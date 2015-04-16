<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'show', $selectedAcademicPeriod, $selectedClass), array('class' => 'divider'));
	if($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

<fieldset class="section_group">
	<legend><?php echo $this->Model->getName($student['SecurityUser']) ?></legend>
	
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.date') ?></th>
					<th><?php echo $this->Label->get('general.title') ?></th>
					<th><?php echo $this->Label->get('general.category') ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Utility->formatDate($obj[$model]['date_of_behaviour']) ?></td>
						<td><?php echo $this->Html->link($obj[$model]['title'], array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
						<td><?php echo $obj['StudentBehaviourCategory']['name'] ?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</fieldset>

<?php $this->end() ?>
