<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'membershipAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Issue Date'), __('Name'), __('Expiry Date'), __('Comment'));
$tableData = array();

foreach($data as $obj) {
    $id = $obj[$model]['id'];
	$row = array();
	$row[] = $obj[$model]['issue_date'];
	$row[] = $this->Html->link($obj[$model]['membership'], array('action' => 'membershipView', $id), array('escape' => false));
	$row[] = $obj[$model]['expiry_date'];
	$row[] = $obj[$model]['comment'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 
?>
