<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'positionsView', $data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));

$tableHeaders = array(__('OpenEMIS ID'), __('Name'), __('From'), __('To'));
$tableData = array();
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
