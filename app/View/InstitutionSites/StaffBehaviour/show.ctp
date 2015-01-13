<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => $model, 'excel'), array('class' => 'divider'));
	}
$this->end();

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
				$firstName = $obj['Staff']['first_name'];
				$middleName = $obj['Staff']['middle_name'];
				$lastName = $obj['Staff']['last_name'];
				$fullName = trim($firstName . ' ' . $middleName) . ' ' . $lastName;
			?>
				<tr>
					<td><?php echo $this->Html->link($idNo, array('action' => $model, 'index', $obj['Staff']['id'])) ?></td>
					<td><?php echo trim($fullName) ?></td>
					<td><?php echo $obj['StaffType']['name'] ?></td>
					<td><?php echo $obj['StaffStatus']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
