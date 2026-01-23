angular.module('institution.student.attendances.archive.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.attendances.archive.svc'])
    .controller('InstitutionStudentAttendancesArchiveCtrl', InstitutionStudentAttendancesArchiveController);

InstitutionStudentAttendancesArchiveController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentAttendancesArchiveSvc'];

function InstitutionStudentAttendancesArchiveController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentAttendancesArchiveSvc) {
    var vm = this;

    vm.action = 'view';
    vm.excelUrl = '';

    vm.institutionId;
    vm.schoolClosed = true;

    vm.absenceTypeOptions = [];
    vm.studentAbsenceReasonOptions = [];
    vm.isMarked = false;

    // Dashboards
    vm.totalStudents = '-';
    vm.presentCount = '-';
    vm.absenceCount = '-';
    vm.lateCount = '-';

    vm.allAttendances = '-';
    vm.allPresentCount = '-';
    vm.allAbsenceCount = '-';
    vm.allLateCount = '-';
    vm.exportexcel = '';
    vm.excelExportAUrl = '';

    // Options
    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';
    vm.selectedWeekStartDate = '';
    vm.selectedWeekEndDate = '';

    vm.dayListOptions = [];
    vm.selectedDay = '';

    vm.classListOptions = [];
    vm.subjectListOptions = [];
    vm.selectedClass = '';

    vm.educationGradeListOptions = [];
    vm.selectedEducationGrade = '';

    vm.attendancePeriodOptions = [];
    vm.selectedAttendancePeriod = '';

    vm.classStudentList = [];
    vm.isMarkableSubjectAttendance = false;

    vm.superAdmin = 1;
    vm.permissionView = 1;
    vm.permissionEdit = 1;

    // gridOptions
    vm.gridReady = false;
    vm.gridOptions = {
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        // rowHeight: 125,
        minColWidth: 200,
        enableColResize: true,
        enableSorting: true,
        unSortIcon: true,
        enableFilter: true,
        suppressMenuHide: true,
        suppressMovableColumns: true,
        suppressContextMenu: true,
        stopEditingWhenGridLosesFocus: true,
        ensureDomOrder: true,
        suppressCellSelection: true,
        onGridSizeChanged: function () {
            this.api.sizeColumnsToFit();
        },
        onGridReady: function () {
            if (angular.isDefined(vm.gridOptions.api)) {
                vm.setGridData();
                vm.setColumnDef();
            }
        },
        context: {
            scope: $scope,
            mode: vm.action,
            absenceTypes: vm.absenceTypeOptions,
            studentAbsenceReasons: vm.studentAbsenceReasonOptions,
            date: vm.selectedDay,
            schoolClosed: vm.schoolClosed,
            week: vm.selectedWeek,
            period: vm.selectedAttendancePeriod,
            isMarked: vm.isMarked,
            subject_id: vm.selectedSubject,
            education_grade_id: vm.selectedEducationGrade
        },
        getRowHeight: getRowHeight,
    };

    function handleError(error) {
        removeLoader();
        console.error(error);
        AlertSvc.warning($scope, error);
        return false;
    }

    function removeLoader() {
        UtilsSvc.isAppendLoader(false);
    }

    function appendLoader() {
        AlertSvc.reset($scope);
        UtilsSvc.isAppendLoader(true);
    }

    function setDayMonthYear() {
        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth() + 1;
        var currentdate = currentDate.getDate();
        // console.log(currentYear + '-' +currentMonth + '-' + currentdate)
        vm.currentDayMonthYear = currentYear + '-' + currentMonth + '-' + currentdate;
    }

    function getAbsenceTypeOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getAbsenceTypeOptions();
        return promise.then(function (result) {
            return result;
        });
    }

    function getTranslatedText() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getTranslatedText();
        return promise.then(function (result) {
            return result;
        });
    }

    function setAbsenceTypes(result) {
        vm.absenceType = result;
        vm.gridOptions.context.absenceTypes = vm.absenceType;
    }

    function getStudentAbsenceReasonOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getStudentAbsenceReasonOptions();
        return promise.then(function (result) {
            return result;
        });
    }


    function setAbsenceReasons(result) {
        vm.studentAbsenceReasonOptions = result;
        vm.gridOptions.context.studentAbsenceReasons = vm.studentAbsenceReasonOptions;
    }

    function getAcademicPeriodOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getAcademicPeriodOptions(vm.institutionId);
        return promise.then(function (result) {
            return result;
        });
    }

    function setAcademicPeriodOptions(result) {
        vm.academicPeriodOptions = result;
        // console.log(vm.academicPeriodOptions);
        if (result.length > 0) {

            for (var i = 0; i < result.length; ++i) {
                vm.selectedAcademicPeriod = result[0].id;
                if (angular.isDefined(result[i]['selected'] && result[i]['selected'])) {
                    vm.selectedAcademicPeriod = result[i].id;
                    break;
                }
            }

        }

    }

    function getWeekListOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getWeekListOptions(vm.selectedAcademicPeriod);
        return promise.then(function (result) {
            return result;
        });
    }

    function setWeekListOptions(result) {
        vm.weekListOptions = result;
        if (result.length > 0) {
            for (var i = 0; i < result.length; ++i) {
                if (angular.isDefined(result[i]['selected']) && result[i]['selected']) {
                    vm.selectedWeek = result[i].id;
                    vm.selectedWeekStartDate = result[i].start_day;
                    vm.selectedWeekEndDate = result[i].end_day;
                    vm.week = vm.selectedWeek;
                    vm.gridOptions.context.week = vm.selectedWeek;
                    break;
                }
            }
        }

    }

    function getDayListOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
        return promise.then(function (result) {
            return result;
        });
    }

    function setDayListOptions(dayListOptions) {
        vm.dayListOptions = dayListOptions;
        var hasSelected = false;
        if (dayListOptions.length > 0) {
            for (var i = 0; i < dayListOptions.length; ++i) {
                if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                    hasSelected = true;
                    vm.selectedDay = dayListOptions[i].date;
                    vm.schoolClosed = (angular.isDefined(dayListOptions[i]['closed']) && dayListOptions[i]['closed']) ? true : false;
                    vm.gridOptions.context.date = vm.selectedDay;
                    vm.gridOptions.context.schoolClosed = vm.schoolClosed;
                    break;
                }
            }

            if (!hasSelected) {
                vm.selectedDay = dayListOptions[0].date;
                vm.schoolClosed = (angular.isDefined(dayListOptions[0]['closed']) && dayListOptions[0]['closed']) ? true : false;
                vm.gridOptions.context.date = vm.selectedDay;
                vm.gridOptions.context.schoolClosed = vm.schoolClosed;
            }
        }

        if (dayListOptions.length < 1) {
            vm.selectedDay = -1;
            vm.gridOptions.context.date = vm.selectedDay;
        }
    }


    function getClassOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
        return promise.then(function (result) {
            return result;
        });
    }

    function setClassOptions(classListOptions) {
        vm.classListOptions = classListOptions;
        if (classListOptions.length > 0) {
            vm.selectedClass = classListOptions[0].id;
            if (classListOptions[0].SecurityRoleFunctions) {
                vm.superAdmin = 0;
                vm.permissionView = classListOptions[0].SecurityRoleFunctions._view;
                vm.permissionEdit = classListOptions[0].SecurityRoleFunctions._edit;
            }
        }
    }

    function getEducationGradeOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getEducationGradeOptions(vm.institutionId, vm.selectedAcademicPeriod, vm.selectedClass);
        return promise.then(function (result) {
            return result;
        });
    }

    function setEducationGradeOptions(educationGradeListOptions) {
        vm.educationGradeListOptions = educationGradeListOptions;
        if (educationGradeListOptions.length > 0) {
            vm.selectedEducationGrade = educationGradeListOptions[0].id;
            vm.gridOptions.context.education_grade_id = vm.selectedEducationGrade;
        }
    }

    function isMarkableSubjectAttendance() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.isMarkableSubjectAttendance(vm.institutionId, vm.selectedAcademicPeriod, vm.selectedClass, vm.selectedDay);
        return promise.then(function (result) {
            return result;
        });
    }

    function setMarkableSubjectAttendance(result) {
        vm.isMarkableSubjectAttendance = result;
    }

    function getSubjectOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getSubjectOptions(vm.institutionId, vm.selectedClass, vm.selectedAcademicPeriod, vm.selectedDay, vm.selectedEducationGrade);
        return promise.then(function (result) {
            return result;
        });
    }

    function setSubjectOptions(subjectListOptions) {
        vm.subjectListOptions = subjectListOptions;
        vm.selectedSubject = 0;
        if (vm.isMarkableSubjectAttendance == true) {
            if (subjectListOptions.length > 0) {
                vm.selectedSubject = subjectListOptions[0].id;
            }
        }
        vm.gridOptions.context.subject_id = vm.selectedSubject;
    }

    function getPeriodOptions() {
        var promise;
        promise = InstitutionStudentAttendancesArchiveSvc.getPeriodOptions(
            vm.selectedClass,
            vm.selectedAcademicPeriod,
            vm.selectedDay,
            vm.selectedEducationGrade,
            vm.selectedWeekStartDate,
            vm.selectedWeekEndDate);
        //POCOR-7183 add params vm.selectedWeekStartDate, vm.selectedWeekEndDate
        return promise.then(function (result) {
            return result;
        });
    }

    function setPeriodOptions(attendancePeriodOptions) {
        vm.attendancePeriodOptions = attendancePeriodOptions;
        if (attendancePeriodOptions.length > 0) {
            vm.selectedAttendancePeriod = attendancePeriodOptions[0].id;
            vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        }

        vm.excelExportAUrl = vm.exportexcel +
            '?institution_id=' + vm.institutionId +
            '&institution_class_id=' + vm.selectedClass +
            '&education_grade_id=' + vm.selectedEducationGrade +
            '&academic_period_id=' + vm.selectedAcademicPeriod +
            '&day_id=' + vm.selectedDay +
            '&attendance_period_id=' + vm.selectedAttendancePeriod +
            '&week_start_day=' + vm.selectedWeekStartDate +
            '&week_end_day=' + vm.selectedWeekEndDate +
            '&subject_id=' + vm.selectedSubject +
            '&week_id=' + vm.selectedWeek;
    }

    function getIsMarked() {
        var promise;
        var options = {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            subject_id: vm.selectedSubject
        };
        promise = InstitutionStudentAttendancesArchiveSvc.getIsMarked(options);
        //POCOR-7183 add params vm.selectedWeekStartDate, vm.selectedWeekEndDate
        return promise.then(function (result) {
            return result;
        });
    }

    function setIsMarked(isMarked) {
        vm.isMarked = isMarked;
        vm.gridOptions.context.isMarked = isMarked;

    }

    function getClassStudent() {
        if (vm.isMarkableSubjectAttendance == true && vm.subjectListOptions.length == 0) {
            return [];
        }
        var promise;
        var options = {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek,
            subject_id: vm.selectedSubject
        };
        promise = InstitutionStudentAttendancesArchiveSvc.getClassStudent(options);

        return promise.then(function (result) {
            return result;
        });
    }

    function setClassStudent(classStudents) {
        vm.classStudents = [];
        vm.classStudentList = classStudents;
        // console.log(classStudents);
    }

