<?php
$this->extend('Page.Layout/container');

$this->start('toolbar');

if (array_key_exists('add', $actions)) {
    echo $this->element('Page.button', ['title' => __('Add'), 'url' => ['action' => 'add'], 'iconClass' => 'fa kd-add']);
}

if (array_key_exists('search', $actions)) {
    echo $this->element('Page.search');
}

$this->end();

$this->start('contentBody');

echo $this->element('Page.table');

$this->end();
?>
