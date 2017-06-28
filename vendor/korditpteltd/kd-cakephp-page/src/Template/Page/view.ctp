<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');
?>

<div class="panel">
    <div class="panel-body">
        <?php
        echo $this->element('OpenEmis.alert');
        echo $this->Page->renderViewElements();
        ?>
    </div>
</div>

<?php $this->end() ?>
