<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));


$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Students'));

$this->start('contentBody');
echo $this->Form->create('Student', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'students'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('school_year', array(
			'id' => 'SchoolYearId',
			'class' => ' form-control',
			'url' => 'InstitutionSites/behaviourStudentList',
			'onchange' => 'jsForm.change(this)',
			//	'empty' => __('All Years'),
			'options' => $yearOptions,
			'default' => $selectedYear
		));
		?>
	</div>

	<div class="col-md-3">
		<?php
		echo $this->Form->input('institutionSiteClassId', array(
			'id' => 'InstitutionSiteClassId',
			'class' => ' form-control',
			'url' => 'InstitutionSites/behaviourStudentList/' . $selectedYear,
			'onchange' => 'jsForm.change(this)',
			//'empty' => __('All Programmes'),
			'options' => $classOptions,
			'default' => $selectedClass
		));
		?>
	</div>

</div>
<?php
echo $this->Form->end();
?>
<div id="mainlist">
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th>
						<span class="left"><?php echo $this->Label->get('general.openemisId'); ?></span>
					</th>
					<th>
						<span class="left"><?php echo $this->Label->get('general.name'); ?></span>

					</th>
					<th>
						<span class="left"><?php echo $this->Label->get('general.grade'); ?></span>

					</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($data as $obj) { ?>
					<?php
					$idNo = $obj['Student']['identification_no'];
					$firstName = $obj['Student']['first_name'];
					$middleName = $obj['Student']['middle_name'];
					$lastName = $obj['Student']['last_name'];
					$fullName = trim($firstName . ' ' . $middleName) . ' ' . $lastName;
					?>
					<tr>
						<td><?php echo $this->Html->link($idNo, array('action' => 'behaviourStudent', $obj['Student']['id']), array('escape' => false)); ?></td>
						<td><?php echo trim($fullName); ?></td>
						<td><?php echo $obj['EducationGrade']['name']; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php $this->end(); ?>
