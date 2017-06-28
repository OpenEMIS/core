<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');
?>

<div class="panel">
    <div class="panel-body">
        <?= $this->element('OpenEmis.alert') ?>
        <?= $this->element('Page.tabs') ?>
        <?= $this->element('Page.filters') ?>
        <?= $this->element('Page.table') ?>
    </div>
</div>

<?php $this->end() ?>
