<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'bankAccountsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Active'), __('Account Name'), __('Account Number'), __('Bank'), __('Branch'));
$tableData = array();


foreach ($data as $obj) {
    $symbol = $this->Utility->checkOrCrossMarker($obj[$model]['active'] == 1);
    $row = array();
    $row[] = array($symbol, array('class' => 'center'));
    $row[] = $this->Html->link($obj[$model]['account_name'], array('action' => 'bankAccountsView', $obj[$model]['id']), array('escape' => false));
    $row[] = $obj[$model]['account_number'];
    $row[] = $obj['BankBranch']['Bank']['name'];
    $row[] = $obj['BankBranch']['name'];
    $tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
