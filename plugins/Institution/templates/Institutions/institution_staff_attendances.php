<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/staff/institution.staff.attendances.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/staff/institution.staff.attendances.ctrl', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min', ['block' => true]);?>
<?= $this->Html->css('ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min', ['block' => true]); ?>
<?php
$this->start('toolbar');
?>
<?php if ($_import) : ?>
    <a href="<?=$importUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="{{test()}}" data-placement="bottom" data-container="body" title="<?= __('Import') ?>" >
            <i class="fa kd-import"></i>
        </button>
    </a>
</button>
<?php endif; ?>
<?php if ($_edit) : ?>
    <?php //POCOR-9700: keep Edit visible but disabled when no shift is selected (multishift schools). ?>
    <?php //Use uib-tooltip (reactive) instead of Bootstrap data-toggle=tooltip so the hint switches when state changes. ?>
    <button class="btn btn-xs btn-default" data-placement="bottom"
            aria-label="<?= __('Edit') ?>"
            uib-tooltip="{{(!$ctrl.selectedShift || $ctrl.selectedShift == -1) ? '<?= __('Select a shift to enable editing') ?>' : '<?= __('Edit') ?>'}}"
            tooltip-placement="bottom" tooltip-append-to-body="true"
            ng-show="$ctrl.action == 'view' && !$ctrl.schoolClosed && $ctrl.selectedDay != -1"
            ng-disabled="!$ctrl.selectedShift || $ctrl.selectedShift == -1"
            ng-click="$ctrl.onEditClick()">
        <i class="fa kd-edit"></i>
    </button>

    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onBackClick()">
        <i class="fa kd-back"></i>
    </button>
<?php endif; ?>
<?php if ($_excel) : ?>
    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Export') ?>" ng-show="$ctrl.action == 'view'" ng-click="$ctrl.onExcelClick()">
        <i class="fa kd-export" ></i>
    </button>
<?php endif; ?>
<?php
// $session = $this->Session;
// $superAdmin = $session->read('Auth.User.super_admin');
// $is_connection_is_online = $session->read('is_connection_stablished');
?>
<?php if ($archiveUrl) : ?>
    <a href="<?=$archiveUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Archive') ?>" >
            <i class="fa fa-folder"></i>
        </button>
    </a>
