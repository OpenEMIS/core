<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'licenseAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Issue Date'), __('Type'), __('Issuer'), __('Number'), __('Expiry Date'));
$tableData = array();

foreach ($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['issue_date'];
	$row[] = $this->Html->link($obj['LicenseType']['name'], array('action' => 'licenseView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['issuer'];
	$row[] = $obj[$model]['license_number'];
	$row[] = $obj[$model]['expiry_date'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
