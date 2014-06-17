<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'awardAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Issue Date'), __('Name'), __('Issuer'), __('Comment'));
$tableData = array();

foreach($data as $obj) {
    $id = $obj[$model]['id'];
	$row = array();
        $row[] = $obj[$model]['issue_date'];
        $row[] = $this->Html->link($obj[$model]['award'], array('action' => 'awardView', $id), array('escape' => false));
        $row[] = $obj[$model]['issuer'];
        $row[] = $obj[$model]['comment'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>