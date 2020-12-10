angular
    .module('institution.student.archive.svc', ['kd.data.svc', 'alert.svc'])
    .service('InstitutionStudentArchiveSvc', InstitutionStudentArchiveSvc);

InstitutionStudentArchiveSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function InstitutionStudentArchiveSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {

    const ALL_DAY_VALUE = -1;

    var translateText = {
        'original': {
            'AcedemicPeriod': 'Academic Period',
            'day': 'Day',
            'class': 'Class',
            'AttendencePerDay': 'Attendance Per Day',
            'OpenEmisId': 'OpenEMIS ID',
            'name': 'Name',
            'attendence': 'Attendance',
            'ReasonComment': 'Reason/Comment'
        },
        'translated': {
        }
    };

    var controllerScope;

    var models = {
        StudentArchive: 'Institution.StudentArchive'
    };

    var service = {
        init: init,
        translate: translate,
        getTranslatedText: getTranslatedText,
        getClassStudent: getClassStudent,
        getDummyData: getDummyData
    };

    return service;

    function init(baseUrl, scope) {
        controllerScope = scope;
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentArchive');
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
            subject_id : params.subject_id
        };

        var success = function(response, deferred) {
            var classStudents = response;
            console.log("Success");
            console.log(classStudents);
            if (angular.isObject(classStudents)) {
                deferred.resolve(classStudents);
            } else {
                deferred.reject('There was an error when retrieving the class student list');
            }
        };
        return StudentArchive
            .find('classStudentsWithAbsence', extra)
            .ajax({success: success, defer: true});
    }

    function getDummyData() {
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
            headerName: translateText.translated.AcedemicPeriod,
            field: "academic_period",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.day,
            field: "day",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.class,
            field: "class",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.AttendencePerDay,
            field: "attendance_per_day",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.OpenEmisId,
            field: "open_emis_id",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.name,
            field: "name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.attendence,
            field: "attendance",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });
        columnDefs.push({
            headerName: translateText.translated.ReasonComment,
            field: "reason_comment",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: "text"
        });

        return columnDefs;
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
};