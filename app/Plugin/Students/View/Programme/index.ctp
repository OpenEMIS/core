<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('Institution.name') ?></th>
				<th><?php echo $this->Label->get('EducationProgramme.name') ?></th>
				<th><?php echo $this->Label->get('date.start') ?></th>
				<th><?php echo $this->Label->get('date.end') ?></th>
				<th><?php echo $this->Label->get('general.status') ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			
			<tr>
				<td><?php echo $obj['InstitutionSite']['name'] ?></td>
				<td><?php echo $this->Html->link($obj['EducationProgramme']['name'], array('action' => $model, 'view', $obj['Programme']['id'])) ?></td>
				<td><?php echo $this->Utility->formatDate($obj['Programme']['start_date']) ?></td>
				<td><?php echo $this->Utility->formatDate($obj['Programme']['end_date']) ?></td>
				<td><?php echo $obj['StudentStatus']['name'] ?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
