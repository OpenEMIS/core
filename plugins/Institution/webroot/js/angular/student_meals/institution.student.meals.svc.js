angular
    .module('institution.student.meals.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentMealsSvc', InstitutionStudentMealsSvc);

InstitutionStudentMealsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStudentMealsSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
   const attendanceType = {
        'NOTMARKED': {
            code: 'NOTMARKED',
            icon: 'fa fa-minus',
            color: '#999999'
        },
        'PRESENT': {
            code: 'PRESENT',
            icon: 'fa fa-check',
            color: '#77B576'
        },
        'LATE': {
            code: 'LATE',
            icon: 'fa fa-check-circle-o',
            color: '#999'
        },
        'UNEXCUSED': {
            code: 'UNEXCUSED',
            icon: 'fa fa-circle-o',
            color: '#CC5C5C'
        },
        'EXCUSED': {
            code: 'EXCUSED',
            icon: 'fa fa-circle-o',
            color: '#CC5C5C'
        },
    };

    const icons = {
        'REASON': 'kd kd-reason',
        'COMMENT': 'kd kd-comment',
        'PRESENT': 'fa fa-minus',
    };

    const ALL_DAY_VALUE = -1;

    var translateText = {
        'original': {
            'OpenEmisId': 'OpenEMIS ID',
            'Name': 'Name',
            'Attendance': 'Meal Received',
            'BenefitType': 'Benefit Type',
            'Monday': 'Monday',
            'Tuesday': 'Tuesday',
            'Wednesday': 'Wednesday',
            'Thursday': 'Thursday',
            'Friday': 'Friday',
            'Saturday': 'Saturday',
            'Sunday': 'Sunday'
        },
        'translated': {
        }
    };

    var controllerScope;

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        StudentMeals: 'Institution.StudentMeals',
        InstitutionClasses: 'Institution.InstitutionClasses',
        InstitutionClassGrades: 'Institution.InstitutionClassGrades',
        StudentAttendanceTypes: 'Attendance.StudentAttendanceTypes',
        InstitutionClassSubjects: 'Institution.InstitutionClassSubjects',
        AbsenceTypes: 'Institution.AbsenceTypes',
        StudentAbsenceReasons: 'Institution.StudentAbsenceReasons',
        StudentAbsencesPeriodDetails: 'Institution.StudentAbsencesPeriodDetails',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes',
        StudentAttendanceMarkedRecords: 'Meal.StudentAttendanceMarkedRecords',
        MealBenefit: 'Meal.MealBenefit',
        MealProgrammes: 'Meal.MealProgrammes',
        StudentMealMarkedRecords: 'Meal.StudentMealMarkedRecords'
    };

    var service = {
        init: init,
        translate: translate,

        getAttendanceTypeList: getAttendanceTypeList,
        getStudentAbsenceReasonOptions: getStudentAbsenceReasonOptions,

        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getIsMarked: getIsMarked,
        getEducationGradeOptions: getEducationGradeOptions,
        getSubjectOptions: getSubjectOptions,
        getPeriodOptions: getPeriodOptions,
        getClassStudent: getClassStudent,

        getSingleDayColumnDefs: getSingleDayColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs,

        saveAbsences: saveAbsences,
        savePeriodMarked: savePeriodMarked,
        isMarkableSubjectAttendance: isMarkableSubjectAttendance,
        mealBenefitOptions: mealBenefitOptions,
        mealProgrameOptions: mealProgrameOptions
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentMeals');
        KdDataSvc.init(models);
    }

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    }

    function getAttendanceTypeList() {
        return attendanceType;
    }

    // data service
    function getTranslatedText() {
        var success = function(response, deferred) {
            var translatedObj = response.data;
            if (angular.isDefined(translatedObj)) {
                translateText = translatedObj;
            }
            deferred.resolve(angular.isDefined(translatedObj));
        };

        KdDataSvc.init({translation: 'translate'});
        return translation.translate(translateText.original, {
            success: success,
            defer: true
        });
    }


    function getStudentAbsenceReasonOptions() {
        var success = function(response, deferred) {
            var studentAbsenceReasons = response.data.data;
            if (angular.isObject(studentAbsenceReasons) && studentAbsenceReasons.length > 0) {
                deferred.resolve(studentAbsenceReasons);
            } else {
                deferred.reject('There was an error when retrieving the student absence reasons');
            }
        };

        return StudentAbsenceReasons
            .select(['id', 'name'])
            .order(['order'])
            .ajax({success: success, defer: true});
    }


    function getAcademicPeriodOptions(institutionId) {
        console.log("Institution ID "+institutionId);
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };

        return AcademicPeriods
            .find('periodHasClass', {
                institution_id: institutionId
            })
            .ajax({success: success, defer: true});
    }

    function getWeekListOptions(academicPeriodId) {
        var success = function(response, deferred) {
            var academicPeriodObj = response.data.data;
            if (angular.isDefined(academicPeriodObj) && academicPeriodObj.length > 0) {
                var weeks = academicPeriodObj[0].weeks; // find only 1 academic period entity

                if (angular.isDefined(weeks) && weeks.length > 0) {
                    deferred.resolve(weeks);
                } else {
                    deferred.reject('There was an error when retrieving the week list');
                }
            } else {
                deferred.reject('There was an error when retrieving the week list');
            }
        };

        return AcademicPeriods
            .find('weeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId, institutionId) {
        var success = function(response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };

        return AcademicPeriods
            .find('daysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId,
                institution_id: institutionId,
                school_closed_required: true
            })
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList)) {
                if (classList.length > 0) {
                    deferred.resolve(classList);
                } else {
                    AlertSvc.warning(controllerScope, 'You do not have any classes');
                    deferred.reject('You do not have any classess');
                }
            } else {
                deferred.reject('There was an error when retrieving the class list');
            }
        };

        return InstitutionClasses
            .find('classesByInstitutionAndAcademicPeriod', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function mealBenefitOptions() {
        var success = function(response, deferred) {
            var mealBenefitType = response.data.data;
            if (angular.isObject(mealBenefitType) && mealBenefitType.length > 0) {
                deferred.resolve(mealBenefitType);
            } else {
                deferred.reject('There was an error when retrieving the meal types benefit');
            }
        };

        return MealBenefit
            .select(['id', 'name'])
            .order(['order'])
            .ajax({success: success, defer: true});
    }

    function mealProgrameOptions() {
        var success = function(response, deferred) {
            var mealProgrammes = response.data.data;
            if (angular.isObject(mealProgrammes) && mealProgrammes.length > 0) {
                deferred.resolve(mealProgrammes);
            } else {
                deferred.reject('There was an error when retrieving the student absence reasons');
            }
        };

        return MealProgrammes
            .select(['id', 'name'])
            .ajax({success: success, defer: true});
    }
    
    function getEducationGradeOptions(institutionId, academicPeriodId, classId) {
        var success = function(response, deferred) {
            var educationGradeList = response.data.data;
            console.log("educationGradeList", educationGradeList)
            if (angular.isObject(educationGradeList)) {
                if (educationGradeList.length > 0) {
                    deferred.resolve(educationGradeList);
                } else {
                    AlertSvc.warning(controllerScope, 'You do not have any education grade');
                    deferred.reject('You do not have any education grades');
                }
            } else {
                deferred.reject('There was an error when retrieving the education grade list');
            }
        };
        return InstitutionClasses
            .find('gradesByInstitutionAndAcademicPeriodAndInstitutionClass', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId,
                institution_class_id: classId
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getSubjectOptions(institutionId,institutionClassId,academicPeriodId,day_id) {
        var success = function(response, deferred) {
            var subjectList = response.data.data;
            if (angular.isObject(subjectList)) {
                    deferred.resolve(subjectList);
            } else {
                deferred.reject('There was an error when retrieving the subject list');
            }
        };

        return InstitutionClassSubjects
            .find('allSubjectsByClassPerAcademicPeriod', {
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day_id
            })
            .ajax({success: success, defer: true});

        return [];
    }

    function getPeriodOptions(institutionClassId, academicPeriodId,day_id, educationGradeId) {
        var success = function(response, deferred) {
            var attendancePeriodList = response.data.data;
            var attendancePeriodList = [{id: 1, name: "Period 1"}] //static data
            if (angular.isObject(attendancePeriodList) && attendancePeriodList.length > 0) {
                deferred.resolve(attendancePeriodList);
            } else {
                deferred.reject('There was an error when retrieving the attendance period list');
            }
        };

        return StudentAttendanceMarkTypes
            .find('periodByClass', {
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day_id,
                education_grade_id: educationGradeId
            })
            .ajax({success: success, defer: true});
    }

    function getClassStudent(institutionId,institutionClassId, academicPeriodId,day_id,week_id,week_start_day,week_end_day,subject_id ) {
        
        var extra = {
            institution_id: institutionId,
            institution_class_id: institutionClassId,
            academic_period_id: academicPeriodId,
            day_id: day_id,
            week_id: week_id,
            week_start_day: week_start_day,
            week_end_day: week_end_day,
            subject_id : subject_id
        };

        if (extra.institution_class_id == '' || extra.academic_period_id == '') {
            return $q.reject('There was an error when retrieving the class student list');
        }

        var success = function(response, deferred) {
            var classStudents = response.data.data;
            console.log(response);
            console.log(classStudents);
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentMeals
            .find('classStudentsWithMeal', extra)
            .ajax({success: success, defer: true});
    }

    function getIsMarked(params) {
        console.log("parms", params)
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            meal_programmes_id: params.meal_programmes_id,
            academic_period_id: params.academic_period_id,
            day: params.day_id,
        };
        if (extra.day_id == ALL_DAY_VALUE) {
            return $q.resolve(false);
        }

        var success = function(response, deferred) {
            var count = response.data.total;
            var count = {data: [], total: 0} // static data
            if (angular.isDefined(count)) {
                var isMarked = count > 0;
                deferred.resolve(isMarked);
            } else {
                deferred.reject('There was an error when retrieving the is_marked record');
            }
        };

        return StudentAttendanceMarkedRecords
            .find('MealIsMarked', extra)
            .ajax({success: success, defer: true});
    }


    // save error
    function clearError(data, skipKey) {
        if (angular.isUndefined(data.save_error)) {
            data.save_error = {};
        }

        angular.forEach(data.save_error, function(error, key) {
            if (key != skipKey) {
                data.save_error[key] = false;
            }
        })
    }

    function hasError(data, key) {
        return (angular.isDefined(data.save_error) && angular.isDefined(data.save_error[key]) && data.save_error[key]);
    }

    // save
    function saveAbsences(data, context) {
        var studentAbsenceData = {
            student_id: data.student_id,
            institution_id: data.institution_id,
            academic_period_id: data.academic_period_id,
            institution_class_id: data.institution_class_id,            
            absence_type_id: data.institution_student_absences.absence_type_id,
            student_absence_reason_id: data.institution_student_absences.student_absence_reason_id,
            comment: data.institution_student_absences.comment,
            period: context.period,
            date: context.date,
            subject_id: context.subject_id,
            education_grade_id: context.education_grade_id
        };

        return StudentAbsencesPeriodDetails.save(studentAbsenceData);
    }

    function savePeriodMarked(params, scope) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            meal_programmes_id: params.meal_programmes_id,
            academic_period_id: params.academic_period_id,
            date: params.day,
        };

        UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
        StudentMealMarkedRecords.save(extra)
        .then(
            function(response) {
                AlertSvc.info(scope, 'Meal will be automatically saved.');
            },
            function(error) {
                AlertSvc.error(scope, 'There was an error when saving the record');
            }
        )
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
        });        
    }

    // column definitions
    function getAllDayColumnDefs(dayList, attendancePeriodList) {
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }
        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: "user.openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.Name,
            field: "user.name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        angular.forEach(dayList, function(dayObj, dayKey) {
            if (dayObj.id != -1) {
                var childrenColDef = [];
                angular.forEach(attendancePeriodList, function(periodObj, periodKey) {
                    childrenColDef.push({
                        headerName: periodObj.id,
                        field: 'week_attendance.' + dayObj.day + '.' + periodObj.id,
                        suppressSorting: true,
                        suppressResize: true,
                        menuTabs: [],
                        minWidth: 30,
                        headerClass: 'children-period',
                        cellClass: 'children-cell',
                        cellRenderer: function(params) {
                            if (angular.isDefined(params.value)) {
                                var code = params.value;
                                return getViewAllDayAttendanceElement(code);
                            }
                        }
                    });
                });

                var dayText = dayObj.name;

                var colDef = {
                    headerName: dayText,
                    children: childrenColDef
                };

                columnDefs.push(colDef);
            }
        });

        return columnDefs;
    }

    function getSingleDayColumnDefs(period) {
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30,
            newRowsAction: 'keep'
        };
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }

        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: "user.openemis_no",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.Name,
            field: "user.name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        columnDefs.push({
            headerName: translateText.translated.Attendance,
            field: "institution_student_meal.meal_benefit_id",
            suppressSorting: true,
            menuTabs: [],
            cellRenderer: function(params) {
                // if (angular.isDefined(params.value)) {
                //     console.log(params);
                //     if(params.value != null){
                //         return 'paid';
                //     }
                //     else{
                //         return 'free';
                //     }
                // }
                console.log("params1", params)
                if (angular.isDefined(params.value)) {
                    var context = params.context;
                    var mealTypes = context.mealTypes;
                    var isMarked = context.isMarked;
                    var isSchoolClosed = params.context.schoolClosed;
                    var mode = params.context.mode;
                    var data = params.data;

                    if (mode == 'view') {
                        return getViewMealElement(data, mealTypes, isMarked, isSchoolClosed);
                    }
                    else if (mode == 'edit') {
                        var api = params.api;
                        return getEditMealElement(data, mealTypes, api, context);
                    }
                }
            }
        });

        columnDefs.push({
            headerName: translateText.translated.BenefitType,
            field: "institution_student_meal.meal_benefit",
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function(params) {
                if (angular.isDefined(params.value)) {
                    var data = params.data;
                    var context = params.context;
                    var mealBenefitTypeOptions = context.mealBenefitTypeOptions;
                    var mealTypes = context.mealTypes;
                    var mode = context.mode;

                    if (angular.isDefined(params.data.institution_student_meal)) {
                        var studentMealTypeId = (params.data.institution_student_meal.meal_benefit_id == null) ? 0 : params.data.institution_student_meal.meal_benefit_id;
                        var absenceTypeObj = mealTypes.find(obj => obj.id == studentMealTypeId);

                        if (mode == 'view') {
                            // switch (absenceTypeObj.code) {
                            //     case attendanceType.PRESENT.code:
                            //         return '<i class="' + icons.PRESENT + '"></i>';
                            //     case attendanceType.LATE.code:
                            //     case attendanceType.UNEXCUSED.code:
                            //         var html = '';
                            //         html += getViewCommentsElement(data);
                            //         return html;
                            //     case attendanceType.EXCUSED.code:
                            //         var html = '';
                            //         html += getViewAbsenceReasonElement(data, studentAbsenceReasonList);
                            //         html += getViewCommentsElement(data);
                            //         return html;
                            // }
                            if(studentMealTypeId == null || studentMealTypeId == 0) {
                                return '<i style="color: #999999;" class="fa fa-minus"></i>';
                            } else if(studentMealTypeId != null) {
                                var html = '';
                                html += getViewMealReasonElement(data, mealBenefitTypeOptions);
                                html += getViewCommentsElement(data);
                            }
                             
                            return html;
                        } else if (mode == 'edit') {
                            // var api = params.api;
                            // switch (absenceTypeObj.code) {
                            //     case attendanceType.PRESENT.code:
                            //         return '<i class="' + icons.PRESENT + '"></i>';
                            //     case attendanceType.LATE.code:
                            //     case attendanceType.UNEXCUSED.code:
                            //         var eCell = document.createElement('div');
                            //         eCell.setAttribute("class", "reason-wrapper");
                            //         var eTextarea = getEditCommentElement(data, context, api);
                            //         eCell.appendChild(eTextarea);
                            //         return eCell;
                            //     case attendanceType.EXCUSED.code:
                            //         var eCell = document.createElement('div');
                            //         eCell.setAttribute("class", "reason-wrapper");
                            //         var eSelect = getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api);
                            //         var eTextarea = getEditCommentElement(data, context, api);
                            //         eCell.appendChild(eSelect);
                            //         eCell.appendChild(eTextarea);
                            //         return eCell;
                            //     default:
                            //         break;
                            // }
                        }
                    }
                }
            }
        });

        

        return columnDefs;
    }

    // cell renderer elements
    function getEditMealElement(data, mealTypeList, api, context) {
        console.log("data1", mealTypeList)
        var dataKey = 'meal_benefit_id';
        var scope = context.scope;
        var eCell = document.createElement('div');
        eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eCell.setAttribute("id", dataKey);

        if (data.institution_student_meal[dataKey] == null) {
            data.institution_student_meal[dataKey] = 0;
        }

        var eSelect = document.createElement("select");
        angular.forEach(mealTypeList, function(obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });

        if (hasError(data, dataKey)) {
            eSelect.setAttribute("class", "error");
        }

        eSelect.value = data.institution_student_meal[dataKey];
        eSelect.addEventListener('change', function () {
            setTimeout(function(){
                setRowDatas(context, data)
            }, 200)
            var oldValue = data.institution_student_meal[dataKey];
            var newValue = eSelect.value;

            var mealTypeObj = mealTypeList.find(obj => obj.id == newValue);
            console.log("absenceTypeObj", mealTypeObj)
            // data.institution_student_absences.absence_type_id = newValue;

            if (newValue != oldValue) {
                var oldParams = {
                    meal_benefit_id: oldValue
                };

                // reset not related data, store old params for reset purpose
                switch (mealTypeObj.code) {
                    case 'FREE':
                        data.institution_student_meal.comment = null;
                        data.institution_student_meal.meal_benefit_id = null;
                        break;
                    case 'PAID':
                        data.institution_student_meal.meal_benefit_id = newValue;
                        oldParams.comment = data.institution_student_meal.comment;
                        break;
                    
                }

                // oldValue = newValue;
                // data.institution_student_meal.meal_benefit_id = newValue;
                // data.institution_student_meal.meal_type_code = mealTypeObj.code;

                var refreshParams = {
                    columns: ['institution_student_meal.student_absence_reason_id'],
                    force: true
                }
                api.refreshCells(refreshParams);
            }

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            // saveAbsences(data, context)
            // .then(
            //     function(response) {
            //         clearError(data, dataKey);
            //         if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
            //             data.save_error[dataKey] = true;
            //             angular.forEach(oldParams, function(value, key) {
            //                 data.institution_student_meal[key] = value;
            //             });
            //             AlertSvc.error(scope, 'There was an error when saving the record');
            //         } else {
            //             data.save_error[dataKey] = false;
            //             AlertSvc.info(scope, 'Attendances will be automatically saved.');
            //         }
            //     },
            //     function(error) {
            //         clearError(data, dataKey);
            //         data.save_error[dataKey] = true;
            //         angular.forEach(oldParams, function(value, key) {
            //             data.institution_student_meal[key] = value;
            //         });
            //         AlertSvc.error(scope, 'There was an error when saving the record');
            //     }
            // )
            // .finally(function() {
            //     var refreshParams = {
            //         columns: [
            //             'institution_student_meal.student_absence_reason_id',
            //             'institution_student_meal.meal_benefit_id'
            //         ],
            //         force: true
            //     };
            //     api.refreshCells(refreshParams);
            //     UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            // });
        });

        eCell.appendChild(eSelect);
        return eCell;
    }

    function setRowDatas(context, data) {
        var studentList = context.scope.$ctrl.classStudentList;
        studentList.forEach(function (dataItem, index) {
            if(dataItem.institution_student_absences.absence_type_code == null || dataItem.institution_student_absences.absence_type_code == "PRESENT") {
                dataItem.rowHeight = 60;
            } else {
                dataItem.rowHeight = 120;
            }
        });
        context.scope.$ctrl.gridOptions.api.setRowData(studentList);
    }

    function getEditCommentElement(data, context, api) {
        var dataKey = 'comment';
        var scope = context.scope;
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("placeholder", "Comments");
        eTextarea.setAttribute("id", dataKey);

        if (hasError(data, dataKey)) {
            eTextarea.setAttribute("class", "error");
        }

        eTextarea.value = data.institution_student_absences[dataKey];
        eTextarea.addEventListener('blur', function () {
            var oldValue = data.institution_student_absences.comment;
            data.institution_student_absences[dataKey] = eTextarea.value;

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            saveAbsences(data, context)
            .then(
                function(response) {
                    clearError(data, dataKey);
                    if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
                        data.save_error[dataKey] = true;
                        data.institution_student_absences[dataKey] = oldValue;
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    } else {
                        data.save_error[dataKey] = false;
                        AlertSvc.info(scope, 'Attendances will be automatically saved.');
                    }
                },
                function(error) {
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    AlertSvc.error(scope, 'There was an error when saving the record');
                    data.institution_student_absences[dataKey] = oldValue;
                }
            )
            .finally(function() {
                var refreshParams = {
                    columns: [
                        'institution_student_absences.student_absence_reason_id',
                        'institution_student_absences.absence_type_id'
                    ],
                    force: true
                };
                api.refreshCells(refreshParams);
                UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            });
        });

        return eTextarea;
    }

    function getEditAbsenceReasonElement(data, studentAbsenceReasonList, context, api) {
        var dataKey = 'student_absence_reason_id';
        var scope = context.scope;
        var eSelectWrapper = document.createElement('div');
        eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        eSelectWrapper.setAttribute("id", dataKey);

        var eSelect = document.createElement("select");
        if (hasError(data, dataKey)) {
            eSelect.setAttribute("class", "error");
        }

        if (data.institution_student_absences[dataKey] == null) {
            data.institution_student_absences[dataKey] = studentAbsenceReasonList[0].id;
        }

        angular.forEach(studentAbsenceReasonList, function(obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });

        eSelect.value = data.institution_student_absences[dataKey];
        eSelect.addEventListener('change', function () {
            var oldValue = data.institution_student_absences[dataKey];
            data.institution_student_absences[dataKey] = eSelect.value;

            UtilsSvc.isAppendSpinner(true, 'institution-student-attendances-table');
            saveAbsences(data, context).then(
                function(response) {
                    clearError(data, dataKey);
                    if (angular.isDefined(response.data.error) && response.data.error.length > 0) {
                        data.save_error[dataKey] = true;
                        data.institution_student_absences[dataKey] = oldValue;
                        AlertSvc.error(scope, 'There was an error when saving the record');
                    } else {
                        data.save_error[dataKey] = false;
                        AlertSvc.info(scope, 'Attendances will be automatically saved.');
                    }
                },
                function(error) {
                    clearError(data, dataKey);
                    data.save_error[dataKey] = true;
                    AlertSvc.error(scope, 'There was an error when saving the record');
                    data.institution_student_absences[dataKey] = oldValue;
                }
            ).finally(function() {
                var refreshParams = {
                    columns: [
                        'institution_student_absences.student_absence_reason_id',
                        'institution_student_absences.absence_type_id'
                    ],
                    force: true
                };
                api.refreshCells(refreshParams);
                UtilsSvc.isAppendSpinner(false, 'institution-student-attendances-table');
            });
        })

        eSelectWrapper.appendChild(eSelect);
        return eSelectWrapper;
    }

    function getViewMealElement(data, absenceTypeList, isMarked, isSchoolClosed) {
        var html = '';
        if(data.institution_student_meal.meal_benefit_id == null) {
            html = '<i style="color: #999999;" class="fa fa-minus"></i>';
        } else {
            if(data.institution_student_meal.meal_benefit_id == 0) {
                html = "Free"
            }
            if(data.institution_student_meal.meal_benefit_id == 1) {
                html = "Paid"
            }
        }

        return html;

    }

    function getViewAbsenceReasonElement(data, studentAbsenceReasonList) {
        var absenceReasonId = data.institution_student_absences.student_absence_reason_id;
        var absenceReasonObj = studentAbsenceReasonList.find(obj => obj.id == absenceReasonId);
        var html = '';

        if (absenceReasonId === null) {
            html = '<i class="' + icons.PRESENT + '"></i>';
        } else {
            var reasonName = absenceReasonObj.name;
            html = '<div class="absence-reason"><i class="' + icons.REASON + '"></i><span>' + reasonName + '</span></div>';
        }

        return html;
    }


    function getViewMealReasonElement(data, studentMealReasonList) {
        var mealReasonId = data.institution_student_meal.meal_benefit_id;
        var mealReasonObj = studentMealReasonList.find(obj => obj.id == mealReasonId);
        var html = '';

        if (mealReasonObj === null) {
            html = '<i style="color: #999999;" class="fa fa-minus"></i>';
        } else {
            var reasonName = mealReasonObj.name;
            html = '<div>' + reasonName + '</div>';
        }

        return html;
    }

    function getViewCommentsElement(data) {
        var comment = data.institution_student_meal.paid;
        var html = '';
        if (comment != null) {
            html = '<div class="absences-comment"><i class="' + icons.COMMENT + '"></i><span>' + comment + '</span></div>';
        }
        return html;
    }

    function getViewAllDayAttendanceElement(code) {
        var html = '';
        switch (code) {
            case attendanceType.NOTMARKED.code:
                html = '<i class="' + attendanceType.NOTMARKED.icon + '"></i>';
                break;
            case attendanceType.PRESENT.code:
                html = '<i style="color: ' + attendanceType.PRESENT.color + ';" class="' + attendanceType.PRESENT.icon + '"></i>';
                break;
            case attendanceType.LATE.code:
                html = '<i style="color: ' + attendanceType.LATE.color + ';" class="' + attendanceType.LATE.icon + '"></i>';
                break;
            case attendanceType.UNEXCUSED.code:
                html = '<i style="color: ' + attendanceType.UNEXCUSED.color + ';" class="' + attendanceType.UNEXCUSED.icon + '"></i>';
                break;
            case attendanceType.EXCUSED.code:
                html = '<i style="color: ' + attendanceType.EXCUSED.color + ';" class="' + attendanceType.EXCUSED.icon + '"></i>';
                break;
            default:
                break;
        }
        return html;
    }

    function isMarkableSubjectAttendance(institutionId,academicPeriodId,selectedClass,selectedDay) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.data[0].code)) {
                var isMarkableSubjectAttendance = false;
                if (response.data.data[0].code == 'SUBJECT') {
                    isMarkableSubjectAttendance = true;
                } else {
                    isMarkableSubjectAttendance = false;
                }
                deferred.resolve(isMarkableSubjectAttendance);
            } else {
                deferred.reject('There was an error when retrieving the isMarkableSubjectAttendance record');
            }
        };

        return StudentAttendanceTypes
            .find('attendanceTypeCode', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId,
                institution_class_id: selectedClass,
                day_id: selectedDay                
            })
            .ajax({success: success, defer: true});

            return [];
    }
    
};