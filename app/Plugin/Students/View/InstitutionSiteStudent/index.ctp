<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Students'));
$this->start('contentActions');
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => $model, 'excel'), array('class' => 'divider'));
	}
	echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = array('url' => array('plugin' => 'Students', 'controller' => 'Students', 'action' => $model), 'inputDefaults' => array('label' => false, 'div' => false));
echo $this->Form->create($model, $formOptions);
echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'OpenEMIS ID, First Name or Last Name', 'form' => false));
?>

<div class="row form-horizontal">
	<div class="col-md-4" style="padding-left: 0">
		<?php
		echo $this->Form->input('school_year_id', array(
			'class' => 'form-control',
			'empty' => __('All Years'),
			'options' => $yearOptions,
			'onchange' => "$('form').submit()",
			'required' => false
		));
		?>
	</div>

	<div class="col-md-4">
		<?php
		echo $this->Form->input('education_programme_id', array(
			'class' => 'form-control',
			'empty' => __('All Programmes'),
			'options' => $programmeOptions,
			'onchange' => "$('form').submit()",
			'required' => false
		));
		?>
	</div>

	<div class="col-md-4" style="padding-right: 0">
		<?php
		echo $this->Form->input('student_status_id', array(
			'class' => 'form-control',
			'empty' => __('All Statuses'),
			'options' => $statusOptions,
			'onchange' => "$('form').submit()",
			'required' => false
		));
		?>
	</div>
</div>

<?php echo $this->Form->end() ?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('Student.identification_no', __('OpenEMIS ID')) ?></th>
				<th><?php echo $this->Paginator->sort('Student.first_name', __('Name')) ?></th>
				<th><?php echo $this->Paginator->sort('EducationProgramme.name', __('Programme')) ?></th>
				<th><?php echo $this->Paginator->sort('StudentStatus.name', __('Status')) ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj['Student']['id'];
				$identificationNo = $this->Utility->highlight($search, $obj['Student']['identification_no']);
				$firstName = $this->Utility->highlight($search, $obj['Student']['first_name'].((isset($obj['Student']['history_first_name']))?'<br>'.$obj['Student']['history_first_name']:''));
				$middleName = $this->Utility->highlight($search, $obj['Student']['middle_name'].((isset($obj['Student']['history_middle_name']))?'<br>'.$obj['Student']['history_middle_name']:''));
				$lastName = $this->Utility->highlight($search, $obj['Student']['last_name'].((isset($obj['Student']['history_last_name']))?'<br>'.$obj['Student']['history_last_name']:''));
				$name = $this->Html->link($firstName.' '.$lastName, array('action' => 'view', $id), array('escape' => false));
		?>
			<tr>
				<td><?php echo $identificationNo; ?></td>
				<td><?php echo $name; ?></td>
				<td><?php echo $obj['EducationProgramme']['name']; ?></td>
				<td><?php echo $obj['StudentStatus']['name']; ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
