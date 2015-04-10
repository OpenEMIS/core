<?php 
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentBody');
/*
foreach($data as $key => $classes){
	echo '<fieldset class="section_group">';
	echo '<legend>'.$key.'</legend>';
	$tableHeaders = array(__('Academic Periods'), __('Classes'), __('Sections'), __('Programme'), __('Grade'));
	$tableData = array();
	foreach($classes as $class){
		$row = array();
		$row[] = $class['AcademicPeriod']['name'];
		$row[] = $class['InstitutionSiteClass']['name'];
		$row[] = $class['InstitutionSiteSection']['name'];
		$row[] = $class['EducationProgramme']['name'];
		$row[] = $class['EducationGrade']['name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	echo '</fieldset>';
}*/
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.academic_period'); ?></th>
				<th><?php echo $this->Label->get('Institution.name'); ?></th>
				<th><?php echo $this->Label->get('general.section'); ?></th>
				<th><?php echo $this->Label->get('general.class'); ?></th>
				<th><?php echo $this->Label->get('general.subject'); ?></th>
				<th><?php echo $this->Label->get('general.teacher'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['InstitutionSiteClass']['AcademicPeriod']['name']; ?></td>
				<td><?php echo $obj['InstitutionSiteClass']['InstitutionSite']['name']; ?></td>
				<td><?php echo $obj['InstitutionSiteSection']['name']; ?></td>
				<td><?php echo $obj['InstitutionSiteClass']['name']; ?></td>
				<td><?php echo (array_key_exists('name', $obj['InstitutionSiteClass']['EducationSubject']))? $obj['InstitutionSiteClass']['EducationSubject']['name']: ''; ?></td>
				<td><?php 
				foreach($obj['InstitutionSiteClass']['teachers'] as $teacher){
					echo $teacher . '<br>';
				}
				?></td>
			</tr>
			
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
