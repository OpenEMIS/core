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

<?= $this->Html->link('<i class="fa kd-back"></i>', $viewUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escapeTitle' => false, 'id' => 'back_url']) ?>

<?= $this->Html->link('<i class="fa kd-lists"></i>', $indexUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]) ?>
<?php
$this->end();
$this->start('panelBody');
?>
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" ng-controller="SecurityPermissionEditCtrl as SecurityPermissionEditController">
    <div class="alert {{SecurityPermissionEditController.class}}" ng-hide="SecurityPermissionEditController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{SecurityPermissionEditController.message}}
    </div>
    <div ng-init="SecurityPermissionEditController.roleId=<?=$roleId ?>; SecurityPermissionEditController.redirectUrl='<?= $this->Url->build($viewUrl)?>'; SecurityPermissionEditController.alertUrl='<?= $this->Url->build($alertUrl) ?>'; SecurityPermissionEditController.moduleKey='<?= $moduleKey?>'">
        <div class="scrolltabs sticky-content">
            <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="{{module.name}}" ng-repeat="module in SecurityPermissionEditController.modules" ng-click="SecurityPermissionEditController.changeModule(module)">
                    </uib-tab>
                </uib-tabset>
                <div class="tabs-divider"></div>
            </scrollable-tabset>
            <div class="section-header security-permission-checkbox" ng-repeat-start="(key, section) in SecurityPermissionEditController.pageSections">
                <input
                class="no-selection-label"
                kd-checkbox-radio={{section.name}}
                type="checkbox"
                ng-true-value="1"
                ng-false-value="0"
                ng-model="section.enabled"
                ng-change="SecurityPermissionEditController.checkAllInSection(key);">
            </div>
            <div class="table-wrapper" ng-repeat-end>
                <div class="table-responsive">
                    <table class="table table-curved">
                        <thead>
                            <th style="width: 300px"><?= __('Function')?></th>
                            <th class="center"><?= __('View')?></th>
                            <th class="center"><?= __('Edit')?></th>
                            <th class="center"><?= __('Add')?></th>
                            <th class="center"><?= __('Delete')?></th>
                            <th class="center"><?= __('Execute')?></th>
                        </thead>
                        <tbody ng-repeat="function in section.items">
                            <tr>
                                <td>{{function.name}}
                                <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="right" uib-tooltip={{function.description}} tooltip-append-to-body="true" tooltip-class="tooltip-blue" ng-hide="function.description==null;"></i>
                                <td class="center"><input class="no-selection-label" kd-checkbox-radio type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="function.Permissions._view" ng-disabled="function._view==null;" ng-change="SecurityPermissionEditController.changePermission(function, 'view', function.Permissions._view);"></td>
                                <td class="center"><input class="no-selection-label" kd-checkbox-radio type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="function.Permissions._edit" ng-disabled="function._edit==null;" ng-change="SecurityPermissionEditController.changePermission(function, 'edit', function.Permissions._edit);"></td>
                                <td class="center"><input class="no-selection-label" kd-checkbox-radio type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="function.Permissions._add" ng-disabled="function._add==null;" ng-change="SecurityPermissionEditController.changePermission(function, 'add', function.Permissions._add);"></td>
                                <td class="center"><input class="no-selection-label" kd-checkbox-radio type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="function.Permissions._delete" ng-disabled="function._delete==null;" ng-change="SecurityPermissionEditController.changePermission(function, 'delete', function.Permissions._delete);"></td>
                                <td class="center"><input class="no-selection-label" kd-checkbox-radio type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="function.Permissions._execute" ng-disabled="function._execute==null;" ng-change="SecurityPermissionEditController.changePermission(function, 'execute', function.Permissions._execute);"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="form-buttons" ng-show="SecurityPermissionEditController.ready">
                <div class="button-label"></div>
                <button class="btn btn-default btn-save" type="button" ng-click="SecurityPermissionEditController.postForm();">
                    <i class="fa fa-check"></i> <?= __('Save') ?>
                </button>
                <?= $this->Html->link('<i class="fa fa-close"></i> '.__('Cancel'), $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            </div>
        </div>
    </div>
</form>
<?php
$this->end();
?>
