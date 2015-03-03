<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $data[$model]['academic_period_id']), array('class' => 'divider'));
	if ($_edit) {
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
?>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('AcademicPeriod.name') ?></div>
	<div class="col-md-6"><?php echo $data['AcademicPeriod']['name'] ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteSection.name') ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name'] ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteSection.institution_site_shift_id') ?></div>
	<div class="col-md-6"><?php echo $data['InstitutionSiteShift']['name'] ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('EducationGrade.name') ?></div>
	<div class="col-md-6">
	<?php
	if (!empty($data['EducationGrade']['name'])) {
		echo $data['EducationGrade']['name'];
	} else {
		foreach($grades as $g) {
			echo $g . '<br />';
		}
	}
	?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteSection.staff_id') ?></div>
	<div class="col-md-6"><?php echo ModelHelper::getName($data['Staff']) ?></div>
</div>

<div class="row">
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo __('Students') ?></div>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId') ?></th>
					<th><?php echo $this->Label->get('general.name') ?></th>
					<th><?php echo $this->Label->get('general.sex') ?></th>
					<th><?php echo $this->Label->get('general.date_of_birth') ?></th>
					<th><?php echo $this->Label->get('general.category') ?></th>
				</tr>
			</thead>

			<tbody>
				<?php 
				foreach($data['InstitutionSiteSectionStudent'] as $obj) : 
					if ($obj['status'] == 0) continue;
				?>
						<tr>
							<td><?php echo $obj['Student']['SecurityUser']['openemis_no'] ?></td>
							<td><?php echo ModelHelper::getName($obj['Student']['SecurityUser']) ?></td>
							<td><?php echo $obj['Student']['SecurityUser']['Gender']['name'] ?></td>
							<td><?php echo $obj['Student']['SecurityUser']['date_of_birth'] ?></td>
							<td><?php echo $categoryOptions[$obj['student_category_id']] ?></td>
						</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<?php
$this->end();
?>
