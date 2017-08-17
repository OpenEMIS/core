<?php
$ngController = '';
$wrapperClass = 'wrapper';

echo $this->element('Page.page_js');
?>

<div class="content-wrapper" <?= $ngController ?>>
    <?= $this->element('Page.breadcrumb') ?>

    <div class="page-header">
        <h2 id="main-header"><?= $header ?></h2>
        <div class="toolbar toolbar-search">
            <?= $this->fetch('toolbar') ?>
        </div>
    </div>

    <div class="<?= $wrapperClass ?>">
        <div class="wrapper-child">
            <div class="panel">
                <div class="panel-body">
                    <?= $this->element('Page.alert') ?>
                    <?= $this->element('Page.tabs') ?>
                    <?= $this->element('Page.filters') ?>
                    <?= $this->fetch('contentBody') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->Page->afterRender();
?>
