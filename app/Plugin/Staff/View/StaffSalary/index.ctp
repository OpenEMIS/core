<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'show'), array('class' => 'divider'));
	if($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

<fieldset class="section_group">
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.date') ?></th>
					<th><?php echo $this->Label->get('StaffSalary.gross') ?></th>
					<th><?php echo $this->Label->get('StaffSalary.additions') ?></th>
					<th><?php echo $this->Label->get('StaffSalary.deductions') ?></th>
					<th><?php echo $this->Label->get('StaffSalary.net') ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td><?php echo $this->Html->link($this->Utility->formatDate($obj[$model]['salary_date'], null, false), array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
						<td><?php echo $obj[$model]['gross_salary'] ?></td>
						<td><?php echo $obj[$model]['additions'] ?></td>
						<td><?php echo $obj[$model]['deductions'] ?></td>
						<td><?php echo $obj[$model]['net_salary'] ?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</fieldset>

<?php $this->end() ?>
