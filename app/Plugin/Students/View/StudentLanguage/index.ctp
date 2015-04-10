<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>
	
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get($model.'.evaluation_date') ?></th>
				<th><?php echo $this->Label->get($model.'.language_id') ?></th>
				<th><?php echo $this->Label->get($model.'.listening') ?></th>
				<th><?php echo $this->Label->get($model.'.speaking') ?></th>
				<th><?php echo $this->Label->get($model.'.reading') ?></th>
				<th><?php echo $this->Label->get($model.'.writing') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Utility->formatDate($obj[$model]['evaluation_date']) ?>
					<td><?php echo $this->Html->link($obj['Language']['name'], array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
					<td><?php echo $obj[$model]['listening'] ?></td>
					<td><?php echo $obj[$model]['speaking'] ?></td>
					<td><?php echo $obj[$model]['reading'] ?></td>
					<td><?php echo $obj[$model]['writing'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>