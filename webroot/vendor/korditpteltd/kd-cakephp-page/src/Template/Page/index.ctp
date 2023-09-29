<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');

echo $this->element('Page.table');

$this->end();
?>
