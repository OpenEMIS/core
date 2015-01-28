<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $data[$model]['academic_period_id']), array('class' => 'divider'));
	if ($_edit) {
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove', $data[$model]['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
?>

<fieldset class="section_break">
	<legend><?php echo __('Section') ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('AcademicPeriod.name') ?></div>
		<div class="col-md-6"><?php echo $data['AcademicPeriod']['name'] ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.section') ?></div>
		<div class="col-md-6"><?php echo $data[$model]['name'] ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteClass.shift') ?></div>
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
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Students') ?></legend>
	<div class="row">
		<div class="table-responsive">
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
			<?php foreach($studentsData as $obj) : ?>
					<tr>
						<td><?php echo $obj['Student']['identification_no'] ?></td>
						<td><?php echo ModelHelper::getName($obj['Student']) ?></td>
						<td><?php echo $obj['Student']['gender'] ?></td>
						<td><?php echo $obj['Student']['date_of_birth'] ?></td>
						<td><?php echo $obj['StudentCategory']['name'] ?></td>
					</tr>
			<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
</fieldset>
<?php
$this->end();
?>
