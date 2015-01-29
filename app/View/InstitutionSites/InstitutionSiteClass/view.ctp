<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $_action, 'index', $data[$model]['academic_period_id']), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $_action, 'edit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $_action, 'delete', $data[$model]['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
//echo $this->element('../InstitutionSites/InstitutionSiteClass/controls');
?>

<fieldset class="section_break">
	<legend><?php echo __('Class'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('AcademicPeriod.name'); ?></div>
		<div class="col-md-6"><?php echo $data['AcademicPeriod']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteSection.name'); ?></div>
		<div class="col-md-6">
		<?php
		foreach($sections as $g) {
			echo $g;
			break;
		}
		?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteClass.name'); ?></div>
		<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('EducationSubject.name'); ?></div>
		<div class="col-md-6"><?php echo $data['EducationSubject']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('EducationSubject.name'); ?></div>
		<div class="col-md-6"><?php echo $data['EducationSubject']['code']; ?></div>
	</div>

</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Teachers'); ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.teacher'); ?></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach($staffData as $obj) { ?>
				<tr>
					<td><?php echo $obj['Staff']['identification_no']; ?></td>
					<td><?php echo $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name']; ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Students'); ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.student'); ?></th>
					<th><?php echo $this->Label->get('general.sex'); ?></th>
					<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
					<th><?php echo $this->Label->get('general.category'); ?></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach($studentsData as $obj) : ?>
				<tr>
					<td><?php echo $obj['Student']['identification_no']; ?></td>
					<td><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</fieldset>
<?php
$this->end();
?>
