<?php
$this->extend('Page.Layout/container');

$this->start('toolbar');

if (!in_array('add', $disabledActions)) {
    echo $this->element('Page.button', ['title' => __('Add'), 'url' => ['action' => 'add'], 'iconClass' => 'fa kd-add']);
}

if (!in_array('search', $disabledActions)) {
    echo $this->element('Page.search');
}

$this->end();

$this->start('contentBody');

echo $this->element('Page.table');

$this->end();
?>
