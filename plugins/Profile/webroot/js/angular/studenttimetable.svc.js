angular
    .module('studenttimetable.svc', ['kd.data.svc', 'alert.svc'])
    .service('StudentTimetableSvc', StudentTimetableSvc);

StudentTimetableSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function StudentTimetableSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    var controllerScope;
    var kdInitialized = false; //POCOR-9594: guard against re-init when multiple controllers share this singleton

    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

    var models = {
        ScheduleTimetableTable: 'Schedule.ScheduleTimetables',
        ScheduleTimeslotsTable: 'Schedule.ScheduleTimeslots',
        ScheduleLessonsTable: 'Schedule.ScheduleLessons',
        ScheduleLessonDetailsTable: 'Schedule.ScheduleLessonDetails',
        AcademicPeriodTable: 'AcademicPeriod.AcademicPeriods',
        InstitutionClassGradesTable: 'Institution.InstitutionClassGrades',
        InstitutionRoomsTable: 'Institution.InstitutionRooms',
        InstitutionClassSubjectsTable: 'Institution.InstitutionClassSubjects',
        ScheduleTimetableCustomizesTable:'Schedule.ScheduleTimetableCustomizes'
    };

    var service = {
        init: init,

        getTimetable: getTimetable,
        getTimeslots: getTimeslots,
        getWorkingDayOfWeek: getWorkingDayOfWeek,
        getLessonType: getLessonType,
        getTimetableStatus: getTimetableStatus,
        getEducationGrade: getEducationGrade,
        getTimetableLessons: getTimetableLessons,

        saveOverviewData: saveOverviewData,
        saveLessonData: saveLessonData,
        saveLessonDetailCurriculumData: saveLessonDetailCurriculumData,
        checkCurriculumSubjectExistSameTimeslot: checkCurriculumSubjectExistSameTimeslot,
        saveLessonDetailNonCurriculumData: saveLessonDetailNonCurriculumData,
        getInstitutionRooms:getInstitutionRooms,
        getInstitutionClassSubjects:getInstitutionClassSubjects,
        getScheduleTimetableCustomizesTable:getScheduleTimetableCustomizesTable,
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        //POCOR-9594: start - only run KdDataSvc.init once per page; calling it again from a second
        // controller instance destroys in-flight promises from the first controller, causing null rejections
        if (!kdInitialized) {
            KdDataSvc.base(baseUrl);
            KdDataSvc.controllerAction('ScheduleTimetable');
            KdDataSvc.init(models);
            kdInitialized = true;
        }
        //POCOR-9594: end
    }

    function getScheduleTimetableCustomizesTable(timetableId){
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimetableCustomizesTable
            .where({
                institution_schedule_timetable_id: timetableId
            })
            .ajax({success: success, defer: true});
    }

    function getInstitutionRooms(institutionId){
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return InstitutionRoomsTable
            .where({
                institution_id: institutionId,
                accessibility: 1,
                end_year:new Date().getFullYear()
            })
            .ajax({success: success, defer: true});
    }

    function getInstitutionClassSubjects(institutionId, institutionClassId , academicPeriodId){
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return InstitutionClassSubjectsTable
            .find('AllSubjects', {
                institution_class_id:institutionClassId,
            })
            .ajax({success: success, defer: true});
    }

    function getTimetable(timetableId) {
        var success = function(response, deferred) {
            //console.log('@StudentTimetableSvc::getTimetable timetableId=', timetableId, 'status=', response.status, 'data=', response.data); //[TEMP-LOG]
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                //console.error('@StudentTimetableSvc::getTimetable REJECTED - no data field', response.data); //[TEMP-LOG]
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimetableTable
            .get(timetableId)
            .contain(['AcademicPeriods', 'ScheduleIntervals.Shifts.ShiftOptions', 'ScheduleTerms', 'InstitutionClasses']) //POCOR-9594: include shift start_time for timeslot time calculation
            .ajax({success: success, defer: true});
    }

    function getTimeslots(scheduleIntervalId) {
        //console.log('@StudentTimetableSvc::getTimeslots CALLED intervalId=', scheduleIntervalId); //[TEMP-LOG]
        var success = function(response, deferred) {
            //console.log('@StudentTimetableSvc::getTimeslots SUCCESS intervalId=', scheduleIntervalId, 'status=', response.status, 'count=', response.data && response.data.data ? response.data.data.length : 'no data', 'data=', response.data); //[TEMP-LOG]
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                //console.error('@StudentTimetableSvc::getTimeslots REJECTED no data field', response.data); //[TEMP-LOG]
                deferred.reject('There was an error when retrieving the data');
            }
        };
        //var errorCb = function(response) { //[TEMP-LOG]
            //console.error('@StudentTimetableSvc::getTimeslots HTTP ERROR status=', response && response.status, 'data=', response && response.data, 'full=', response); //[TEMP-LOG]
        //}; //[TEMP-LOG]

        return ScheduleTimeslotsTable
            .where({institution_schedule_interval_id: scheduleIntervalId})
            .order(['ScheduleTimeslots.order']) //POCOR-9594: quote reserved word 'order' via table alias to avoid MySQL syntax error
            .ajax({success: success, defer: true});
    }

    function getTimetableLessons(timetableId) {
        var success = function(response, deferred) {
            //console.log('@StudentTimetableSvc::getTimetableLessons timetableId=', timetableId, 'status=', response.status, 'count=', response.data && response.data.data ? response.data.data.length : 'no data', 'data=', response.data); //[TEMP-LOG]
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                //console.error('@StudentTimetableSvc::getTimetableLessons REJECTED', response.data); //[TEMP-LOG]
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleLessonsTable
            .find('allLessons', {
                institution_schedule_timetable_id: timetableId
            })
            .ajax({success: success, defer: true});
    }

    function getWorkingDayOfWeek() {
        var success = function(response, deferred) {
            //console.log('@StudentTimetableSvc::getWorkingDayOfWeek status=', response.status, 'data=', response.data); //[TEMP-LOG]
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                //console.error('@StudentTimetableSvc::getWorkingDayOfWeek REJECTED', response.data); //[TEMP-LOG]
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return AcademicPeriodTable
            .find('workingDayOfWeek')
            .ajax({success: success, defer: true});
    }

    function getLessonType() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleLessonsTable
            .find('lessonType')
            .ajax({success: success, defer: true});
    }

    function getTimetableStatus() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleTimetableTable
            .find('timetableStatus')
            .ajax({success: success, defer: true});
    }

    function getEducationGrade(institutionClassId) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return InstitutionClassGradesTable
            .where({institution_class_id: institutionClassId})
            .ajax({success: success, defer: true});
    }

    // save events
    function saveOverviewData(timetableData) {
        console.log('timetableData', timetableData);
        var saveData = {
            id: timetableData.id,
            name: timetableData.name,
            status: timetableData.status,
            academic_period_id: timetableData.academic_period_id,
            institution_class_id: timetableData.institution_class_id,
            institution_id: timetableData.institution_id,
            institution_schedule_interval_id: timetableData.institution_schedule_interval_id,
            institution_schedule_term_id: timetableData.institution_schedule_term_id
        };

        return ScheduleTimetableTable.edit(saveData);
    }

    function saveLessonData(lessonData) {
        console.log('lessonData', lessonData);
        var saveData = {
            day_of_week: lessonData.day_of_week,
            institution_schedule_timetable_id: lessonData.institution_schedule_timetable_id,
            institution_schedule_timeslot_id: lessonData.institution_schedule_timeslot_id
        };

        return ScheduleLessonsTable.save(saveData);
    }

    function checkCurriculumSubjectExistSameTimeslot(lessonDetailData){

        var searchData = {
            day_of_week: lessonDetailData.day_of_week,
            institution_schedule_timeslot_id: lessonDetailData.institution_schedule_timeslot_id,
            institution_schedule_timetable_id: lessonDetailData.institution_schedule_timetable_id,
            lesson_type: lessonDetailData.lesson_type,
            institution_room_id:lessonDetailData.schedule_curriculum_lesson_room.institution_room_id,
            institution_subject_id: lessonDetailData.schedule_curriculum_lesson.institution_subject_id
        };

        var success = function(response, deferred) {
            console.log('Checkresponse', response);
            if (angular.isDefined(response.data.data)) {
                deferred.resolve(response.data.data);
            } else {
                deferred.reject('There was an error when retrieving the data');
            }
        };

        return ScheduleLessonDetailsTable
            .find('checkSubjectExistSameTimeslot', searchData)
            .ajax({success: success, defer: true});
    }


    function saveLessonDetailCurriculumData(lessonDetailData) {

        var codeOnly = 0;
        //console.log('schedule_curriculum_lesson_details:',lessonDetailData.schedule_curriculum_lesson);
        if(lessonDetailData.schedule_curriculum_lesson.code_only !=0){
            codeOnly = 1;
        }
        var saveData = {
            day_of_week: lessonDetailData.day_of_week,
            institution_schedule_timeslot_id: lessonDetailData.institution_schedule_timeslot_id,
            institution_schedule_timetable_id: lessonDetailData.institution_schedule_timetable_id,
            lesson_type: lessonDetailData.lesson_type,
            schedule_lesson_room: {
                institution_schedule_lesson_detail_id:'1',
                institution_room_id:lessonDetailData.schedule_curriculum_lesson_room.institution_room_id,
            },
            schedule_curriculum_lesson: {
                institution_subject_id: lessonDetailData.schedule_curriculum_lesson.institution_subject_id,
                code_only:codeOnly,
            }
        };


        if (angular.isDefined(lessonDetailData.id)) {
            saveData.id = lessonDetailData.id;
        }

        return ScheduleLessonDetailsTable.save(saveData);
    }

    function saveLessonDetailNonCurriculumData(lessonDetailData) {

        var saveData = {
            day_of_week: lessonDetailData.day_of_week,
            institution_schedule_timeslot_id: lessonDetailData.institution_schedule_timeslot_id,
            institution_schedule_timetable_id: lessonDetailData.institution_schedule_timetable_id,
            lesson_type: lessonDetailData.lesson_type,
            schedule_lesson_room: {
                institution_schedule_lesson_detail_id:'1',
                institution_room_id:lessonDetailData.schedule_non_curriculum_lesson_room.institution_room_id,
            },
            schedule_non_curriculum_lesson: {
                name: lessonDetailData.schedule_non_curriculum_lesson.name
            }
        };

        if (angular.isDefined(lessonDetailData.id)) {
            saveData.id = lessonDetailData.id;
        }

        return ScheduleLessonDetailsTable.save(saveData);
    }
};
