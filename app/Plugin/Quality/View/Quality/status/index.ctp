<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'statusAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Name'), __('Year'), __('Date Enabled'), __('Date Disabled'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $this->Html->link($rubricOptions[ $obj[$model]['rubric_template_id']], array('action' => 'statusView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['year'];
	$row[] = $obj[$model]['date_enabled'];;
	$row[] = $obj[$model]['date_disabled'];

	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 
?>  