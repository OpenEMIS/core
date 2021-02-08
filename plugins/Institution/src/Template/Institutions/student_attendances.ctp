<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_attendances/institution.student.attendances.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_attendances/institution.student.attendances.ctrl', ['block' => true]); ?>

<?php
$this->start('toolbar');
?>

<?php if ($_excel) : ?>
    <a ng-href="{{$ctrl.excelExportAUrl}}" ng-show="$ctrl.action == 'view'" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Export"><i class="fa kd-export"></i></a>
<?php endif; ?>

<?php if ($_import) : ?>
    <a href="<?=$importUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Import Absences') ?>" >
            <i class="fa kd-import"></i>
        </button>
    </a>
</button>
<?php endif; ?>

<?php if ($_edit) : ?>
    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="$ctrl.action == 'view' && $ctrl.selectedDay != -1 && $ctrl.selectedDay <= $ctrl.currentDayMonthYear && !$ctrl.schoolClosed && $ctrl.classStudentList.length > 0 && $ctrl.permissionEdit == 1" ng-click="$ctrl.onEditClick()">
        <i class="fa kd-edit"></i> 
    </button>

    <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="$ctrl.action == 'edit' && $ctrl.classStudentList.length > 0" ng-click="$ctrl.onBackClick()">
        <i class="fa kd-back"></i>
    </button>
<?php endif; ?>
<?php 
$session = $this->Session;
$superAdmin = $session->read('Auth.User.super_admin');
$is_connection_is_online = $session->read('is_connection_stablished');
?>
<?php if(($is_connection_is_online == 1 && $is_button_accesible == 1)) :  ?>
<?php if ($archiveUrl) : ?>
    <a href="<?=$archiveUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Archive') ?>" >
            <i class="fa fa-folder"></i>
        </button>
    </a>
</button>
<?php endif; ?>
<?php endif; ?>

<?php
$this->end();
?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<?= $this->element('OpenEmis.alert') ?>

<style>
    .attendance-dashboard .data-section.single-day {
        width: 24%;
    }

    .attendance-dashboard .data-section i.fa-address-book-o {
        background-color: transparent;
        border: none;
        color: #999;
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

    .splitter-filter .input-selection.attendance.disabled {
        background-color: #f2f2f2!important;
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

    .rtl #institution-student-attendances-table .ag-cell textarea#comment {
        font-size: 14px;
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
        <bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.institutionId=<?= $institution_id ?>;$ctrl.exportexcel='<?=$excelUrl ?>';" float-btn="true">
           
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
                    <div class="data-section single-day" ng-show="$ctrl.selectedDay != -1">
                        <div class="data-field">
                            <h4><?= __('No. of Students Late') ?></h4>
                            <h1 class="data-header">{{$ctrl.lateCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <i class="fa fa-address-book-o"></i>
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
                            <h4><?= __('No. of Absence') ?></h4>
                            <h1 class="data-header">{{$ctrl.allAbsenceCount}}</h1>
                        </div>
                    </div>
                    <div class="data-section" ng-show="$ctrl.selectedDay == -1">
                        <div class="data-field">
                            <h4><?= __('No. of Late') ?></h4>
                            <h1 class="data-header">{{$ctrl.allLateCount}}</h1>
                        </div>
                    </div>
                </div>
                <div id="institution-student-attendances-table" class="table-wrapper">
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
                    <h5><?= __('Education Grade') ?>: </h5>
                    <div class="input-select-wrapper">

                        <select ng-disabled="$ctrl.action=='edit'" name="education_grade" ng-options="education_grade.id as education_grade.name for education_grade in $ctrl.educationGradeListOptions" ng-model="$ctrl.selectedEducationGrade" ng-change="$ctrl.changeEducationGrade();">
                            <option value="" ng-if="$ctrl.educationGradeListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5 ng-if="$ctrl.isMarkableSubjectAttendance==true"><?= __('Subjects') ?>: </h5>
                    <div class="input-select-wrapper" ng-if="$ctrl.isMarkableSubjectAttendance==true">
                        <select ng-disabled="$ctrl.action=='edit'" name="subject" ng-options="subject.id as subject.name for subject in $ctrl.subjectListOptions" ng-model="$ctrl.selectedSubject" ng-change="$ctrl.changeSubject();">
                            <option value="" ng-if="$ctrl.subjectListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                    <h5 ng-if="$ctrl.isMarkableSubjectAttendance==''"><?= __('Attendance per day') ?>: </h5>
                    <div class="input" ng-if="$ctrl.isMarkableSubjectAttendance==''">
                        <div class="input-selection attendance" ng-class="{'disabled': $ctrl.action=='edit' || $ctrl.selectedDay==-1}">
                            <div class="input" ng-repeat="attendance_period in $ctrl.attendancePeriodOptions">
                                <input ng-disabled="$ctrl.action=='edit' || $ctrl.selectedDay==-1" kd-checkbox-radio="{{attendance_period.name}}" ng-model="$ctrl.selectedAttendancePeriod" ng-change="$ctrl.changeAttendancePeriod();" value="{{attendance_period.id}}" type="radio" name="attendance_per_day">
                            </div>
                        </div>
                    </div>
                </div>
            </bg-pane>
        </bg-splitter>
    </div>
</div>


<?php
$this->end();
?>
