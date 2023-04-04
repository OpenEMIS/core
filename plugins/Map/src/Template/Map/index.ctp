<?= $this->Html->script('Map.angular/map/map.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Map.angular/map/map.svc', ['block' => true]); ?>
<?php
/*************** Start POCOR-5188 */
$this->start('toolbar');
?>
    <?php 
        if(!empty($is_manual_exist)):
    ?>

    <a href="<?php echo $is_manual_exist['url']; ?>" target="_blank">
        <button  class="btn btn-xs btn-default icon-big"  data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Help') ?>" >
        <i class="fa fa-question-circle"></i>
        </button>
    </a>
    <?php endif ?>
    
<?php
$this->end();
/*************** End POCOR-5188 */ 
?>
<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>
    <kdx-map id="map-group-cluster" ng-if="mapReady" [config]="mapConfig" [position]="mapPosition" [data]="mapData" style="height:85%"></kdx-map>
<?php $this->end() ?>
