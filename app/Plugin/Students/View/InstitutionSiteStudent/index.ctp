<?php 
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site_students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'students_search');
$this->assign('contentClass', 'search');
$this->assign('contentHeader', __('List of Students'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model));
$formOptions['inputDefaults'] = array('label' => false, 'div' => false);
echo $this->Form->create($model, $formOptions);
?>
<div class="row">
	<div class="search_wrapper">
		<?php 
			echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				'value' => $searchField,
				'class' => 'default',
				'placeholder' => __('Student OpenEMIS ID or Student Name')
			));
		?>
		<span class="icon_clear">X</span>
	</div>
	<span class="left icon_search" onclick="$('form').submit()"></span>
</div>

<div class="row">
	<?php
	echo $this->Form->input('school_year', array(
		'id' => 'SchoolYearId',
		'class' => 'search_select form-control',
		'empty' => __('All Years'),
		'options' => $yearOptions,
		'default' => $selectedYear
	));
	?>
</div>

<div class="row">
	<?php
	echo $this->Form->input('education_programme_id', array(
		'id' => 'EducationProgrammeId',
		'class' => 'search_select form-control',
		'empty' => __('All Programmes'),
		'options' => $programmeOptions,
		'default' => $selectedYear
	));
	?>
</div>
<div class="row">
	<?php
	echo $this->Form->input('student_status_id', array(
		'id' => 'StudentStatusId',
		'class' => 'search_select form-control',
		'empty' => __('All Statuses'),
		'options' => $statusOptions
	));
	?>
</div>
<?php
$orderSort = $order==='asc' ? 'up' : 'down';
echo $this->Form->hidden('orderBy', array('value' => $orderBy));
echo $this->Form->hidden('order', array('value' => $order));
echo $this->Form->hidden('page', array('value' => $page));
echo $this->Form->end();
?>
<div id="mainlist">
	<?php if(!empty($data)) { ?>
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
	<?php } ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th>
						<span class="left"><?php echo __('OpenEMIS ID'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy =='Student.identification_no')?$orderSort:'up'; ?>" orderBy="Student.identification_no"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Name'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy =='Student.first_name')?$orderSort:'up'; ?>" orderBy="Student.first_name"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Programme'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy =='EducationProgramme.name')?$orderSort:'up'; ?>" orderBy="EducationProgramme.name"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Status'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy =='StudentStatus.name')?$orderSort:'up'; ?>" orderBy="StudentStatus.name"></span>
					</th>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($data as $obj) { ?>
				<?php
				$idNo = $this->Utility->highlight($searchField, $obj['Student']['identification_no']);
				$firstName = $this->Utility->highlight($searchField, $obj['Student']['first_name']);
				$middleName = $this->Utility->highlight($searchField, $obj['Student']['middle_name']);
				$lastName = $this->Utility->highlight($searchField, $obj['Student']['last_name']);
				$fullName = trim($firstName.' '.$middleName). ' '.$lastName;
				?>
				<tr>
					<td><?php echo $idNo; ?></td>
					<td><?php echo $this->Html->link($fullName, array('controller' => 'Students', 'action' => 'view', $obj['Student']['id']), array('escape' => false)); ?></td>
					<td><?php echo $obj['EducationProgramme']['name']; ?></td>
					<td><?php echo $obj['StudentStatus']['name']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
</div>
<?php $this->end(); ?>
