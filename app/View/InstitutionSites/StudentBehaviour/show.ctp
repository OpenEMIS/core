<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentBody');
echo $this->element('../InstitutionSites/StudentBehaviour/controls');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId') ?></th>
				<th><?php echo $this->Label->get('general.name') ?></th>
				<th><?php echo $this->Label->get('general.grade') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php 
			foreach ($data as $obj) : 
				$idNo = $obj['Student']['identification_no'];
			?>
				<tr>
					<td><?php echo $this->Html->link($idNo, array('action' => $model, 'index', $obj['Student']['id'])) ?></td>
					<td><?php echo $this->Model->getName($obj['Student']) ?></td>
					<td><?php echo $obj['EducationGrade']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