</button>
<?php endif; ?>
<?php /*************** Start POCOR-5188 */ ?>
    <?php
        if(!empty($is_manual_exist)):
    ?>

    <a href="<?php echo $is_manual_exist['url']; ?>" target="_blank">
        <button  class="btn btn-xs btn-default icon-big"  data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Help') ?>" >
        <i class="fa fa-question-circle"></i>
        </button>
    </a>
    <?php endif ?>
    <?php /*************** End POCOR-5188 */ ?>
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
<style>
    .data-section{width:20%;}
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

    #institution-staff-attendances-table .sg-theme .ag-cell {
        display: flex;
        flex-flow: column wrap;
        justify-content: center;
    }

    #institution-staff-attendances-table .ag-cell textarea#comment:focus {
        outline: none;
    }

    #institution-staff-attendances-table .ag-cell textarea#comment {
        display: block;
        padding: 5px 10px;
        -webkit-border-radius: 3px;
        border-radius: 3px;
        font-size: 12px;
        height: 70px;
        width: 100%;
        border: 1px solid #CCC;
    }

    #institution-staff-attendances-table .ag-cell .input-select-wrapper {
        margin-bottom: 0;
    }

    #institution-staff-attendances-table .ag-cell .input-select-wrapper select {
        background: #FFFFFF;
        display: block;
    }

    #institution-staff-attendances-table .sg-theme .ag-header-cell.children-period .ag-header-cell-label {
        display: flex;
        justify-content: center;
        padding: 10px 0;
    }

    #institution-staff-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 1px solid #DDDDDD;
        border-bottom: 1px solid #DDDDDD;
        font-weight: 700;
        text-align: center;
        padding: 10px;
    }

    #institution-staff-attendances-table .sg-theme .children-cell {
        text-align: center;
    }

    #institution-staff-attendances-table .sg-theme .ag-row-hover {
        background-color: #FDFEE6 !important;
    }

    .rtl #institution-staff-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 0;
        border-left: 1px solid #DDDDDD;
    }

    #institution-staff-attendances-table .sg-theme .time-view {
        padding: 4px;
        font-size: 13px;
    }

    /* POCOR-9700: shrink-wrap the input-group so the addon hugs the input AND the two stacked
       cells (Time In / Time Out) sit on separate rows. Bootstrap 3's default display:table
       width:100% stretched inside the wide ag-Grid cell and left a gap; inline-flex closed the
       gap but put the two cells side by side and truncated Time Out. Block-level flex with
       fit-content keeps both: block layout = vertical stacking, fit-content = shrink-wrap. */
    #institution-staff-attendances-table .input-group.time {
        display: flex;
        width: fit-content;
        gap: 4px;
    }
    /* POCOR-9700: matching 4px vertical breathing room between Time In and Time Out rows —
       otherwise the two blue addons abut and read as one tall block. Adjacent-sibling selector
       so we never add margin before the first row or after the last. */
    #institution-staff-attendances-table .input-group.time + .input-group.time {
        margin-top: 4px;
    }
    /* POCOR-9700: when the wrapper became display:flex, the addon (a flex item) stretches to
       match the input's height, but its Bootstrap default vertical-align:middle no longer
       applies (vertical-align only honours inline / table-cell). The glyph then sits at the
       top, per its line-height:1. Making the addon a centering flex container itself fixes the
       icon's vertical alignment inside the stretched blue box. */
    #institution-staff-attendances-table .input-group.time .input-group-addon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* POCOR-9700: native HTML5 type=time edit-cell input sized so 24h/12h text fits comfortably. */
    #institution-staff-attendances-table .timPikr {
        width: 100px;
        padding-right: 8px;
        text-align: center;
    }
    /* POCOR-9700: hide the native browser clock indicator — the blue glyphicon addon is the
       single clickable affordance (clicking the addon focuses the input so the spinner / keyboard
       still works). Without this rule Chrome rendered TWO clocks side by side. */
    #institution-staff-attendances-table .timPikr::-webkit-calendar-picker-indicator {
        display: none;
        -webkit-appearance: none;
    }

    #institution-staff-attendances-table .sg-theme .time-view > i {
        margin: 0 8px 0 0;
        font-weight: bold;
    }

    .rtl #institution-staff-attendances-table .sg-theme .time-view > i {
        margin: 0 0 0 8px;
        font-weight: bold;
    }

    #institution-staff-attendances-table .sg-theme .comment-wrapper {
        display: flex;
        flex-flow: row nowrap;
    }

    #institution-staff-attendances-table .sg-theme .comment-wrapper> i {
        padding: 4px;
        font-weight: bold;
    }

    #institution-staff-attendances-table .sg-theme .comment-text {
        text-overflow: ellipsis;
        white-space: normal;
        overflow: auto;
        width: 90%;
        padding: 0 4px;
        font-size: 13px;
    }

    .mobile-split-btn button.btn-default{z-index:9999!important; bottom:40px; position:fixed !important; right:15px;}

     @media screen and (max-width:667px){
              .table-wrapper ::-webkit-scrollbar {
                  -webkit-appearance: none;
              }

              .table-wrapper ::-webkit-scrollbar:vertical {
                  width: 8px;
              }

              .table-wrapper ::-webkit-scrollbar:horizontal {
                  height: 8px;
              }

              .table-wrapper ::-webkit-scrollbar-thumb {
                  background-color: rgba(0, 0, 0, .3);
                  border-radius: 10px;
                  border: 2px solid #ffffff;
              }

              .table-wrapper ::-webkit-scrollbar-track {
                  border-radius: 10px;
                  background-color: #ffffff;
              }
         }

