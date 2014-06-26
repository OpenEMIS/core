<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'specialNeedAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Type'), __('Comment'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['special_need_date'];
	$row[] = $this->Html->link($obj['SpecialNeedType']['name'], array('action' => 'specialNeedView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['comment'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
