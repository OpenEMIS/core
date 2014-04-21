<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthMedicationAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Name'), __('Dosage'), __('Commenced'), __('Ended'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = $this->Html->link($obj[$model]['name'], array('action' => 'healthMedicationView', $obj[$model]['id']), array('escape' => false));
        $row[] = $obj[$model]['dosage'] ;
        $row[] = $obj[$model]['start_date'] ;
        $row[] = $obj[$model]['end_date'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>