// ready
    angular.element(document).ready(function () {
        // POCOR-7895: refactured
        setDayMonthYear();
        InstitutionStudentAttendancesArchiveSvc.init(angular.baseUrl, $scope);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        appendLoader();
        if (vm.institutionId === null) {
            vm.initGrid();
            removeLoader();
            return;
        }

        getTranslatedText()
            .then(getAbsenceTypeOptions)
            .then(setAbsenceTypes)
            .then(getStudentAbsenceReasonOptions)
            .then(setAbsenceReasons)
            .then(getAcademicPeriodOptions)
            .then(setAcademicPeriodOptions)
            .then(getClassOptions)
            .then(setClassOptions)
            .then(getEducationGradeOptions)
            .then(setEducationGradeOptions)
            .then(getWeekListOptions)
            .then(setWeekListOptions)
            .then(getDayListOptions)
            .then(setDayListOptions)
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                removeLoader();
            }).catch(handleError);
    });

    // grid
    vm.initGrid = function () {
        AggridLocaleSvc.getTranslatedGridLocale().then(
            function (localeText) {
                vm.gridOptions.localeText = localeText;
                vm.gridReady = true;
            },
            function (error) {
                console.error(error);
                vm.gridReady = true;
            }
        );
    }

    function getRowHeight(params) {
        return params.data.rowHeight;
    }


    vm.setGridData = function () {
        if (angular.isDefined(vm.gridOptions.api)) {
            // vm.gridOptions.api.setRowData(vm.classStudentList);
            vm.setRowDatas(vm.classStudentList);
            vm.countStudentData();
        }
    }

    vm.setRowDatas = function (studentList) {
        studentList.forEach(function (dataItem, index) {
            if (dataItem.absence_type_code == 0 ||
                dataItem.absence_type_code == "PRESENT" ||
                dataItem.absence_type_code == null) {
                dataItem.rowHeight = 60;
            } else {
                dataItem.rowHeight = 120;
            }
            if (dataItem.comment != "") {
                dataItem.rowHeight = 120;

            }

        });
        vm.gridOptions.api.setRowData(studentList);

    }

    vm.setColumnDef = function (noScheduledClicked) {
        if (!noScheduledClicked) {
            noScheduledClicked = false;
        }
        var columnDefs = [];
        if (vm.selectedDay != -1) {
            columnDefs = InstitutionStudentAttendancesArchiveSvc.getSingleDayColumnDefs(noScheduledClicked);
        } else {
            columnDefs = InstitutionStudentAttendancesArchiveSvc.getAllDayColumnDefs(vm.dayListOptions, vm.attendancePeriodOptions);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    // dashboard count
    vm.countStudentData = function () {
        var attendanceType = InstitutionStudentAttendancesArchiveSvc.getAttendanceTypeList();
        if (vm.selectedDay != -1) {
            // single day
            vm.totalStudents = vm.classStudentList.length;
            if (vm.isMarked) {
                var presentCount = 0;
                var absenceCount = 0;
                var lateCount = 0;

                if (vm.totalStudents > 0) {
                    angular.forEach(vm.classStudentList, function (obj, key) {
                        if (angular.isDefined(obj['absence_type_code'])) {
                            var code = obj['absence_type_code'];

                            switch (code) {
                                case null:
                                case attendanceType.PRESENT.code:
                                    ++presentCount;
                                    break;
                                case attendanceType.LATE.code:
                                    ++presentCount;
                                    ++lateCount;
                                    break;
                                case attendanceType.UNEXCUSED.code:
                                case attendanceType.EXCUSED.code:
                                    ++absenceCount;
                                    break;
                            }
                        }
                    });
                }

                vm.presentCount = presentCount;
                vm.absenceCount = absenceCount;
                vm.lateCount = lateCount;
            } else {
                vm.presentCount = '-';
                vm.absenceCount = '-';
                vm.lateCount = '-';
            }
        }
        if (vm.selectedDay == -1) {
            // all day
            var allAttendances = '-';
            var allPresentCount = '-';
            var allAbsenceCount = '-';
            var allLateCount = '-';

            // if (vm.totalStudents > 0) {
            allAttendances = 0;
            allPresentCount = 0;
            allAbsenceCount = 0;
            allLateCount = 0;

            angular.forEach(vm.classStudentList, function (obj, studentKey) {
                // console.log(obj);
                if (angular.isDefined(obj.week_attendance) && Object.keys(obj.week_attendance).length > 0) {
                    var weekAttendance = obj.week_attendance;
                    angular.forEach(weekAttendance, function (day, dayKey) {
                        if (Object.keys(day).length > 0) {
                            angular.forEach(day, function (period, periodKey) {
                                switch (period) {
                                    case attendanceType.NOTMARKED.code:
                                        break;
                                    case attendanceType.PRESENT.code:
                                        ++allAttendances;
                                        ++allPresentCount;
                                        break;
                                    case attendanceType.LATE.code:
                                        ++allAttendances;
                                        ++allLateCount;
                                        break;
                                    case attendanceType.UNEXCUSED.code:
                                    case attendanceType.EXCUSED.code:
                                        ++allAttendances;
                                        ++allAbsenceCount;
                                        break;
                                }
                            });
                        }
                    });
                }
            });
            // }

            vm.allAttendances = allAttendances;
            vm.allPresentCount = allPresentCount;
            vm.allAbsenceCount = allAbsenceCount;
            vm.allLateCount = allLateCount;
        }
    }

    // params
    vm.getClassStudentParams = function () {

        vm.excelExportAUrl = vm.exportexcel
            + '?institution_id=' + vm.institutionId +
            '&institution_class_id=' + vm.selectedClass +
            '&education_grade_id=' + vm.selectedEducationGrade +
            '&academic_period_id=' + vm.selectedAcademicPeriod +
            '&day_id=' + vm.selectedDay +
            '&attendance_period_id=' + vm.selectedAttendancePeriod +
            '&week_start_day=' + vm.selectedWeekStartDate +
            '&week_end_day=' + vm.selectedWeekEndDate +
            '&subject_id=' + vm.selectedSubject +
            '&week_id=' + vm.selectedWeek

        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek,
            subject_id: vm.selectedSubject
        };
    }

    vm.getIsMarkedParams = function () {
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            subject_id: vm.selectedSubject
        };
    }

    vm.getPeriodMarkedParams = function () {
        return {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            institution_class_id: vm.selectedClass,
            education_grade_id: vm.selectedEducationGrade,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            subject_id: vm.selectedSubject
        };
    }

    // changes
    vm.changeAcademicPeriod = function () {
        vm.gridOptions.context.period = vm.selectedAcademicPeriod;
        appendLoader();
        vm.initGrid();
        getTranslatedText()
            .then(getClassOptions)
            .then(setClassOptions)
            .then(getEducationGradeOptions)
            .then(setEducationGradeOptions)
            .then(getWeekListOptions)
            .then(setWeekListOptions)
            .then(getDayListOptions)
            .then(setDayListOptions)
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    };

    vm.changeWeek = function () {
        appendLoader();
        vm.initGrid();
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedWeekStartDate = weekObj.start_day;
        vm.selectedWeekEndDate = weekObj.end_day;
        vm.gridOptions.context.week = vm.selectedWeek;
        getTranslatedText()
            .then(getDayListOptions)
            .then(setDayListOptions)
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeDay = function () {
        appendLoader();
        vm.initGrid();
        var dayObj = vm.dayListOptions.find(obj => obj.date == vm.selectedDay);
        vm.selectedDayDate = dayObj.date;
        vm.selectedFormattedDayDate = dayObj.name;
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth() + 1;
        var currentdate = currentDate.getDate();
        vm.currentDayMonthYear = currentYear + '-' + currentMonth + '-' + currentdate;
        getTranslatedText()
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);;
    }

    vm.changeClass = function () {
        appendLoader();
        if (vm.superAdmin == 0) {
            vm.updateClassRoles(vm.selectedClass);
        }
        vm.initGrid();
        getTranslatedText()
            .then(getEducationGradeOptions)
            .then(setEducationGradeOptions)
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    };

    vm.changeEducationGrade = function () {
        appendLoader();
        if (vm.superAdmin == 0) {
            vm.updateClassRoles(vm.selectedClass);
        }
        vm.initGrid();
        vm.gridOptions.context.education_grade_id = vm.selectedEducationGrade;
        getTranslatedText()
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getSubjectOptions)
            .then(setSubjectOptions)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeSubject = function () {
        // console.log(vm.selectedSubject);
        appendLoader();
        vm.initGrid();
        vm.gridOptions.context.subject_id = vm.selectedSubject;
        getTranslatedText()
            .then(isMarkableSubjectAttendance)
            .then(setMarkableSubjectAttendance)
            .then(getPeriodOptions)
            .then(setPeriodOptions)
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    }

    vm.changeAttendancePeriod = function () {

        vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        appendLoader();
        getTranslatedText()
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    }

    // button events
    vm.onEditClick = function () {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
        //ticket POCOR-6658 Starts=> comment savePeriodMarked function becuase it is not working for multigrade class
        //InstitutionStudentAttendancesArchiveSvc.savePeriodMarked(vm.getPeriodMarkedParams(), $scope);
        InstitutionStudentAttendancesArchiveSvc.getsavePeriodMarked(vm.getPeriodMarkedParams(), $scope);
        //ticket POCOR-6658 Ends
    };

    vm.updateIsMarked = function (isMarked) {
        vm.isMarked = isMarked;
        vm.gridOptions.context.isMarked = isMarked;
    };

    vm.onBackClick = function () {
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;
        appendLoader();
        getTranslatedText()
            .then(getIsMarked)
            .then(setIsMarked)
            .then(getClassStudent)
            .then(setClassStudent)
            .then(function () {
                vm.initGrid();
                vm.setColumnDef();
                vm.setGridData();
                removeLoader();
            }).catch(handleError);
    };

    vm.onNoScheduledClick = function () {
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;
        UtilsSvc.isAppendLoader(true);

        InstitutionStudentAttendancesArchiveSvc.getNoScheduledClassMarked(vm.getIsMarkedParams())
            .then(function (isMarked) {
                vm.updateIsMarked(isMarked);
                vm.setColumnDef(isMarked);
                vm.countStudentData();
                AlertSvc.reset($scope);
            }, vm.error)
            .finally(function () {
                UtilsSvc.isAppendLoader(false);
            });
    };

    vm.onExcelClick = function () {
        var excelUrlWithQuery = vm.excelUrl + '?' +
            'institution_id=' + vm.institutionId + '&' +
            'academic_period_id=' + vm.selectedAcademicPeriod + '&' +
            'class_id=' + vm.selectedClass;

        window.location.href = excelUrlWithQuery;
        return;
    }

    vm.updateClassRoles = function (selectedClass) {
        var selectedClass = selectedClass;
        var classListOptions = vm.classListOptions;
        if (classListOptions.length > 0) {
            angular.forEach(classListOptions, function (value, key) {
                if (value.SecurityRoleFunctions) {
                    if (value.id == selectedClass) {
                        vm.permissionView = value.SecurityRoleFunctions._view;
                        vm.permissionEdit = value.SecurityRoleFunctions._edit;
                    }
                }
            });
        }
    }
}
