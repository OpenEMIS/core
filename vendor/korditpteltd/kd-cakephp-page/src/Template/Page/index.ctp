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

<<<<<<< HEAD
echo $this->element('Page.table');
=======
<div class="panel">
    <div class="panel-body">
        <?= $this->element('Page.alert') ?>
        <?= $this->element('Page.tabs') ?>
        <?= $this->element('Page.filters') ?>
        <?= $this->element('Page.table') ?>
    </div>
</div>
>>>>>>> 92ed69a6b777b6ce5d15390aea6b89cec6f2ec6c

$this->end();
?>
