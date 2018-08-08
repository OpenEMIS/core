<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Staff.angular/staff_attendances/staff.attendances.svc', ['block' => true]); ?>
<?= $this->Html->script('Staff.angular/staff_attendances/staff.attendances.ctrl', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min', ['block' => true]); ?>
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

    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('History');?>" ng-show="$ctrl.action == 'view'" ng-click="$ctrl.onHistoryClick()">
        <i class="fa fa-file-text"></i>
    </button>

    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onBackClick()">
        <i class="fa kd-back"></i>
    </button>
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
                    <select name="academic_period" ng-options="period.id as period.name for period in $ctrl.academicPeriodOptions" ng-model="$ctrl.selectedAcademicPeriod" ng-change="$ctrl.changeAcademicPeriod();">
                        <option value="" ng-if="$ctrl.academicPeriodOptions.length == 0"><?= __('No Options') ?></option>
                    </select>
                </div>
            </div>
        <div class="input select">
            <div class="input-select-wrapper">
                <select name="week" ng-options="week.id as week.name for week in $ctrl.weekListOptions" ng-model="$ctrl.selectedWeek" ng-change="$ctrl.changeWeek();">
                    <option value="" ng-if="$ctrl.weekListOptions.length == 0"><?= __('No Options') ?></option>
                </select>
            </div>
        </div>
    </div>
</div>
<div ng-init="$ctrl.institutionId=<?= $institution_id ?>;$ctrl.staffId=<?= $staff_id ?>;">
    <div id="staff-attendances-table" class="table-wrapper">
        <!-- <div style="width: initial; height: initial;" ng-if="$ctrl.gridOptions" kd-ag-grid="$ctrl.gridOptions" has-tabs="true"></div> -->
        <div ng-if="$ctrl.gridOptions" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
    </div>
</div>
<!-- <style>
    .attendance-dashboard .data-section.single-day {
        width: 32%;
    }

    .splitter-filter select[disabled] {
        background-color: #f2f2f2!important;
        border: 1px solid #ccc!important;
        color: #999!important;
    }

    .splitter-filter .split-content-header {
        margin-bottom: 15px;
    }

    .splitter-filter .input-selection.attendance {
        width: 100%;
    }

    #institution-student-attendances-table .sg-theme .ag-cell {
        display: flex;
        flex-flow: column wrap;
        justify-content: center;
    }

    #institution-student-attendances-table .ag-cell .reason-wrapper {
        position: relative;
        width: 100%;
        display: inline-block;
    }

    #institution-student-attendances-table .ag-cell .reason-wrapper .input-select-wrapper {
        margin-bottom: 15px;
    }

    #institution-student-attendances-table .ag-cell textarea#comment.error,
    #institution-student-attendances-table .ag-cell #student_absence_reason_id select.error,
    #institution-student-attendances-table .ag-cell #absence_type_id select.error {
        border-color: #CC5C5C !important;
    }
    
    #institution-student-attendances-table .ag-cell textarea#comment:focus {
        outline: none;
    }

    #institution-student-attendances-table .ag-cell textarea#comment {
        display: block;
        padding: 5px 10px;
        -webkit-border-radius: 3px;
        border-radius: 3px;
        font-size: 12px;
        height: 70px;
        width: 100%;
        border: 1px solid #CCC;
    }

    #institution-student-attendances-table .ag-cell .input-select-wrapper {
        margin-bottom: 0;
    }

    #institution-student-attendances-table .ag-cell .input-select-wrapper select {
        background: #FFFFFF;
        display: block;
    }

    #institution-student-attendances-table .ag-cell .absence-reason,
    #institution-student-attendances-table .ag-cell .absences-comment {
        overflow: hidden;
        white-space: normal;
        text-overflow: ellipsis;
        max-height: 70px;
        display: flex;
        align-items: baseline;
    }

    #institution-student-attendances-table .ag-cell .absence-reason span,
    #institution-student-attendances-table .ag-cell .absences-comment span {
        margin: 0 10px;
    }


    #institution-student-attendances-table .ag-cell .absence-reason + .absences-comment  {
        margin-top: 15px;
    }
    
    #institution-student-attendances-table .sg-theme .ag-header-cell.children-period .ag-header-cell-label {
        display: flex;
        justify-content: center;
        padding: 10px 0;
    }

    #institution-student-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 1px solid #DDDDDD;
        border-bottom: 1px solid #DDDDDD;
        font-weight: 700;
        text-align: center;
        padding: 10px;
    }

    #institution-student-attendances-table .sg-theme .children-cell {
        text-align: center;
    }

    #institution-student-attendances-table .sg-theme .ag-row-hover {
        background-color: #FDFEE6 !important;
    }

    .rtl #institution-student-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 0;
        border-left: 1px solid #DDDDDD;
    }
