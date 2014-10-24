<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentBody');
?>
	
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.date') ?></th>
				<th><?php echo $this->Label->get('general.title') ?></th>
				<th><?php echo $this->Label->get('general.category') ?></th>
				<th><?php echo $this->Label->get('InstitutionSite.name') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Utility->formatDate($obj[$model]['date_of_behaviour']) ?></td>
					<td><?php echo $this->Html->link($obj[$model]['title'], array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
					<td><?php echo $obj['StaffBehaviourCategory']['name'] ?></td>
					<td><?php echo $obj['InstitutionSite']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
