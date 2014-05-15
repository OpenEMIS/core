<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Comments'));
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'commentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Title'), __('Comment'));
$tableData = array();

foreach ($data as $obj) {
    $row = array();
    $row[] = $obj[$model]['comment_date'];
    $row[] = $this->Html->link($obj[$model]['title'], array('action' => 'commentsView', $obj[$model]['id']));
    $row[] = $obj[$model]['comment'];
    $tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>