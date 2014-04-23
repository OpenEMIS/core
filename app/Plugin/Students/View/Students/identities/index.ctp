<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Type'), __('Number'), __('Issued'), __('Expiry'), __('Location'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj['IdentityType']['name'] ;
        $row[] = $this->Html->link($obj[$model]['number'], array('action' => 'identitiesView', $obj[$model]['id']), array('escape' => false)) ;
	$row[] = $obj[$model]['issue_date'];
	$row[] = $obj[$model]['expiry_date'];
        $row[] = $obj[$model]['issue_location'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

 $this->end(); ?>
