<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Staff.angular/staff_attendances/staff.attendances.svc', ['block' => true]); ?>
<?= $this->Html->script('Staff.angular/staff_attendances/staff.attendances.ctrl', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min', ['block' => true]);?>
<?= $this->Html->css('ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min', ['block' => true]); ?>
<?php
$this->start('toolbar');
?>

<!-- <?php if ($_excel) : ?>
    <a href="<?=$excelUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Export') ?>" >
            <i class="fa kd-export" ></i>
        </button>
    </a>
<?php endif; ?> -->

<!-- <?php if ($_import) : ?>
    <a href="<?=$importUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="{{test()}}" data-placement="bottom" data-container="body" title="<?= __('Import') ?>" >
            <i class="fa kd-import"></i>
        </button>
    </a>
</button>
<?php endif; ?> -->

<?php if ($_edit) : ?>
    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="$ctrl.action == 'view'" ng-click="$ctrl.onEditClick()">
        <i class="fa kd-edit"></i>
    </button>
    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onBackClick()">
        <i class="fa kd-back"></i>
    </button>
<?php endif; ?>
<?php if ($_history) : ?>
    <a href="<?=$historyUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('History') ?>" >
            <i class="fa fa-file-text"></i>
        </button>
    </a>
<?php endif; ?>

<?php
$this->end();
?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
$paramsQuery = $this->ControllerAction->getQueryString();
$institutionId = $paramsQuery['institution_id'];
?>
<?= $this->element('OpenEmis.alert') ?>
<div class="alert {{class}}" ng-hide="message == null">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
</div>
<?php if (isset($tabElements)) : ?>
    <?php $selectedAction = isset($selectedAction) ? $selectedAction : null; ?>
    <div id="tabs" class="nav nav-tabs horizontal-tabs">
        <?php foreach($tabElements as $element => $attr): ?>
            <span role="presentation" class="<?php echo ($element == $selectedAction) ? 'tab-active' : ''; ?>"><?php echo $this->Html->link(__($attr['text']), $attr['url']); ?></span>
        <?php endforeach; ?>
    </div>
<?php endif ?>
<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
            <div class="input select">
                <div class="input-select-wrapper">
                    <select class="form-control" name="academic_period" ng-options="period.id as period.name for period in $ctrl.academicPeriodOptions" ng-model="$ctrl.selectedAcademicPeriod" ng-change="$ctrl.changeAcademicPeriod();">
                        <option value="" ng-if="$ctrl.academicPeriodOptions.length == 0"><?= __('No Options') ?></option>
                    </select>
                </div>
            </div>
        <div class="input select">
            <div class="input-select-wrapper">
                <select class="form-control" name="week" ng-options="week.id as week.name for week in $ctrl.weekListOptions" ng-model="$ctrl.selectedWeek" ng-change="$ctrl.changeWeek();">
                    <option value="" ng-if="$ctrl.weekListOptions.length == 0"><?= __('No Options') ?></option>
                </select>
            </div>
        </div>
    </div>
</div>
<div ng-init="$ctrl.institutionId=<?= $institution_id ?>;$ctrl.staffId=<?= $staff_id ?>;">
    <div id="staff-attendances-table" class="table-wrapper">
        <div ng-if="$ctrl.gridOptions" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
    </div>
</div>

<style>
    #staff-attendances-table .sg-theme .ag-cell {
        display: flex;
        flex-flow: column wrap;
        justify-content: center;
    }

    #staff-attendances-table .sg-theme .ag-row-hover {
        background-color: #FDFEE6 !important;
    }

    .rtl #staff-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 0;
        border-left: 1px solid #DDDDDD;
    }

    #staff-attendances-table .sg-theme .time-view {
        padding: 4px;
        font-size: 13px;
        color: #77B576;
    }

    #staff-attendances-table .sg-theme .time-view > i {
        margin: 0 8px 0 0;
        font-weight: bold;
    }

    .rtl #staff-attendances-table .sg-theme .time-view > i {
        margin: 0 0 0 8px;
        font-weight: bold;
    }
</style>
<?php
$this->end();
?>
