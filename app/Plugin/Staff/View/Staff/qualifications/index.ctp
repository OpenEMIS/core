<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'qualificationsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Graduate Year'), __('Level'), __('Qualification Title'), __('Document No.'), __('Institution'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['graduate_year'] ;
	$row[] = $obj['QualificationLevel']['name'] ;
	$row[] = $this->Html->link($obj[$model]['qualification_title'], array('action' => 'qualificationsView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['document_no'] ;
	$row[] = $obj['QualificationInstitution']['name'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 
?>
