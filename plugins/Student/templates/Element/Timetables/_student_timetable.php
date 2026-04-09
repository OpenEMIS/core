<?php
//POCOR-9594: scripts loaded once per page via ['block'=>true] deduplication
?>
<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('table2excel.js', ['block' => true]) ?>
<?= $this->Html->script('Profile.angular/studenttimetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Profile.angular/studenttimetable.ctrl', ['block' => true]); ?>
<?php
//POCOR-9594: unique table ID per timetable so each export button targets the right table
$tableId = 'tblTimetable_' . (int)$timetable_id;
?>
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
    .input-selection-inline {
        width: 100%;
    }
    .timetable-block-toolbar {
        margin-bottom: 6px;
    }
</style>
<?php //POCOR-9594: each timetable block gets its own ng-controller instance ?>
<div ng-controller="StudentTimetableCtrl as $ctrl"
     ng-init="$ctrl.timetableId=<?= (int)$timetable_id ?>;$ctrl.institutionId=<?= (int)$institutionDefaultId ?>;$ctrl.academicPeriodId=<?= (int)$academicPeriodId ?>;$ctrl.studentId=<?= (int)$userId ?>;$ctrl.tableId='<?= $tableId ?>';">
    <div class="timetable-block-toolbar">
        <a href="javascript:void(0)">
            <button ng-click="$ctrl.ExportTimetable()" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Download') ?>">
                <i class="fa kd-download"></i>
            </button>
        </a>
        <?php if (!empty($is_manual_exist)): ?>
        <a href="<?= h($is_manual_exist['url']) ?>" target="_blank">
            <button class="btn btn-xs btn-default icon-big" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Help') ?>">
                <i class="fa fa-question-circle"></i>
            </button>
        </a>
        <?php endif ?>
    </div>
    <div class="alert alert-info" ng-show="$ctrl.dayOfWeekList.length <= 0"><?= __('There are no records.') ?></div>
    <table id="<?= $tableId ?>" ng-if="$ctrl.tableReady" class="timetable-table" ng-show="$ctrl.dayOfWeekList.length > 0" style="margin-bottom: 20px;">
        <thead>
            <tr class="timetable-header">
                <th colspan="{{1 + $ctrl.dayOfWeekList.length}}">
                    <div>
                        <h6><?= __('Grade') ?>: {{$ctrl.overviewData.education_grade_name}} | <?= __('Class') ?>: {{$ctrl.institutionClassData.name}}</h6>
                    </div>
                </th>
            </tr>
            <tr class="timetable-header">
                <th><h5><?= __('Time') ?></h5></th>
                <th ng-repeat="(key, day) in $ctrl.dayOfWeekList">
                    <h5>{{day.day.substring(0,3)}}</h5>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr ng-repeat="(key, timeslot) in $ctrl.scheduleTimeslots">
                <td class="timetable-timeslot">
                    <h5>{{$ctrl.toTimeAmPm(timeslot.start_time) | date:'hh:mm a'}} - {{$ctrl.toTimeAmPm(timeslot.end_time) | date:'hh:mm a'}}</h5>
                </td>
                <td class="timetable-lesson" ng-repeat="(key, day) in $ctrl.dayOfWeekList">
                    <span ng-repeat="(key, lessons) in $ctrl.timetableLessons">
                        <div ng-if="lessons.institution_schedule_timeslot_id==timeslot.id && lessons.day_of_week==day.day_of_week"><!-- POCOR-9594: match by timeslot id -->
                            <div ng-repeat="(key, schedule) in lessons.schedule_lesson_details">
                                <div class="input-selection-inline">
                                    <span><strong>{{schedule.schedule_non_curriculum_lesson.name}}</strong></span>
                                    <span ng-if="schedule.schedule_curriculum_lesson.code_only == 1"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.education_subject_code}}</strong></span>
                                    <span ng-if="schedule.schedule_curriculum_lesson.code_only == null || schedule.schedule_curriculum_lesson.code_only == 0"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.name}}</strong></span>
                                    <br ng-show="schedule.schedule_curriculum_lesson.institution_subject.classes.length > 1">
                                    <span ng-show="schedule.schedule_curriculum_lesson.institution_subject.classes.length > 1" ng-repeat="(key, classname) in schedule.schedule_curriculum_lesson.institution_subject.classes">{{classname.name}}<font ng-show="!$last">, </font></span>
                                    <br ng-show="schedule.schedule_curriculum_lesson.institution_subject.teachers.length > 0">
                                    <br ng-show="schedule.schedule_curriculum_lesson.institution_subject.teachers.length > 0">
                                    <span ng-repeat="(key, teacher) in schedule.schedule_curriculum_lesson.institution_subject.teachers">{{teacher.name}}<font ng-show="!$last">, </font></span>
                                    <br>
                                    <span>{{schedule.schedule_lesson_room.institution_room.name}}</span>
                                </div>
                            </div>
                        </div>
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</div>