</style>
<div class="panel">
    <div class="panel-body" style="position: relative;">
		<bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.institutionId=<?= $institution_id ?>; $ctrl.excelUrl='<?= $excelUrl;?>'; $ctrl.history=<?= $_history ? $_history : 0 ?>;$ctrl.ownEdit=<?= $_ownEdit ? $_ownEdit : 0 ?>;$ctrl.ownView=<?= $_ownView ? $_ownView : 0 ?>;$ctrl.otherEdit=<?= $_otherEdit ? $_otherEdit : 0 ?>;$ctrl.otherView=<?= $_otherView ? $_otherView : 0 ?>;$ctrl.permissionStaffId=<?= $_permissionStaffId ? $_permissionStaffId : 0 ?>;" float-btn="true">

            <bg-pane class="main-content">
                <div class="alert {{class}}" ng-hide="message == null">
                    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
                </div>
                <div class="overview-box alert attendance-dashboard" ng-class="disableElement" ng-show="$ctrl.action == 'view'">
                    <a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
                    <div class="data-section single-day" ng-show="$ctrl.selectedDay != -1">
                        <i class="kd-staff icon"></i>
                        <div class="data-field">
                            <h4><?= __('Total Staff') ?>:</h4>
                            <h1 class="data-header">{{$ctrl.totalStaff}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <!-- <i class="kd-address-book icon"></i> -->
                        <i class="kd-staff icon"></i>
                        <div class="data-field">
                            <h4><?= __('Total Attendance') ?></h4>
                            <h1 class="data-header">{{$ctrl.allAttendances}}</h1>
                        </div>
                    </div>
                    <div class="data-section">
                        <div class="data-field">
                            <h4><?= __('No. of Present') ?></h4>
                            <h1 class="data-header">{{$ctrl.allPresentCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section">
                        <div class="data-field">
                            <h4><?= __('No. of Staff on Leave') ?></h4>
                            <h1 class="data-header">{{$ctrl.allLeaveCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section"> <?php //POCOR-8135 ?>
                        <div class="data-field">
                            <h4><?= __('No. of Staff Absent') ?></h4>
                            <h1 class="data-header">{{$ctrl.allAbsentCount}}</h1>
                        </div>
                    </div>
<!--                    <div class="data-section">-->
<!--                        <div class="data-field">-->
<!--                            <h4>--><?php //= __('No. of Staff on Late') ?><!--</h4>-->
<!--                            <h1 class="data-header">{{$ctrl.allLateCount}}</h1>-->
<!--                        </div>-->
<!--                    </div>-->
                </div>
                <h4>{{$ctrl.selectedFormattedDayDate}}</h4>
                <div id="institution-staff-attendances-table" class="table-wrapper">
                    <div ng-if="$ctrl.gridReady" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
                </div>
            </bg-pane>
            <bg-pane class="split-content splitter-slide-out splitter-filter" min-size-p="20" max-size-p="30" size-p="20">
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
                        <select ng-disabled="$ctrl.action=='edit'" name="day" ng-options="day.id as day.name for day in $ctrl.dayListOptions" ng-model="$ctrl.selectedDay" ng-change="$ctrl.changeDay();">
                            <option value="" ng-if="$ctrl.dayListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>

                    <h5><?= __('Shift') ?>: </h5>
                    <div class="input-select-wrapper">
                        <select ng-disabled="$ctrl.action=='edit'" name="shift" ng-options="shift.id as shift.name for shift in $ctrl.shiftListOptions" ng-model="$ctrl.selectedShift" ng-change="$ctrl.changeShift();">
                            <option value="" ng-if="$ctrl.shiftListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>

                </div>
            </bg-pane>
        </bg-splitter>
    </div>
</div>
<?php
$this->end();
?>
