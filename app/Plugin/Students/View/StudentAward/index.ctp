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
				<th><?php echo $this->Label->get($model.'.issue_date') ?></th>
				<th><?php echo $this->Label->get($model.'.award') ?></th>
				<th><?php echo $this->Label->get($model.'.issuer') ?></th>
				<th><?php echo $this->Label->get($model.'.comment') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Utility->formatDate($obj[$model]['issue_date']) ?></td>
					<td><?php echo $this->Html->link($obj[$model]['award'], array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
					<td><?php echo $obj[$model]['issuer'] ?></td>
					<td><?php echo $obj[$model]['comment'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>