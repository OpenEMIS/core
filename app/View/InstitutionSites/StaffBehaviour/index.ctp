<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	$named = $this->params['named'];
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'show'), array('class' => 'divider'));
	if($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array_merge(array('action' => $model, 'add'), $named), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

<fieldset class="section_group">
	<legend><?php echo $this->Model->getName($staff['Staff']) ?></legend>
	
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
						<td><?php echo $this->Html->link($obj[$model]['title'], array_merge(array('action' => $model, 'view', $obj[$model]['id']), $named)) ?></td>
						<td><?php echo $obj['StaffBehaviourCategory']['name'] ?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</fieldset>

<?php $this->end() ?>
