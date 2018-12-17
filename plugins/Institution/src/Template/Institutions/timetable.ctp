<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.ctrl', ['block' => true]); ?>

<?php

$this->start('toolbar');
?>
    <?php if ($_back) : ?>
        <a href="<?= $_back ?>" ng-show="$ctrl.action == 'edit'">
            <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back') ?>" >
                <i class="fa kd-back" ></i>
            </button>
        </a>
    <?php endif; ?>

    <a ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onInfoClicked()">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Overview') ?>" >
            <i class="fa fa-info"></i>
        </button>
    </a>
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
<div class="alert {{class}}" ng-hide="message == null">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
</div>

<style>
    .timetable-table {
        width: 100%;
        border: 1px solid #DDD;
        table-layout: fixed;
    }

    .timetable-table th, .timetable-table td {
        border: 1px solid #DDD;
    }

    .timetable-table .timetable-header * {
        text-align: center;
    }

    .timetable-table .timetable-timeslot {
        text-align: center;
        width: 200px;
    }

    .timetable-table .timetable-header.title,
    .timetable-table .timetable-lesson {
        background-color: #EEE;
    }

    .timetable-table .timetable-lesson:hover,
    .timetable-table .lesson-selected {
        background-color: #fff;
    }

    .splitter-filter .timetable-sub-overview input {
        width: 100%;
        height: 30px;
        border: 1px solid #ccc;
        border-radius: 3px;
        margin-bottom: 15px;
        padding: 5px 10px;
    }

    .splitter-filter .timetable-sub-overview > div {
        position: relative;
    }

    .splitter-filter .timetable-sub-overview > div > i {
        position: absolute;
        top: 0;
        right: 0;
    }

    .splitter-filter .lesson-form  {
        border: 1px solid #ddd;
        border-radius: 3px;
        margin-bottom: 15px;
    }

    .splitter-filter .lesson-form .lesson-form-header h5 {
        margin: 0px;
        padding: 10px;
        border-bottom: 1px solid #DDD;
        background-color: #EEE;
    }

    .splitter-filter .lesson-form .lesson-form-body {
        margin: 10px 0;
    }

    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper h6,
    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper .input {
        padding: 0 10px;
    }

    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper .input input {
        width: 100%;
        border-radius: 3px;
        border: 1px solid #ddd;
        height: 25px;
    }
    
</style>
 
