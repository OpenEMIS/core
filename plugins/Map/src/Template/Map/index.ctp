<?= $this->Html->script('Map.angular/map/map.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Map.angular/map/map.svc', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>
    <kdx-map id="map-group-cluster" ng-if="mapReady" [config]="mapConfig" [position]="mapPosition" [data]="mapData" style="height:85%"></kdx-map>
<?php $this->end() ?>
