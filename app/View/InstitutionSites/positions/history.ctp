<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'positionsView', $data[$model]['id']), array('class' => 'divider'));

$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));

$tableHeaders = array(__('OpenEMIS ID'), __('Name'),__('FTE'), __('From'), __('To'));
$tableData = array();

foreach($staffList as $obj) {
	$row = array();
	$name = $obj['Staff']['first_name'].' '.$obj['Staff']['middle_name'].' '.$obj['Staff']['last_name'] ;
	$row[] = $obj['Staff']['identification_no'];
	$row[] = $this->Html->link($name, array('action' => 'positionsStaffEdit', $obj['InstitutionSiteStaff']['id']), array('escape' => false)) ;
	$row[] = $obj['InstitutionSiteStaff']['FTE']*100;
	$row[] = $obj['InstitutionSiteStaff']['start_date'];
	$row[] = empty($obj['InstitutionSiteStaff']['end_date'])? 'Current':$obj['InstitutionSiteStaff']['end_date'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
