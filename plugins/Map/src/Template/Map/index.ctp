<?= $this->Html->script('Map.angular/map/map.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Map.angular/map/map.svc', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>
    <kdx-map id="map-group-cluster" [config]="MapController.mapConfig" [position]="MapController.mapPosition" [data]="MapController.mapData"></kdx-map>

<?php $this->end() ?>
