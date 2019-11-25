<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('table2excel.js', ['block' => true])?>
<?= $this->Html->script('Schedule.angular/timetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.ctrl', ['block' => true]); ?>

<?php

$this->start('toolbar');
?>
    <?php if ($_back) : ?>
        <a href="<?= $_back ?>" ng-show="$ctrl.action == 'view' || $ctrl.action == 'edit'">
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
   <a ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onCustomizeClicked()">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Customize') ?>" >
            <i class="fa fa-paint-brush"></i>
        </button>
    </a>
    <a href="javascript:void(0)" ng-show="$ctrl.action == 'view'">
        <button ng-click="$ctrl.ExportTimetable()" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Download') ?>" >
            <i class="fa kd-download" ></i>
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
        padding:5px;
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
        border-radius:5px;
        margin-bottom:5px;
    }
    
    .splitter-filter .timetable-sub-customize input {
        width: 50%;
    }
    .splitter-filter .timetable-sub-customize .input-form-wrapper  .input-inline.left{
       float: left;
       width: 50%;
       text-align: left;
    }
    .splitter-filter .timetable-sub-customize .input-form-wrapper  .input-inline.right{
        float: right;
        width: 50%;
        text-align: right;
    }
    table thead{
        background-color:{{$ctrl.timetableCustomizeColors['timetable_header_bg']}};        
    }
    table thead h2, table thead h5, table thead h6{
        color:{{$ctrl.timetableCustomizeColors['timetable_header_txt']}};
    }
	.onDeleteTimeTableCellData{float:right;}
	.onDeleteTimeTableCellData .fa-trash{z-index:9999; color:red; cursor: pointer; display:none;}
	.input-selection-inline:hover .fa-trash{
		display:block;
	}
</style>
 