</style> -->

<!-- <div class="panel">
    <div class="panel-body" style="position: relative;">
        <bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.institutionId=<?= $institution_id ?>;" float-btn="false">
            <bg-pane class="main-content">
                <div class="alert {{class}}" ng-hide="message == null">
                    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
                </div>

                <div class="overview-box alert attendance-dashboard" ng-class="disableElement" ng-show="$ctrl.action == 'view'">
                    <a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
                    <div class="data-section single-day" ng-show="$ctrl.selectedDay != -1">
                        <i class="kd-students icon"></i>
                        <div class="data-field">
                            <h4><?= __('Total Students') ?>:</h4>
                            <h1 class="data-header">{{$ctrl.totalStudents}}</h1>
                        </div>
                    </div>
                    <div class="data-section single-day" ng-show="$ctrl.selectedDay != -1">
                        <div class="data-field">
                            <h4><?= __('No. of Students Present') ?></h4>   
                            <h1 class="data-header">{{$ctrl.presentCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section single-day" ng-show="$ctrl.selectedDay != -1">
                        <div class="data-field">
                            <h4><?= __('No. of Students Absent') ?></h4>    
                            <h1 class="data-header">{{$ctrl.absenceCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <i class="kd-students icon"></i>
                        <div class="data-field">
                            <h4><?= __('Total Attendance') ?></h4>    
                            <h1 class="data-header">{{$ctrl.allAttendances}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <div class="data-field">
                            <h4><?= __('No. of Present') ?></h4>    
                            <h1 class="data-header">{{$ctrl.allPresentCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <div class="data-field">
                            <h4><?= __('No. of Late') ?></h4>    
                            <h1 class="data-header">{{$ctrl.allLateCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <div class="data-field">
                            <h4><?= __('No. of Absence') ?></h4>    
                            <h1 class="data-header">{{$ctrl.allAbsenceCount}}</h1>
                        </div>
                    </div>
                </div>
                <div id="institution-student-attendances-table" class="table-wrapper">
                    <div ng-if="$ctrl.gridReady" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
                </div>
            </bg-pane>

            <bg-pane class="split-content splitter-slide-out splitter-filter" min-size-p="20" max-size-p="20" size-p="20">
                <div class="split-content-header">
                    <h3><?= __('Filter') ?></h3>
                </div>
                <div class="split-content-area">
                    <h5><?= __('Academic Period') ?>: </h5>
                    <div class="input-select-wrapper">
                        <select ng-disabled="$ctrl.action=='edit'" name="academic_period" ng-options="period.id as period.name for period in $ctrl.academicPeriodOptions" ng-model="$ctrl.selectedAcademicPeriod" ng-change="$ctrl.changeAcademicPeriod();">
                            <option value="" ng-if="$ctrl.academicPeriodOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5><?= __('Week') ?>: </h5>
                    <div class="input-select-wrapper">
                        <select ng-disabled="$ctrl.action=='edit'" name="week" ng-options="week.id as week.name for week in $ctrl.weekListOptions" ng-model="$ctrl.selectedWeek" ng-change="$ctrl.changeWeek();">
                            <option value="" ng-if="$ctrl.weekListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5><?= __('Day') ?>: </h5>
                    <div class="input-select-wrapper">
                        <select ng-disabled="$ctrl.action=='edit'" name="day" ng-options="day.date as day.name for day in $ctrl.dayListOptions" ng-model="$ctrl.selectedDay" ng-change="$ctrl.changeDay();">
                            <option value="" ng-if="$ctrl.dayListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5><?= __('Class') ?>: </h5>
                    <div class="input-select-wrapper">
                        <select ng-disabled="$ctrl.action=='edit'" name="class" ng-options="class.id as class.name for class in $ctrl.classListOptions" ng-model="$ctrl.selectedClass" ng-change="$ctrl.changeClass();">
                            <option value="" ng-if="$ctrl.classListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5><?= __('Attendance per day') ?>: </h5>
                    <div class="input">
                        <div class="input-selection attendance">
                            <div class="input" ng-repeat="attendance_period in $ctrl.attendancePeriodOptions">
                                <input kd-checkbox-radio="{{attendance_period.name}}" ng-model="$ctrl.selectedAttendancePeriod" ng-change="$ctrl.changeAttendancePeriod();" value="{{attendance_period.id}}" type="radio" name="attendance_per_day">
                            </div>
                        </div>
                    </div>  
                </div>
            </bg-pane>
        </bg-splitter>
    </div>
</div> -->


<?php
$this->end();
?>
