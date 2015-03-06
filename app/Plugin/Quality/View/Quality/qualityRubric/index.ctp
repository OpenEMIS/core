<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'qualityRubricAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Academic Period'), __('Name'), __('Section'), __('Staff'));
$tableData = array();
foreach ($data as $obj) {
	$staffName = $this->Model->getName($obj['Staff']);
	$row = array();
	$row[] = $obj['AcademicPeriod']['name'];
	$row[] = $this->Html->link($obj['RubricsTemplate']['name'], array('action' => 'qualityRubricView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['InstitutionSiteSection']['name'];
	$row[] = $staffName;

	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end(); ?>