<div class="panel">
    <div class="panel-body" style="position: relative;">
        <bg-splitter orientation="horizontal" class="content-splitter timetable" elements="getSplitterElements" ng-init="$ctrl.timetableId=<?= $timetable_id; ?>;$ctrl.institutionId=<?= $institutionDefaultId; ?>;$ctrl.academicPeriodId=<?= $academicPeriodId; ?>; $ctrl.action='<?= $_action; ?>';" float-btn="false" collapse="{{$ctrl.hideSplitter}}">
            <bg-pane class="main-content" min-size-p="70" max-size-p="100">
                <table id="tblTimetable" ng-if="$ctrl.tableReady" class="timetable-table">
                    <thead>
                        <tr class="timetable-header">
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
                                <h5>{{day.day.substring(0,3)}}</h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="(key, timeslot) in $ctrl.scheduleTimeslots">
                            <td class="timetable-timeslot">
                                <h5>{{$ctrl.toTimeAmPm(timeslot.start_time) | date:'hh:mm a'}} - {{$ctrl.toTimeAmPm(timeslot.end_time) | date:'hh:mm a'}}</h5>
                            </td>
                            <td class="timetable-lesson {{$ctrl.getClassName(timeslot, day)}} {{($ctrl.getClassName(timeslot, day) == $ctrl.currentSelectedCell.class) ? 'lesson-selected' : ''}}" ng-repeat="(key, day) in $ctrl.dayOfWeekList" ng-click="$ctrl.onTimeslotCellClicked(timeslot, day)">
                                <span ng-repeat="(key, lessons) in $ctrl.timetableLessons">
                                    
                                    <div ng-if="lessons.timeslot.start_time==timeslot.start_time && lessons.day_of_week==day.day_of_week">
                                        <div ng-repeat="(key, schedule) in lessons.schedule_lesson_details">
										 
                                            <div class="input-selection-inline" style="background-color:{{$ctrl.timetableCustomizeColors['subject_bg_'+schedule.schedule_curriculum_lesson.institution_subject_id]}};color:{{$ctrl.timetableCustomizeColors['subject_txt_'+schedule.schedule_curriculum_lesson.institution_subject_id]}};">
											
												<div class="onDeleteTimeTableCellData"><i class="fa fa-trash" ng-click="$ctrl.onDeleteTimeTableCellData($event,schedule.id)"></i></div>
												
                                                <span><strong>{{schedule.schedule_non_curriculum_lesson.name}}</strong></span>
                                                <span ng-if="schedule.schedule_curriculum_lesson.code_only == 1"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.education_subject_code}}</strong></span>
                                                <span ng-if="schedule.schedule_curriculum_lesson.code_only ==null || schedule.schedule_curriculum_lesson.code_only == 0"><strong>{{schedule.schedule_curriculum_lesson.institution_subject.name}}</strong></span>
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
            </bg-pane>
            <bg-pane class="split-content splitter-slide-out split-with-btn splitter-filter" min-size-p="30" max-size-p="30" size-p="40">
                <div class="split-content-header">
                    <h3>{{$ctrl.splitterContent}}</h3>
                    <div class="split-content-btn">
                        <button href="#" class="btn btn-outline" ng-click="$ctrl.onSplitterClose()">
                            <i class="fa fa-close fa-lg"></i>
                        </button>
                    </div>

                    <div ng-if="$ctrl.splitterContent == $ctrl.SPLITTER_LESSONS" class="timetable-sub-lessons">
                        <div class="lesson-type">
                            <h5><?= __('Type') ?>: </h5>
                            <div style="display: inline-block; width: 100%;" class="input select">
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
                                    <h5>{{$ctrl.getLessonTitle(lesson.lesson_type)}}</h5>
                                    <i class="fa fa-trash" ng-click="$ctrl.onDeleteLessonData(key)"></i>
                                </div>
                                <!-- Non Curriculum Lessons -->
                                <div ng-if="lesson.lesson_type == $ctrl.NON_CURRICULUM_LESSON" class="lesson-form-body">
                                    <div class="lesson-wrapper non-curriculum lesson-name">
                                        <h6><?= __('Name') ?> </h6>
                                        <div class="input text required">
                                            <input type="text" ng-class="{'form-error':$ctrl.errorMessageNonCurriculum[key].length > 0}" ng-required="true" ng-model="lesson.schedule_non_curriculum_lesson.name" />
                                            <span class="error-message" ng-show="$ctrl.errorMessageNonCurriculum[key].length >0">{{$ctrl.errorMessageNonCurriculum[key]}}</span>
                                        </div>
                                    </div>
                                    <div class="lesson-wrapper non-curriculum institution-room">
                                        <h6><?= __('Room') ?> </h6>
                                        <div class="input text required select" >
                                            <div class="input-select-wrapper">
                                            <select ng-model="lesson.schedule_non_curriculum_lesson_room.institution_room_id" ng-change="$ctrl.onUpdateLessonData(key, $ctrl.NON_CURRICULUM_LESSON)">
                                                <option value="">Select Room</option>
                                                <option ng-repeat="(key, room) in $ctrl.institutionRooms" value="{{room.id}}">{{room.name}}</option>
                                               
                                            </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Curriculum Lessons -->
                                <div ng-if="lesson.lesson_type == $ctrl.CURRICULUM_LESSON" class="lesson-form-body">
                                    <div class="lesson-wrapper curriculum lesson-subject">
                                        <h6><?= __('Subject') ?> </h6>
                                        <div class="input text required select">
                                            <div class="input-select-wrapper">
                                            <select ng-required="true" ng-class="{'form-error':$ctrl.errorMessageCurriculum[key].length > 0}" ng-model="lesson.schedule_curriculum_lesson.institution_subject_id">
                                                <option value="">Select Subject</option>
                                                <option ng-repeat="(key, subject) in $ctrl.institutionClassSubjects" value="{{subject.institution_subject_id}}">{{subject.institution_subject_name}}</option>
                                               
                                            </select>
                                                <span class="error-message" ng-show="$ctrl.errorMessageCurriculum[key].length > 0">{{$ctrl.errorMessageCurriculum[key]}}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lesson-wrapper curriculum lesson-code">
                                        <h6><?= __('Display') ?> </h6>
                                        <div class="input">
                                            <input kd-checkbox-radio="Code Only" value="0" ng-model="lesson.schedule_curriculum_lesson.code_only" type="checkbox">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="lesson-wrapper curriculum institution-room">
                                        <h6><?= __('Room') ?> </h6>
                                        <div class="input text required select">
                                            <div class="input-select-wrapper">
                                            <select ng-model="lesson.schedule_curriculum_lesson_room.institution_room_id"  ng-change="$ctrl.onUpdateLessonData(key, $ctrl.CURRICULUM_LESSON)">
                                                <option value="">Select Room</option>
                                                <option ng-repeat="(key, room) in $ctrl.institutionRooms" value="{{room.id}}">{{room.name}}</option>
                                               
                                            </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div ng-if="$ctrl.splitterContent == $ctrl.SPLITTER_OVERVIEW" class="timetable-sub-overview">
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
                    
                    <div ng-if="$ctrl.splitterContent == $ctrl.SPLITTER_CUSTOMIZE" class="timetable-sub-overview timetable-sub-customize">
                       <div class="input input-selection-inline">
                        <label><?= __('Timetable Header') ?></label>
                        <hr>
                     
                        <div class="input-form-wrapper">
                           <div class="input-inline left"><label><?= __('Background') ?></label></div>
                           <div class="input-inline right">
                           <i class="fa kd-bg-color"></i>                            
                            <input type="color" size="10" ng-model="$ctrl.customizeFormData['timetable_header_bg']" value="{{$ctrl.timetableCustomizeColors.timetable_header_bg}}" name="timetable_header_background">
                           </div>
                        </div>

                        <div class="input-form-wrapper">
						
                           <div class="input-inline left"><label><?= __('Text') ?></label></div>
                           <div class="input-inline right">
                            <i class="fa kd-font-color"></i>
							
                            <input type="color" size="10" ng-model="$ctrl.customizeFormData['timetable_header_txt']" value="{{$ctrl.timetableCustomizeColors.timetable_header_txt}}"  name="timetable_header_text">
                           </div>
                        </div>
                        </div>
                        <div class="input input-selection-inline" ng-repeat="(key, subject) in $ctrl.institutionClassSubjects">
                            <label>{{subject.institution_subject_name}}</label>
                            
                        <hr>
                        
                        <div class="input-form-wrapper">
                           <div class="input-inline left"><label><?= __('Background') ?></label></div>
                           <div class="input-inline right">
                           <i class="fa kd-bg-color"></i>                            
                            <input type="color" size="10" ng-model="$ctrl.customizeFormData['subject_bg_'+subject.institution_subject_id]" name="subject_background_{{subject.institution_subject_id}}" value="{{$ctrl.timetableCustomizeColors.timetable_header_bg}}">
                           </div>
                        </div>
                        <div class="input-form-wrapper">
                           <div class="input-inline left"><label><?= __('Text') ?></label></div>
                           <div class="input-inline right">
                            <i class="fa kd-font-color"></i>
                            <input type="color" size="10" ng-model="$ctrl.customizeFormData['subject_txt_'+subject.institution_subject_id]" name="subject_text_{{subject.institution_subject_id}}" value="{{$ctrl.timetableCustomizeColors['subject_txt_'+subject.institution_subject_id]}}">
                           </div>
                        </div>						
                        </div>	
						<hr>
						<div class="input-form-wrapper">
                           <div class="input-inline left"></div>
                           <div class="input-inline right">
                            <button type="button" class="btn btn-primary" ng-click="$ctrl.onSaveTitmetableCustomizeData()">Send</button>
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