<div class="panel">
    <div class="panel-body" style="position: relative;">
        <bg-splitter orientation="horizontal" class="content-splitter timetable" elements="getSplitterElements" ng-init="$ctrl.timetableId=<?= $timetable_id; ?>; $ctrl.action='<?= $_action; ?>';" float-btn="false" collapse="{{$ctrl.hideSplitter}}">
            <bg-pane class="main-content">
                <table ng-if="$ctrl.tableReady" class="timetable-table">
                    <thead>
                        <tr class="timetable-header title">
                            <th colspan="{{1 + $ctrl.dayOfWeekList.length}}">
                                <div>
                                    <h2>{{$ctrl.overviewData.name}}</h2>
                                    <h6>Grade: {{$ctrl.overviewData.education_grade_name}} | Class: {{$ctrl.institutionClassData.name}}</h6>
                                </div>
                            </th>
                        </tr>
                        <tr class="timetable-header">
                            <th>
                                <h5><?= __('Time') ?></h5>
                            </th>
                            <th ng-repeat="(key, day) in $ctrl.dayOfWeekList">
                                <h5>{{day.day}}</h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="(key, timeslot) in $ctrl.scheduleTimeslots">
                            <td class="timetable-timeslot">
                                <h5>{{timeslot.start_time}} - {{timeslot.end_time}}</h5>
                            </td>
                            <td class="timetable-lesson {{$ctrl.getClassName(timeslot, day)}} {{($ctrl.getClassName(timeslot, day) == $ctrl.currentSelectedCell.class) ? 'lesson-selected' : ''}}" ng-repeat="(key, day) in $ctrl.dayOfWeekList" ng-click="$ctrl.onTimeslotCellClicked(timeslot, day)" class="">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </bg-pane>
            <bg-pane class="split-content splitter-slide-out split-with-btn splitter-filter">
                <div class="split-content-header">
                    <h3>{{$ctrl.splitterContent}}</h3>
                    <div class="split-content-btn">
                        <button href="#" class="btn btn-outline" ng-click="$ctrl.onSplitterClose()">
                            <i class="fa fa-close fa-lg"></i>
                        </button>
                    </div>

                    <div ng-if="$ctrl.splitterContent == 'Lessons'" class="timetable-sub-lessons">
                        <div class="lesson-type">
                            <h5><?= __('Type') ?>: </h5>
                            <div style="display: inline-block; width: 100%;">
                                <div class="input-select-wrapper" style="width: 90%;">
                                    <select name="lesson_type" ng-options="lesson.id as lesson.name for lesson in $ctrl.lessonType" ng-model="$ctrl.selectedLessonType">
                                    </select>
                                </div>
                                <div style="display: inline-block; margin:0 5px;" ng-click="$ctrl.onAddLessonType()">
                                    <i class="fa fa-plus"></i>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="lesson-list">
                            <div ng-repeat="(key, lesson) in $ctrl.currentLessonList" class="lesson-form">
                                <div class="lesson-form-header">
                                    <h5>{{$ctrl.getLessonTitle(lesson.type)}}</h5>
                                </div>
                                <!-- Non Curriculum Lessons -->
                                <div ng-if="lesson.type == 2" class="lesson-form-body">
                                    <div class="lesson-wrapper non-curriculum lesson-name">
                                        <h6><?= __('Name') ?> </h6>
                                        <div class="input text required">
                                            <input type="text"/>
                                        </div>
                                    </div>
                                    <div class="lesson-wrapper non-curriculum institution-room">
                                        <h6><?= __('Room') ?> </h6>
                                        <div class="input text required">
                                            <input type="text"/>
                                        </div>
                                    </div>
                                </div>
                                <!-- Curriculum Lessons -->
                                <div ng-if="lesson.type == 1" class="lesson-form-body">
                                    <div class="lesson-wrapper curriculum lesson-name">
                                        <h6><?= __('Name1') ?> </h6>
                                        <div class="input text required">
                                            <input type="text"/>
                                        </div>
                                    </div>
                                    <div class="lesson-wrapper curriculum institution-room">
                                        <h6><?= __('Room1') ?> </h6>
                                        <div class="input text required">
                                            <input type="text"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div ng-if="$ctrl.splitterContent == 'Overview'" class="timetable-sub-overview">
                        <div class="academic-period">
                            <h5><?= __('Academic Period') ?>: </h5>
                            <div class="input text required">
                                <input type="text" disabled="disabled" value="{{$ctrl.overviewData.academic_period_name}}"/>
                            </div>
                        </div>
                        <div class="term">
                            <h5><?= __('Term') ?>: </h5>
                            <div class="input text required">
                                <input type="text" disabled="disabled" value="{{$ctrl.overviewData.term_name}}"/>
                            </div>
                        </div>
                        <div class="timetable-status">
                            <h5><?= __('Status') ?>: </h5>
                            <i ng-if="$ctrl.overviewError.status" class="fa fa-exclamation-circle fa-lg icon-red" tooltip-placement="bottom" uib-tooltip="{{$ctrl.overviewError.status}}" tooltip-append-to-body="true" tooltip-class="tooltip-red"></i>
                            <div class="input-select-wrapper">
                                <select name="lesson_type" ng-options="status.id as status.name for status in $ctrl.timetableStatus" ng-model="$ctrl.overviewData.status" ng-change="$ctrl.onUpdateOverviewData('status')">
                                </select>
                            </div>
                        </div>
                        <div class="name">
                            <h5><?= __('Name') ?>: </h5>
                            <i ng-if="$ctrl.overviewError.name" class="fa fa-exclamation-circle fa-lg icon-red" tooltip-placement="bottom" uib-tooltip="{{$ctrl.overviewError.name}}" tooltip-append-to-body="true" tooltip-class="tooltip-red"></i>
                            <div class="input text required">
                                <input type="text" ng-model="$ctrl.overviewData.name" ng-blur="$ctrl.onUpdateOverviewData('name')"/>
                            </div>
                        </div>
                        <div class="education-grade">
                            <h5><?= __('Grade') ?>: </h5>
                            <div class="input text required">
                                <input type="text" disabled="disabled" value="{{$ctrl.overviewData.education_grade_name}}"/>
                            </div>
                        </div>
                        <div class="institution-class">
                            <h5><?= __('Class') ?>: </h5>
                            <div class="input text required">
                                <input type="text" disabled="disabled" value="{{$ctrl.overviewData.class_name}}"/>
                            </div>
                        </div>
                        <div class="schedule-interval">
                            <h5><?= __('Interval') ?>: </h5>
                            <div class="input text required">
                                <input type="text" disabled="disabled" value="{{$ctrl.overviewData.interval_name}}"/>
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
