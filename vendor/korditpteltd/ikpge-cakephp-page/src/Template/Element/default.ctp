<?php
$htmlLang = isset($htmlLang) ? $htmlLang : 'en';
$htmlLangDir = isset($htmlLangDir) ? $htmlLangDir : 'ltr';
$productName = isset($productName) ? $productName : 'OpenEMIS';
$ngApp = isset($ngApp) ? $ngApp : 'OpenEMIS_APP';
$appCtrl = isset($appCtrl) ? $appCtrl : 'AppCtrl';
$ngController = isset($ngController) ? 'ng-controller="' . $ngController . '"' : '';

$url = '#';
if (!empty($homeUrl)) {
    $url = $this->Url->build($homeUrl);
}
?>

<!DOCTYPE html>
<html lang="<?= $htmlLang ?>" dir="<?= $htmlLangDir ?>" class="<?= $htmlLangDir == 'rtl' ? 'rtl' : '' ?>">
<head>
    <?= $this->Html->charset() ?>
    <title><?= $productName ?></title>

    <?php
    echo $this->Html->meta('icon');
    echo $this->fetch('meta');

    echo $this->element('styles');
    echo $this->fetch('css');

    echo $this->element('scripts');
    echo $this->fetch('script');
    ?>
</head>

<body class='fuelux' ng-app="<?= $ngApp ?>" ng-controller="<?= $appCtrl ?>">
    <header>
        <nav class="navbar navbar-fixed-top">
            <div class="navbar-left">
                <div class="menu-handler">
                    <button class="menu-toggle" type="button">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>
                <a href="<?= $url ?>">
                    <div class="brand-logo">
                        <i class="kd-openemis"></i>
                        <h1><?= $productName ?></h1>
                    </div>
                </a>
            </div>
            <?php if (!isset($headerSideNav) || (isset($headerSideNav) && $headerSideNav)) : ?>
            <div class="navbar-right">
                <?= $this->element('Page.header_navigation') ?>
            </div>
            <?php endif ?>
        </nav>
    </header>

    <bg-splitter orientation="horizontal" class="pane-wrapper" resize-callback="splitterDragCallback" elements="getSplitterElements">
        <bg-pane id="leftPane" class="left-pane" min-size-p="30px" max-size-p="40">
            <div class="pane-container">
                <div class="left-menu">
                    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                        <?= $this->element('navigation') ?>
                    </div>

                    <?php
                    $menuItemSelected = isset($menuItemSelected) ? implode('-', $menuItemSelected) : '';
                    $selectedLink = isset($selectedLink) ? $selectedLink : $menuItemSelected;
                    ?>
                    <script type="text/javascript">
                    $(document).ready(function() {
                        $('#accordion').on('show.bs.collapse', function (e) {
                            var target = e.target;
                            var level = $(target).attr('data-level');
                            var id = $(target).attr('id');
                            $('[data-level=' + level + ']').each(function() {
                                if ($(this).attr('id') != id && $(this).hasClass('in') == true) {
                                    $(this).collapse('hide');
                                }
                            });
                        })

                        var action = '<?= $selectedLink ?>';
                        $('#' + action).addClass('nav-active');
                        var ul = $('#' + action).parents('ul');

                        ul.each(function() {
                            $(this).addClass('in');
                            $(this).siblings('a.accordion-toggle').removeClass('collapsed');
                        });
                    });
                    </script>
                </div>
            </div>
        </bg-pane>

        <bg-pane id="rightPane" class="right-pane pane-container">
            <?= $this->fetch('content') ?>
        </bg-pane>
    </bg-splitter>

    <footer>
        <?= __('Copyright') ?> &copy; <?= date('Y') ?>  OpenEMIS. <?= __('All rights reserved.') ?> | <?= __('Version') . ' ' . $SystemVersion ?>
    </footer>

    <?php
    echo $this->fetch('scriptBottom');
    echo $this->Html->script('Page.angular/kd-angular-splitter');
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
        Chosen.init();
        Checkable.init();
        MobileMenu.init();
        TableResponsive.init();
        Tooltip.init();
        ScrollTabs.init();
        Header.init();
    });
    </script>

</body>

</html>
