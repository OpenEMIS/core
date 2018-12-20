angular.module('timetable.ctrl', ['utils.svc', 'alert.svc', 'timetable.svc'])
    .controller('TimetableCtrl', TimetableController);

TimetableController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'TimetableSvc'];

function TimetableController($scope, $q, $window, $http, UtilsSvc, AlertSvc, TimetableSvc) {
    var vm = this;

    // const
    vm.CURRICULUM_LESSON = 1;
    vm.NON_CURRICULUM_LESSON = 2;
    vm.DELETE_LESSON = -1;

    vm.SPLITTER_OVERVIEW = 'Overview';
    vm.SPLITTER_LESSONS = 'Lessons';

    // config
    vm.action = 'view';
    vm.hideSplitter = 'true';
    vm.splitterContent = vm.SPLITTER_OVERVIEW; // Overview/Lessons
    vm.tableReady = false;

    // options and data
    vm.timetableId = '';
    vm.timetableData = {};
    vm.institutionClassData = {};
    vm.scheduleIntervalData = {};
    vm.scheduleTermData = {};
    vm.scheduleTimeslots = [];
    vm.dayOfWeekList = [];
    vm.timetableStatus = [];
    vm.educationGradeList = [];

    // for overview data - display and saving
    vm.overviewData = {};
    vm.overviewError = {};

    vm.currentSelectedCell = {
        day_of_week: {},
        timeslot: {},
        class: ''
    };

    // for lessons data - display and saving
    vm.lessonList = {};

    vm.lessonType = [];
    vm.selectedLessonType = 0;

    /*
        Non-Curriculum Lesson structure
        {
            type: NON_CURRICULUM_LESSON
            name: '',
            institution_room_id: 
        }
    
        Curriculum Lesson structure
        {
            type: CURRICULUM_LESSON
            institution_subject_id: ,
            code_only: bool,
            institution_room_id: 
        }
     */
    vm.currentLessonList = [];

    // ready
    angular.element(document).ready(function () {
        console.log('action', vm.action);
        console.log('timetableId', vm.timetableId);

        TimetableSvc.init(angular.baseUrl, $scope);
        UtilsSvc.isAppendLoader(true);
        if (vm.timetableId != null) {
            TimetableSvc.getTimetable(vm.timetableId)
            .then(function(timetableData) {
                console.log('getTimetable', timetableData);
                vm.timetableData = timetableData;
                vm.institutionClassData = timetableData.institution_class;
                vm.scheduleIntervalData = timetableData.schedule_interval;
                vm.scheduleTermData = timetableData.schedule_term;

                vm.resetOverviewData();

                return TimetableSvc.getTimeslots(vm.timetableData.institution_schedule_interval_id);
            }, vm.error)
            .then(function(timeslotsData) {
                console.log('getTimeslots', timeslotsData);
                vm.scheduleTimeslots = timeslotsData;

                return TimetableSvc.getWorkingDayOfWeek();
            }, vm.error)
            .then(function(workingDayOfWeek) {
                console.log('getWorkingDayOfWeek', workingDayOfWeek);
                vm.dayOfWeekList = workingDayOfWeek;

                return TimetableSvc.getTimetableLessons(vm.timetableData.id);
            }, vm.error)
            .then(function(allLessons) {
                console.log('getTimetableLessons', allLessons);

                return TimetableSvc.getEducationGrade(vm.timetableData.institution_class_id);
            }, vm.error)
            .then(function(educationGrades) {
                console.log('getEducationGrade', educationGrades);
                vm.educationGradeList = educationGrades;
                vm.overviewData.education_grade_name = '';

                for (var i = 0; i < educationGrades.length; i++) {
                    vm.overviewData.education_grade_name += educationGrades[i].grade_name;
                    if (i != educationGrades.length - 1) {
                        vm.overviewData.education_grade_name += ', ';
                    }
                }

                vm.tableReady = true;
                return TimetableSvc.getLessonType();
            })
            .then(function(lessonType) {
                console.log('getLessonType', lessonType);
                vm.lessonType = lessonType;

                return TimetableSvc.getTimetableStatus();
            }, vm.error)
            .then(function(timetableStatus) {
                console.log('getTimetableStatus', timetableStatus);
                vm.timetableStatus = timetableStatus;

            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
                AlertSvc.info($scope, 'Timetable will be automatically saved.');
            })
        }

    });

    // error
    vm.error = function (error) {
        AlertSvc.error($scope, error);
        console.log('error', error);
        return $q.reject(error);
    }

    // save events
    vm.saveOverviewData = function(field) {
        UtilsSvc.isAppendLoader(true);
        vm.resetOverviewError();
        TimetableSvc.saveOverviewData(vm.overviewData)
        .then(function(response) {
            // check if has error
            var data = response.data;

            if (angular.isObject(data.error) && Object.keys(data.error).length > 0) {
                for (var fieldKey in data.error) {
                    var errorField = data.error[fieldKey];

                    var tempError = [];
                    for (var errorRule in errorField) {
                        tempError.push(errorField[errorRule]);
                    }
                    vm.overviewError[fieldKey] = tempError.join(', ');
                }
            } else {
                vm.updateTimetableData(field, data.data[field]);
            }
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.saveLessonDetails = function(lessonDetail, lessonType) {
        console.log('lessonDetail', vm.lessonDetail);

        var responseData;
        
        UtilsSvc.isAppendLoader(true);
        if (lessonType == vm.NON_CURRICULUM_LESSON) {
            TimetableSvc.saveLessonDetailNonCurriculumData(lessonDetail)
            .then(function(response) {
                console.log('non lesson', response);
            })
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            });
        } else { // vm.CURRICULUM_LESSON
            TimetableSvc.saveLessonDetailCurriculumData(lessonDetail)
            .then(function(response) {
                console.log('lesson', response);
            })
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            });
        }
    }

    vm.saveLessonSlot = function() {
        console.log('saveLessonSlot', vm.currentSelectedCell);

        var lessonData = {
            day_of_week: vm.currentSelectedCell.day_of_week.day_of_week,
            institution_schedule_timetable_id: vm.timetableId,
            institution_schedule_timeslot_id: vm.currentSelectedCell.timeslot.id
        };

        UtilsSvc.isAppendLoader(true);
        TimetableSvc.saveLessonData(lessonData)
        .then(function(response) {
            var data = response.data;
        })
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        })
    }   

    // button/change events
    vm.onUpdateOverviewData = function(field) {
        console.log('onUpdateOverviewData', vm.overviewData);
        vm.saveOverviewData(field);
    }

    vm.onUpdateLessonData = function(key, lessonType) {
        console.log('saveLessonDetails', vm.currentLessonList[key]);
        vm.saveLessonDetails(vm.currentLessonList[key], lessonType);
    }

    vm.onDeleteLessonData = function(key) {
        console.log('onDeleteLessonData', vm.currentLessonList[key]);
        
    }

    vm.onInfoClicked = function() {
        vm.splitterContent = vm.SPLITTER_OVERVIEW;
        vm.hideSplitter = 'false';
    }

    vm.onTimeslotCellClicked = function(timeslot, day) {
        vm.splitterContent = vm.SPLITTER_LESSONS;
        var selectedClass = vm.getClassName(timeslot, day);

        if (vm.currentSelectedCell.class != selectedClass) {
            vm.resetOverviewError(true);
            vm.toggleSplitter(false, timeslot, day, selectedClass);
            vm.saveLessonSlot();
            vm.currentLessonList = [];
        }
    }

    vm.onAddLessonType = function() {
        if (vm.selectedLessonType != 0) {
            vm.currentLessonList.push(vm.getEmptyLessonDetailObject(vm.selectedLessonType));
        }

        console.log(vm.currentLessonList);

        vm.selectedLessonType = 0;
    }

    vm.onSplitterClose = function() {
        vm.toggleSplitter(true);

        if (vm.splitterContent == vm.SPLITTER_OVERVIEW) {
            vm.resetOverviewError(true);
        }
    }

    // misc function
    vm.toggleSplitter = function(toggle = false, timeslot = {}, day = {}, selectedClass = '') {
        vm.hideSplitter = toggle.toString();
        vm.currentSelectedCell = {
            day_of_week: day,
            timeslot: timeslot,
            class: selectedClass
        };
    }

    vm.resetOverviewError = function(resetData = false) {
        if (resetData) {
            for (var field in vm.overviewError) {
                vm.resetOverviewData(field);
            }
        }
        vm.overviewError = {};
    }

    vm.getClassName = function(timeslot, day) {
        return 'lesson-' + timeslot.id + '-' + day.day_of_week;
    }

    vm.getLessonTitle = function(lessonTypeId) {
        for (var lesson in vm.lessonType) {
            if (vm.lessonType[lesson].id == lessonTypeId) {
                return vm.lessonType[lesson].title;
            }
        }
        return '';
    }

    vm.resetOverviewData = function(field = null) {
        if (field == null) {
            // for saving usage
            vm.overviewData.id = vm.timetableData.id;
            vm.overviewData.name = vm.timetableData.name;
            vm.overviewData.status = vm.timetableData.status;
            vm.overviewData.academic_period_id = vm.timetableData.academic_period_id;
            vm.overviewData.institution_class_id = vm.timetableData.institution_class_id;
            vm.overviewData.institution_id = vm.timetableData.institution_id,
            vm.overviewData.institution_schedule_interval_id = vm.timetableData.institution_schedule_interval_id;
            vm.overviewData.institution_schedule_term_id = vm.timetableData.institution_schedule_term_id;

            // for display usage
            vm.overviewData.academic_period_name = vm.timetableData.academic_period.name;
            vm.overviewData.term_name = vm.timetableData.schedule_term.name;
            vm.overviewData.class_name = vm.timetableData.institution_class.name;
            vm.overviewData.interval_name = vm.timetableData.schedule_interval.name;

        } else {
            vm.overviewData[field] = vm.timetableData[field];
        }
    }

    vm.updateTimetableData = function(field, value) {
        vm.timetableData[field] = value;
    }

    vm.getEmptyLessonDetailObject = function(lessonType) {
        var lessonDetailObject = {
            lesson_type: lessonType,
            day_of_week: vm.currentSelectedCell.day_of_week.day_of_week,
            institution_schedule_timetable_id: vm.timetableData.id,
            institution_schedule_timeslot_id: vm.currentSelectedCell.timeslot.id,

            schedule_lesson_rooms: {
                _ids: []
            }
        };

        if (lessonType == vm.NON_CURRICULUM_LESSON) {
            lessonDetailObject['schedule_non_curriculum_lesson'] = {
                name: ''
            };
        } else { // vm.CURRICULUM_LESSON
            lessonDetailObject['schedule_curriculum_lesson'] = {
                code_only: false,
                institution_subject_id: null
            };
        }

        console.log('lessonDetailObject', lessonDetailObject);


        // if (lessonType == NON_CURRICULUM_LESSON) {
        //     lessonObject = {
        //         lesson_type: NON_CURRICULUM_LESSON,
        //         name: '',
        //         institution_room_id: []
        //     };
        // } else { // CURRICULUM_LESSON
        //     lessonObject = {
        //         type: CURRICULUM_LESSON,
        //         institution_subject_id: -1,
        //         code_only: false,
        //         institution_room_id: []
        //     };
        // }


        return lessonDetailObject;
    }
}