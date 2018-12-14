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

    .timetable-table .timetable-lesson:hover {
        background-color: #fff;
    }

</style>
 
<div class="panel">
    <div class="panel-body" style="position: relative;">
        <bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.timetableId=<?= $timetable_id; ?>; $ctrl.action='<?= $_action; ?>';" float-btn="false" collapse="{{$ctrl.hideSplitter}}">
            <bg-pane class="main-content">
                <table ng-if="$ctrl.tableReady" class="timetable-table">
                    <thead>
                        <tr class="timetable-header title">
                            <th colspan="{{1 + $ctrl.dayOfWeekList.length}}">
                                <div>
                                    <h2>{{$ctrl.timetableData.name}}</h2>
                                    <h6>Grade: | Class: {{$ctrl.institutionClassData.name}}</h6>
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
                            <td class="timetable-lesson {{$ctrl.getClassName(timeslot, day)}}" ng-repeat="(key, day) in $ctrl.dayOfWeekList" ng-click="$ctrl.onTimeslotCellClicked(timeslot, day)" class="">
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
                        <div>This is the lesson splitter content</div>
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
                            <div ng-repeat="(key, lesson) in $ctrl.currentLessonList">
                                <div class="lesson-form-header">
                                    Header
                                </div>
                                <div>
                                    Body
                                </div>
                            </div>
                        </div>
                    </div>

                    <div ng-if="$ctrl.splitterContent == 'Overview'" class="timetable-sub-overview">
                        <div>This is the overview splitter content</div>
                    </div>
                </div>
            </bg-pane>
        </bg-splitter>
    </div>
</div>

<?php
$this->end();
?>
