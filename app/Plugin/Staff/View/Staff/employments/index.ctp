<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'employmentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Type'), __('Date'), __('Comment'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
	$row[] = $this->Html->link($obj['EmploymentType']['name'], array('action' => 'employmentsView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['employment_date'] ;
	$row[] = $obj[$model]['comment'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>
