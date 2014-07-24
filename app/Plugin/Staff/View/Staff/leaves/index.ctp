<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'leavesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Type'), __('Status'), __('First Day'), __('Last Day'), __('Comments'), __('No of Days'));
$tableData = array();

$total = array();

foreach ($data as $obj) {
	$days = $obj[$model]['number_of_days'];
	$type = $obj['StaffLeaveType']['name'];
	if (!array_key_exists($obj['StaffLeaveType']['name'], $total)) {
		$total[$type] = $days;
	} else {
		$total[$type] = $total[$type] + $days;
	}

	$row = array();
	$row[] = $this->Html->link($type, array('action' => 'leavesView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['LeaveStatus']['name'];
	$row[] = $obj[$model]['date_from'];
	$row[] = $obj[$model]['date_to'];
	$row[] = $obj[$model]['comments'];
	$row[] = $days;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>