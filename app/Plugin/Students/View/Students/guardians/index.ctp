<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'guardiansAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('First Name'), __('Last Name'), __('Relationship'), __('Mobile Phone'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = $this->Html->link($obj['Guardian']['first_name'], array('action' => 'guardiansView', $obj['Guardian']['id']), array('escape' => false));
        $row[] = $obj['Guardian']['last_name'];
        $row[] = $obj['GuardianRelation']['name'] ;
        $row[] = $obj['Guardian']['mobile_phone'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>