<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId') ?></th>
				<th><?php echo $this->Label->get('general.name') ?></th>
				<th><?php echo $this->Label->get('general.type') ?></th>
				<th><?php echo $this->Label->get('general.status') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php 
			foreach ($data as $obj) :
				$idNo = $obj['Staff']['identification_no'];
			?>
				<tr>
					<td><?php echo $this->Html->link($idNo, array('action' => $model, 'index', $obj['Staff']['id'])) ?></td>
					<td><?php echo $this->Model->getname($obj['Staff']) ?></td>
					<td><?php echo $obj['StaffType']['name'] ?></td>
					<td><?php echo $obj['StaffStatus']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
