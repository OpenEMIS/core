<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');

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
}

$this->end();
?>
