<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');

$tableHeaders = array($this->Label->get('general.school_year'), $this->Label->get('EducationProgramme.name'), $this->Label->get('EducationGrade.name'), $this->Label->get('FinanceFee.fee'), $this->Label->get('FinanceFee.paid'), $this->Label->get('FinanceFee.outstanding'));
$tableData = array();
if(!empty($data)) { 
	foreach ($data as $obj) {
		$row = array();
		$row[] = array($obj['SchoolYear']['name'], array('class'=>array('center')));
		$row[] = array($obj['EducationGrade']['EducationProgramme']['name'], array('class'=>array('center')));
		$row[] = $this->Html->link($obj['EducationGrade']['name'], array('action' => 'feeView', $obj['InstitutionSiteStudentFee']['id']), array('escape' => false));
		$row[] = $obj['InstitutionSiteFee']['total_fee'];
		$row[] = $obj['InstitutionSiteStudentFee']['total_paid'];
		$row[] = $obj['InstitutionSiteStudentFee']['total_outstanding'];
		$tableData[] = $row;
	}
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>