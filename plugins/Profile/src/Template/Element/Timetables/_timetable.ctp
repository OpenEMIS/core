<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('table2excel.js', ['block' => true])?>
<?= $this->Html->script('Profile.angular/timetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Profile.angular/timetable.ctrl', ['block' => true]); ?>

<?php
$this->start('toolbar');
?>
	<a href="javascript:void(0)" ng-show="$ctrl.action == 'view'">
        <button ng-click="$ctrl.ExportTimetable()" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Download') ?>" >
            <i class="fa kd-download" ></i>
        </button>
    </a>
<?php
$this->end();
?>


<?= $this->element('OpenEmis.alert') ?>
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

    .rtl .splitter-filter .timetable-sub-overview > div { position:static;}

    .splitter-filter .lesson-form  {
        border: 1px solid #ddd;
        border-radius: 3px;
        margin-bottom: 15px;
    }

    .splitter-filter .lesson-form .lesson-form-header {
        padding: 10px;
        border-bottom: 1px solid #DDD;
        background-color: #EEE;
        position: relative;
    }

    .splitter-filter .lesson-form .lesson-form-header h5 {
        margin: 0px;
    }

    .splitter-filter .lesson-form .lesson-form-header h5 {
        margin: 0px;
        display: inline-block;
    }

    .splitter-filter .lesson-form .lesson-form-header i {
        position: absolute;
        right: 0;
        padding: 0 10px;
        font-size: 14px;
    }

    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper h6,
    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper .input {
        padding: 0 10px;
        margin-bottom: 15px;
    }

    .splitter-filter .lesson-form .lesson-form-body .lesson-wrapper .input input {
        width: 100%;
        border-radius: 3px;
        border: 1px solid #ddd;
        height: 25px;
        padding: 5px 10px;
    }
    .input-selection-inline{
        width: 100%;
    }
</style>
<div class="" ng-init="$ctrl.shiftDefaultId=<?= $shiftDefaultId; ?>;$ctrl.institutionId=<?= $institutionDefaultId; ?>;$ctrl.academicPeriodId=<?= $academicPeriodId; ?>;$ctrl.staffId=<?= $userId; ?>;$ctrl.scheduleIntervalDefaultId=<?= $scheduleIntervalDefaultId;?>;">
<div class="alert alert-info" ng-show="$ctrl.dayOfWeekList.length <= 0">There are no records.</div>                        
<table id="tblTimetable" ng-if="$ctrl.tableReady" class="timetable-table" ng-show="$ctrl.dayOfWeekList.length > 0">   
                    <thead>
                    
                        <tr class="timetable-header">
                            <th>
                                <h5><?= __('Time') ?></h5>
                            </th>
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
                                    
                                    <div ng-if="lessons.timeslot.start_time==timeslot.start_time && lessons.day_of_week==day.day_of_week">
                                        <div ng-repeat="(key, schedule) in lessons.schedule_lesson_details">
                                            <div class="input-selection-inline" ng-show="schedule.schedule_curriculum_lesson.institution_subject.classes.length > 0 && schedule.schedule_curriculum_lesson.institution_subject.teachers.length > 0 && schedule.schedule_curriculum_lesson.institution_subject.id == lessons._matchingData.InstitutionSubject.id">                                                
                                                <span ng-if="schedule.schedule_curriculum_lesson.code_only == 1"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.education_subject_code}}</strong></span>
                                                <span ng-if="schedule.schedule_curriculum_lesson.code_only ==null || schedule.schedule_curriculum_lesson.code_only == 0"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.name}}</strong></span>
                                                <br ng-show="schedule.schedule_curriculum_lesson.institution_subject.classes.length > 1">
                                                <span ng-show="schedule.schedule_curriculum_lesson.institution_subject.classes.length > 1" ng-repeat="(key, classname) in schedule.schedule_curriculum_lesson.institution_subject.classes">{{classname.name}}<font ng-show="!$last">, </font></span>
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