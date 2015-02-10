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

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('AcademicPeriod.name'); ?></div>
	<div class="col-md-6"><?php echo $data['AcademicPeriod']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteSection.name'); ?></div>
	<div class="col-md-6">
	<?php
	if (!empty($data['InstitutionSiteSectionClass'])) {
		foreach($data['InstitutionSiteSectionClass'] as $section) {
			echo $section['InstitutionSiteSection']['name'] . '<br>';
			break;
		}
	}
	?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteClass.name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('EducationSubject.code'); ?></div>
	<div class="col-md-6"><?php echo !empty($data['EducationSubject']['code']) ? $data['EducationSubject']['code'] : ''; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('EducationSubject.name'); ?></div>
	<div class="col-md-6"><?php echo !empty($data['EducationSubject']['name']) ? $data['EducationSubject']['name'] : ''; ?></div>
</div>

<div class="row">
	<div class="panel panel-default">
		<div class="panel-heading dark-background"><?php echo __('Teachers') ?></div>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId') ?></th>
					<th><?php echo $this->Label->get('general.name') ?></th>
				</tr>
			</thead>

			<tbody>
				<?php 
				foreach($data['InstitutionSiteClassStaff'] as $obj) : 
					if ($obj['status'] == 0) continue;
				?>
						<tr>
							<td><?php echo $obj['Staff']['identification_no'] ?></td>
							<td><?php echo ModelHelper::getName($obj['Staff']) ?></td>
						</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div class="panel panel-default">
		<div class="panel-heading dark-background"><?php echo __('Students') ?></div>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId') ?></th>
					<th><?php echo $this->Label->get('general.name') ?></th>
					<th><?php echo $this->Label->get('general.sex') ?></th>
					<th><?php echo $this->Label->get('general.date_of_birth') ?></th>
					<!--th><?php echo $this->Label->get('general.category') ?></th-->
				</tr>
			</thead>

			<tbody>
				<?php 
				foreach($data['InstitutionSiteClassStudent'] as $obj) : 
					if ($obj['status'] == 0) continue;
				?>
						<tr>
							<td><?php echo $obj['Student']['identification_no'] ?></td>
							<td><?php echo ModelHelper::getName($obj['Student']) ?></td>
							<td><?php echo $obj['Student']['gender'] ?></td>
							<td><?php echo $obj['Student']['date_of_birth'] ?></td>
							<!--td><?php echo $categoryOptions[$obj['student_category_id']] ?></td-->
						</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>

<?php
$this->end();
?>
