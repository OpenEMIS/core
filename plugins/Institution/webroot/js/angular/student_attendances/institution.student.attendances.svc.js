angular
    .module('institution.student.attendances.svc', ['kd.data.svc'])
    .service('InstitutionStudentAttendancesSvc', InstitutionStudentAttendancesSvc);

InstitutionStudentAttendancesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionStudentAttendancesSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        translate: translate,

        getAbsenceTypeOptions: getAbsenceTypeOptions,
        getStudentAbsenceReasonOptions: getStudentAbsenceReasonOptions,

        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getDayListOptions: getDayListOptions,
        getClassOptions: getClassOptions,
        getPeriodOptions: getPeriodOptions,
        getClassStudent: getClassStudent,

        getSingleDayColumnDefs: getSingleDayColumnDefs
    };

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes',
        AbsenceTypes: 'Institution.AbsenceTypes',
        StudentAbsenceReasons: 'Institution.StudentAbsenceReasons'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentAttendances');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success: success, defer: true});
    };

    function getAbsenceTypeOptions() {
        var success = function(response, deferred) {
            var absenceType = response.data.data;
            if (angular.isObject(absenceType) && absenceType.length > 0) {
                deferred.resolve(absenceType);
            } else {
                deferred.reject('There was an error when retrieving the absence types');
            }
        };

        return AbsenceTypes
            .find('AbsenceTypeList')
            .ajax({success: success, defer: true});
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
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('There was an error when retrieving the academic periods');
            }
        };

        return AcademicPeriods
            .find('PeriodHasClass', {
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
            .find('WeeksForPeriod', {
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getDayListOptions(academicPeriodId, weekId) {
        var success = function(response, deferred) {
            var dayList = response.data.data;
            if (angular.isObject(dayList) && dayList.length > 0) {
                deferred.resolve(dayList);
            } else {
                deferred.reject('There was an error when retrieving the day list');
            }
        };

        return AcademicPeriods
            .find('DaysForPeriodWeek', {
                academic_period_id: academicPeriodId,
                week_id: weekId
            })
            .ajax({success: success, defer: true});
    }

    function getClassOptions(institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            var classList = response.data.data;
            if (angular.isObject(classList) && classList.length > 0) {
                deferred.resolve(classList);
            } else {
                deferred.reject('There was an error when retrieving the class list');
            }
        };

        return InstitutionClasses
            .find('ClassesByInstitutionAndAcademicPeriod', {
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
        
        return [];
    }

    function getPeriodOptions(institutionClassId, academicPeriodId) {
        var success = function(response, deferred) {
            var attendancePeriodList = response.data.data;
            if (angular.isObject(attendancePeriodList) && attendancePeriodList.length > 0) {
                deferred.resolve(attendancePeriodList);
            } else {
                deferred.reject('There was an error when retrieving the attendance period list');
            }
        };

        return StudentAttendanceMarkTypes
            .find('PeriodByClass', {
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer: true});
    }

    function getClassStudent(institutionId, institutionClassId, academicPeriodId, attendancePeriod, day, weekStartDay, weekEndDay) {
        var success = function(response, deferred) {
            var classStudents = response.data.data;

            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentAttendances
            .find('ClassStudentsWithAbsence', {
                institution_id: institutionId,
                institution_class_id: institutionClassId,
                academic_period_id: academicPeriodId,
                day_id: day,
                attendance_period_id: attendancePeriod,
                week_start_day: weekStartDay,
                week_end_day: weekEndDay
            })
            .ajax({success: success, defer: true});
    }

    function getSingleDayColumnDefs() {
        var columnDefs = [];
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
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
            headerName: "Name",
            field: "user.name_with_id",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        columnDefs.push({
            headerName: "Attendance",
            field: "StudentAbsences.absence_type_id",
            filterParams: filterParams,
            pinned: direction,
            suppressSorting: true,
            menuTabs: [],
            filter: "text",
            cellRenderer: function(params) {
                if (angular.isDefined(params.value)) {
                    var absenceTypeId = params.value;
                    var api = params.api;
                    var absenceTypeList = params.context.absenceType;
                    var mode = params.context.mode;

                    var id = (params.value == null) ? 0 : params.value;
                    var absenceTypeObj = absenceTypeList.find(obj => obj.id == id);

                    // console.log('absenceTypeList', absenceTypeList);
                    // console.log('mode', mode);

                    if (mode == 'view') {
                        var html = '';
                        switch (absenceTypeObj.code) {
                            case 'PRESENT':
                                html = '<div style="color: #77B576;"><i class="fa fa-check"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                break;
                            case 'LATE':
                                html = '<div style="color: #77B576;"><i class="fa fa-check-circle"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                break;
                            case 'UNEXCUSED':
                                html = '<div style="color: #CC5C5C"><i class="fa fa-circle"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                break;
                            case 'EXCUSED':
                                html = '<div style="color: #CC5C5C"><i class="fa fa-circle"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                break;
                            default:
                                break;
                        }

                        // 3 cases - not marked, present, has reasons
                        return html;
                    } 
                    else if (mode == 'edit') {
                        var oldValue = id;
                        var eCell = document.createElement('div');
                        // eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");
                        eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");

                        var eSelect = document.createElement("select");
                            angular.forEach(absenceTypeList, function(obj, key) {
                            var eOption = document.createElement("option");
                            var labelText = obj.name;
                            eOption.setAttribute("value", obj.id);
                            eOption.innerHTML = labelText;
                            eSelect.appendChild(eOption);
                        });
                        eSelect.value = oldValue;

                        eSelect.addEventListener('blur', function () {
                            var newValue = eSelect.value;
                            if (newValue != oldValue) {
                                console.log('value change!', newValue);
                            }
                        });

                        eSelect.addEventListener('change', function () {
                            var newValue = eSelect.value;
                            if (newValue != oldValue) {
                                oldValue = newValue;
                                params.value = newValue;
                                params.data['StudentAbsences']['absence_type_id'] = newValue;
                                
                                var refreshParams = {
                                    rowNode: [params.node],
                                    columns: ['StudentAbsences.student_absence_reason_id'],
                                    force: true
                                }
                                api.refreshCells(refreshParams);
                            }
                        });

                        eCell.appendChild(eSelect);
                        return eCell;
                    }
                }
            }
        });

        columnDefs.push({
            headerName: "Reason / Comment",
            field: "StudentAbsences.student_absence_reason_id",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function(params) {
                if (angular.isDefined(params.value)) {
                    var data = params.data;
                    var studentAbsenceReasonList = params.context.studentAbsenceReason;
                    var absenceTypeList = params.context.absenceType;
                    var mode = params.context.mode;

                    var studentAbsenceTypeId = (params.data.StudentAbsences.absence_type_id == null) ? 0 : params.data.StudentAbsences.absence_type_id;
                    var absenceTypeObj = absenceTypeList.find(obj => obj.id == studentAbsenceTypeId);

                    if (mode == 'view') {
                        // return "<p>Reason View</p>";
                        return '<i class="fa fa-minus"></i>';
                    }
                    else if (mode == 'edit') {
                        switch (absenceTypeObj.code) {
                            case 'PRESENT':
                                return '<i class="fa fa-minus"></i>';
                            case 'LATE':
                                // console.log('fedit');
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eTextarea = getCommentElement(data);
                                eCell.appendChild(eTextarea);
                                return eCell;
                            case 'UNEXCUSED':
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eSelect = getAbsenceReasonElement(data, studentAbsenceReasonList);
                                var eTextarea = getCommentElement(data);
                                eCell.appendChild(eSelect);
                                eCell.appendChild(eTextarea);
                                return eCell;
                            case 'EXCUSED':
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eSelect = getAbsenceReasonElement(data, studentAbsenceReasonList);
                                var eTextarea = getCommentElement(data);
                                eCell.appendChild(eSelect);
                                eCell.appendChild(eTextarea);
                                return eCell;
                            default:
                                break;
                        }
                    }
                }
            }
        });

        return columnDefs;
    }

    function getCommentElement(data) {
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("placeholder", "Comments");
        eTextarea.value = data.StudentAbsences.comment;

        eTextarea.addEventListener('change', function () {
            data.StudentAbsences.comment = eTextarea.value;
        })

        return eTextarea;
    }

    function getAbsenceReasonElement(data, studentAbsenceReasonList) {
        var eSelectWrapper = document.createElement('div');
        eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");

        var eSelect = document.createElement("select");
        var oldValue = (data.StudentAbsences.absence_type_id == null) ? 0 : data.StudentAbsences.absence_type_id;

        angular.forEach(studentAbsenceReasonList, function(obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });
            
        eSelect.value = oldValue;

        eSelect.addEventListener('blur', function () {
            var newValue = eSelect.value;
            if (newValue != oldValue) {
                console.log('value change!', newValue);
            }
        });

        eSelect.addEventListener('change', function () {
            data.StudentAbsences.absence_type_id = eSelect.value;
        })

        eSelectWrapper.appendChild(eSelect);
        return eSelectWrapper;
    }
};