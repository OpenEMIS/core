<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', $selectedYear), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element("../InstitutionSites/$model/controls");
?>
	
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('EducationProgramme.name') ?></th>
					<th><?php echo $this->Label->get('EducationGrade.name') ?></th>
					<th><?php echo sprintf('%s (%s)', $this->Label->get('StudentFee.fee'), $currency) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $programmeOptions[$obj['EducationGrade']['education_programme_id']] ?></td>
					<td><?php echo $this->Html->link($obj['EducationGrade']['name'], array('action' => $model, 'view', $obj[$model]['id'], $selectedYear)) ?></td>
					<td class="cell-number"><?php echo $obj[$model]['total'] ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>

<?php $this->end(); ?>  
