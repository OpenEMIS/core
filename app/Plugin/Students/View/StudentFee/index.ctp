<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentBody');
?>
	
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.year') ?></th>
					<th><?php echo $this->Label->get($model . '.programme') . ' - ' . $this->Label->get($model . '.grade') ?></th>
					<th style="width: 110px;"><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.fees'), $currency) ?></th>
					<th style="width: 110px;"><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.paid'), $currency) ?></th>
					<th style="width: 130px;"><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.outstanding'), $currency) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data as $obj) : ?>
				<?php
					$grade = $obj['InstitutionSiteFee']['EducationGrade']['EducationProgramme']['name'] . ' - ' . $obj['InstitutionSiteFee']['EducationGrade']['name'];
				?>
				<tr>
					<td><?php echo $obj['InstitutionSiteFee']['SchoolYear']['name'] ?></td>
					<td><?php echo $this->Html->link($grade, array('action' => $model, 'view', $obj[$model]['student_id'], $obj['InstitutionSiteFee']['id'])) ?></td>
					<td class="cell-number"><?php echo $obj['InstitutionSiteFee']['total'] ?></td>
					<td class="cell-number"><?php echo $obj[0]['paid'] ?></td>
					<td class="cell-number"><?php echo number_format($obj['InstitutionSiteFee']['total'] - $obj[0]['paid'], 2) ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>

<?php $this->end(); ?>  
