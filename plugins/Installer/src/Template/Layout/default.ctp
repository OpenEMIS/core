<!DOCTYPE html>
<?php require CONFIG . 'installer_mode_config.php'; ?>
<html lang="en" dir="ltr" class="ltr">
<head>
    <?= $this->Html->charset(); ?>
    <title><?= $_productName ?></title>

    <?php
    $icon = strpos($_productName, ucfirst(APPLICATION_FAVICON)) != -1 ? APPLICATION_FAVICON : '';
    echo $this->Html->meta('icon', 'favicon'.$icon.'.ico');
    echo $this->fetch('meta');

    echo $this->element('styles');
    echo $this->fetch('css');

    echo $this->element('scripts');
    echo $this->fetch('script');

    echo $this->element('Angular.app');
    ?>
</head>

<body class='fuelux installer' ng-app="OE_Core" ng-controller="AppCtrl">
    <header>
        <nav class="navbar navbar-fixed-top">
            <div class="navbar-left">
                <div class="menu-handler">
                    <button class="menu-toggle" type="button">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>
                <a href="#">
                    <div class="brand-logo">
                        <i class="kd-openemis"></i>
                        <h1><?= $_productName ?></h1>
                    </div>
                </a>
            </div>
        </nav>
    </header>

    <div style="padding: 80px 20px">
    <?php
        echo $this->fetch('content');
    ?>
    </div>

    <?= $this->element('OpenEmis.footer') ?>
    <!-- <?= $this->fetch('scriptBottom'); ?> -->
    <!-- <?= $this->element('OpenEmis.scriptBottom') ?> -->

</body>
</html>
