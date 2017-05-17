<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Security.angular/permission/security.permission.edit.svc', ['block' => true]); ?>
<?= $this->Html->script('Security.angular/permission/security.permission.edit.ctrl', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
<style type='text/css'>
    .ag-grid-duration {
        width: 50%;
        border: none;
        background-color: inherit;
        text-align: center;
    }

    .ag-grid-dir-ltr {
        direction: ltr !important;
    }
</style>
<?= $this->Html->link('<i class="fa kd-back"></i>', $viewUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escapeTitle' => false]) ?>

<?= $this->Html->link('<i class="fa kd-lists"></i>', $indexUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]) ?>
<?php
$this->end();
$this->start('panelBody');
?>
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" ng-controller="SecurityPermissionEditCtrl as SecurityPermissionEditController">
    <div class="alert {{SecurityPermissionEditController.class}}" ng-hide="SecurityPermissionEditController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{SecurityPermissionEditController.message}}
    </div>
    <div ng-init="SecurityPermissionEditController.roleId=<?=$roleId; ?>">
        <div class="scrolltabs sticky-content">
            <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="{{module.name}}" ng-repeat="module in SecurityPermissionEditController.modules" ng-click="SecurityPermissionEditController.changeModule(module)">
                    </uib-tab>
                </uib-tabset>
                <div class="tabs-divider"></div>
            </scrollable-tabset>


        </div>
    </div>
</form>
<?php
$this->end();
?>
