<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');

if (isset($elements)) {
    echo $this->Page->renderViewElements($elements);
} else {
    echo 'There are no elements';
}

$this->end();
?>
