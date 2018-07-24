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

        getSingleDayColumnDefs: getSingleDayColumnDefs,
        getAllDayColumnDefs: getAllDayColumnDefs
    };

    var models = {
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        StudentAttendances: 'Institution.StudentAttendances',
        InstitutionClasses: 'Institution.InstitutionClasses',
        StudentAttendanceMarkTypes: 'Attendance.StudentAttendanceMarkTypes',
        AbsenceTypes: 'Institution.AbsenceTypes',
        StudentAbsenceReasons: 'Institution.StudentAbsenceReasons',
        StudentAbsences: 'Institution.StudentAbsences'
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

    function getClassStudent(params) {
        var extra = {
            institution_id: params.institution_id,
            institution_class_id: params.institution_class_id,
            academic_period_id: params.academic_period_id,
            attendance_period_id: params.attendance_period_id,
            day_id: params.day_id,
            week_id: params.week_id,
            week_start_day: params.week_start_day,
            week_end_day: params.week_end_day,
        };

        var success = function(response, deferred) {
            var classStudents = response.data.data;

            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };

        return StudentAttendances
            .find('ClassStudentsWithAbsence', extra)
            .ajax({success: success, defer: true});
    }

    function getAllDayColumnDefs(dayList, attendancePeriodList) {
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

        angular.forEach(dayList, function(dayObj, dayKey) {
            if (dayObj.id != -1) {

                console.log('dayObj', dayObj);
                console.log('dayKey', dayKey);

                var childrenColDef = [];
                angular.forEach(attendancePeriodList, function(periodObj, periodKey) {
                    // console.log('periodObj', periodObj);
                    // console.log('periodKey', periodKey);

                    childrenColDef.push({
                        headerName: periodObj.id,
                        field: 'week_attendance.' + dayObj.day + '.' + periodObj.id,
                        cellRenderer: function(params) {
                            if (angular.isDefined(params.value)) {
                                var code = params.value;
                                switch (code) {
                                    case 'PRESENT':
                                        html = '<div style="color: #77B576;"><i style="color: #77B576;" class="fa fa-check"></i>';
                                        break;
                                    case 'LATE':
                                        html = '<div style="color: #77B576;"><i class="fa fa-check-circle-o"></i>';
                                        break;
                                    case 'UNEXCUSED':
                                        // html = '<div style="color: #CC5C5C"><i class="fa fa-circle-o"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                        break;
                                    case 'EXCUSED':
                                        // html = '<div style="color: #CC5C5C"><i class="fa fa-circle-o"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                                        break;
                                    default:
                                        break;
                                }
                                return html;
                            }
                        }
                    }
                });

                var colDef = {
                    headerName: obj.day,
                    children: childrenColDef
                    // field: "institution_student_absences.absence_type_id",
                    // suppressSorting: true,
                    // menuTabs: [],
                    // cellRenderer: function(params) {
                        // return '<p>' + obj.day +'</p>';
                    // }
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
            field: "institution_student_absences.absence_type_id",
            suppressSorting: true,
            menuTabs: [],
            cellRenderer: function(params) {
                if (angular.isDefined(params.value)) {
                    var context = params.context;
                    var absenceTypeList = context.absenceType;
                    var mode = params.context.mode;
                    var data = params.data;

                    if (mode == 'view') {
                        return getViewAttendanceElement(data, absenceTypeList);
                    } 
                    else if (mode == 'edit') {
                        var api = params.api;
                        return getEditAttendanceElement(data, absenceTypeList, api, context);
                    }
                }
            }
        });

        columnDefs.push({
            headerName: "Reason / Comment",
            field: "institution_student_absences.student_absence_reason_id",
            menuTabs: [],
            suppressSorting: true,
            cellRenderer: function(params) {
                if (angular.isDefined(params.value)) {
                    var data = params.data;
                    var context = params.context;
                    var studentAbsenceReasonList = context.studentAbsenceReason;
                    var absenceTypeList = context.absenceType;
                    var mode = context.mode;

                    var studentAbsenceTypeId = (params.data.institution_student_absences.absence_type_id == null) ? 0 : params.data.institution_student_absences.absence_type_id;
                    var absenceTypeObj = absenceTypeList.find(obj => obj.id == studentAbsenceTypeId);

                    if (mode == 'view') {
                        switch (absenceTypeObj.code) {
                            case 'PRESENT':
                                return '<i class="fa fa-minus"></i>';
                            case 'LATE':
                            case 'UNEXCUSED':
                                var html = '';
                                html += getViewCommentsElement(data);
                                return html;
                            case 'EXCUSED':
                                var html = '';
                                html += getViewAbsenceReasonElement(data, studentAbsenceReasonList);
                                html += getViewCommentsElement(data);
                                return html;
                        }   
                    } else if (mode == 'edit') {
                        switch (absenceTypeObj.code) {
                            case 'PRESENT':
                                return '<i class="fa fa-minus"></i>';
                            case 'LATE':
                                // console.log('fedit');
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eTextarea = getEditCommentElement(data, context);
                                eCell.appendChild(eTextarea);
                                return eCell;
                            case 'UNEXCUSED':
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eTextarea = getEditCommentElement(data, context);
                                eCell.appendChild(eTextarea);
                                return eCell;
                            case 'EXCUSED':
                                var eCell = document.createElement('div');
                                eCell.setAttribute("class", "reason-wrapper");
                                var eSelect = getEditAbsenceReasonElement(data, studentAbsenceReasonList, context);
                                var eTextarea = getEditCommentElement(data, context);
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
            date: context.date
        };

        console.log('save - studentAbsenceData', studentAbsenceData);
        StudentAbsences.save(studentAbsenceData);
    } 

    // Cell renderer elements
    function getEditAttendanceElement(data, absenceTypeList, api, context) {
        if (data.institution_student_absences.absence_type_id == null) {
            data.institution_student_absences.absence_type_id = 0;
        }

        var oldValue = data.institution_student_absences.absence_type_id;
        var eCell = document.createElement('div');
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
        eSelect.addEventListener('change', function () {
            var newValue = eSelect.value;
            data.institution_student_absences.absence_type_id = newValue;
            if (newValue != oldValue) {
                oldValue = newValue;
                var absenceTypeObj = absenceTypeList.find(obj => obj.id == newValue);

                // reset not related data
                switch (absenceTypeObj.code) {
                    case 'PRESENT':
                        data.institution_student_absences.student_absence_reason_id = null;
                        data.institution_student_absences.comment = null;
                        data.institution_student_absences.absence_type_id = null;
                        break;
                    case 'LATE':
                    case 'UNEXCUSED':
                        data.institution_student_absences.student_absence_reason_id = null;
                        data.institution_student_absences.comment = null;
                        break;
                    case 'EXCUSED':
                        data.institution_student_absences.comment = null;
                        break;
                }

                data.institution_student_absences.absence_type_id = newValue;
                
                // refresh student_absence_reason_id to change the input based on absence type
                var refreshParams = {
                    columns: ['institution_student_absences.student_absence_reason_id'],
                    force: true
                }
                api.refreshCells(refreshParams);
            }

            saveAbsences(data, context);
        });

        eCell.appendChild(eSelect);
        return eCell;
    }

    function getEditCommentElement(data, context) {
        var eTextarea = document.createElement("textarea");
        eTextarea.setAttribute("placeholder", "Comments");
        eTextarea.value = data.institution_student_absences.comment;

        eTextarea.addEventListener('change', function () {
            data.institution_student_absences.comment = eTextarea.value;
        });

        eTextarea.addEventListener('blur', function () {
            data.institution_student_absences.comment = eTextarea.value;
            saveAbsences(data, context);
        });

        return eTextarea;
    }

    

    function getEditAbsenceReasonElement(data, studentAbsenceReasonList, context) {
        var eSelectWrapper = document.createElement('div');
        eSelectWrapper.setAttribute("class", "oe-select-wrapper input-select-wrapper");
        var eSelect = document.createElement("select");

        if (data.institution_student_absences.student_absence_reason_id == null) {
            data.institution_student_absences.student_absence_reason_id = studentAbsenceReasonList[0].id;
        }

        angular.forEach(studentAbsenceReasonList, function(obj, key) {
            var eOption = document.createElement("option");
            var labelText = obj.name;
            eOption.setAttribute("value", obj.id);
            eOption.innerHTML = labelText;
            eSelect.appendChild(eOption);
        });
            
        eSelect.addEventListener('change', function () {
            data.institution_student_absences.student_absence_reason_id = eSelect.value;
            saveAbsences(data, context);
        })

        eSelectWrapper.appendChild(eSelect);
        return eSelectWrapper;
    }

    function getViewAttendanceElement(data, absenceTypeList) {
        var id = (data.institution_student_absences.absence_type_id === null) ? 0 : data.institution_student_absences.absence_type_id;
        var absenceTypeObj = absenceTypeList.find(obj => obj.id == id);
        var html = '';
        switch (absenceTypeObj.code) {
            case 'PRESENT':
                html = '<div style="color: #77B576;"><i class="fa fa-check"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                break;
            case 'LATE':
                html = '<div style="color: #77B576;"><i class="fa fa-check-circle-o"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                break;
            case 'UNEXCUSED':
                html = '<div style="color: #CC5C5C"><i class="fa fa-circle-o"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                break;
            case 'EXCUSED':
                html = '<div style="color: #CC5C5C"><i class="fa fa-circle-o"></i> <span> ' + absenceTypeObj.name + ' </span></div>';
                break;
            default:
                break;
        }

        // 3 cases - not marked, present, has reasons
        return html;
    }

    function getViewCommentsElement(data) {
        var comment = data.institution_student_absences.comment;
        var html = '';
        if (comment != null) {
            html = '<div class="absences-comment"><i class="fa fa-commenting"></i><span>' + comment + '</span></div>';
        }
        return html;
    }

    function getViewAbsenceReasonElement(data, studentAbsenceReasonList) {
        // console.log('data', data);
        // console.log('studentAbsenceReasonList', studentAbsenceReasonList);

        var absenceReasonId = data.institution_student_absences.student_absence_reason_id;
        var absenceReasonObj = studentAbsenceReasonList.find(obj => obj.id == absenceReasonId);
        var html = '';

        if (absenceReasonId === null) {
            html = '<i class="fa fa-minus"></i>';
        } else {
            var reasonName = absenceReasonObj.name;
            html = '<div class="absence-reason"><i class="fa fa-commenting"></i><span>' + reasonName + '</span></div>';
        }

        return html;
    }
};