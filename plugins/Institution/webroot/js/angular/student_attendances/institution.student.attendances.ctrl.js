angular.module('institution.student.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.attendances.svc'])
    .controller('InstitutionStudentAttendancesCtrl', InstitutionStudentAttendancesController);

InstitutionStudentAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentAttendancesSvc'];

function InstitutionStudentAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentAttendancesSvc) {
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
    vm.selectedClass = '';

    vm.attendancePeriodOptions = [];
    vm.selectedAttendancePeriod = '';

    vm.classStudentList = [];

    // gridOptions
    vm.gridReady = false;
    vm.gridOptions = {
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 71,
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
        onGridSizeChanged: function() {
            this.api.sizeColumnsToFit();
        },
        onGridReady: function() {
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
            isMarked: vm.isMarked
        },
    };

    // ready
    angular.element(document).ready(function () {
        InstitutionStudentAttendancesSvc.init(angular.baseUrl, $scope);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        UtilsSvc.isAppendLoader(true);
        if (vm.institutionId != null) {
            InstitutionStudentAttendancesSvc.getTranslatedText().
            then(function(isTranslated) {
                return InstitutionStudentAttendancesSvc.getAbsenceTypeOptions();
            }, vm.error)
            InstitutionStudentAttendancesSvc.getAbsenceTypeOptions()
            .then(function(absenceTypeOptions) {
                vm.absenceType = absenceTypeOptions;
                vm.gridOptions.context.absenceTypes = vm.absenceType;
                return InstitutionStudentAttendancesSvc.getStudentAbsenceReasonOptions();
            }, vm.error)
            .then(function(studentAbsenceReasonOptions) {
                vm.studentAbsenceReasonOptions = studentAbsenceReasonOptions;
                vm.gridOptions.context.studentAbsenceReasons = vm.studentAbsenceReasonOptions;
                return InstitutionStudentAttendancesSvc.getAcademicPeriodOptions(vm.institutionId);
            }, vm.error)
            .then(function(academicPeriodOptions) {
                vm.updateAcademicPeriodList(academicPeriodOptions);
                return InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(weekListOptions) {
                vm.updateWeekList(weekListOptions);
                return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
            }, vm.error)
            .then(function(dayListOptions) {
                vm.updateDayList(dayListOptions);
                return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(classListOptions) {
                vm.updateClassList(classListOptions);
                return InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(attendancePeriodOptions) {
                vm.updateAttendancePeriodList(attendancePeriodOptions);
                return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
            }, vm.error)
            .then(function(isMarked) {
                vm.updateIsMarked(isMarked);
                return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
            .then(function(classStudents) {
                vm.updateClassStudentList(classStudents);
            }, vm.error)
            .finally(function() {
                vm.initGrid();
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    // error
    vm.error = function (error, test) {
        return $q.reject(error);
    }

    // update data
    vm.updateAcademicPeriodList = function(academicPeriodOptions) {
        vm.academicPeriodOptions = academicPeriodOptions;
        if (academicPeriodOptions.length > 0) {
            var selectedAcademicPeriodId = 0;
            for (var i = 0; i < academicPeriodOptions.length; ++i) {
                if (angular.isDefined(academicPeriodOptions[i]['selected'] && academicPeriodOptions[i]['selected'])) {
                    selectedAcademicPeriodId = i;
                    break;
                }
            }
            vm.selectedAcademicPeriod = academicPeriodOptions[selectedAcademicPeriodId].id;
        }
    }

    vm.updateWeekList = function(weekListOptions) {
        vm.weekListOptions = weekListOptions;
        if (weekListOptions.length > 0) {
            for (var i = 0; i < weekListOptions.length; ++i) {
                if (angular.isDefined(weekListOptions[i]['selected']) && weekListOptions[i]['selected']) {
                    vm.selectedWeek = weekListOptions[i].id;
                    vm.selectedWeekStartDate = weekListOptions[i].start_day;
                    vm.selectedWeekEndDate = weekListOptions[i].end_day;
                    vm.week = vm.selectedWeek;
                    vm.gridOptions.context.week = vm.selectedWeek;
                    break;
                }
            }
        }
    }

    vm.updateDayList = function(dayListOptions) {
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
    }

    vm.updateClassList = function(classListOptions) {
        vm.classListOptions = classListOptions;
        if (classListOptions.length > 0) {
            vm.selectedClass = classListOptions[0].id;
        }
    }

    vm.updateAttendancePeriodList = function(attendancePeriodOptions) {
        vm.attendancePeriodOptions = attendancePeriodOptions;
        if (attendancePeriodOptions.length > 0) {
            vm.selectedAttendancePeriod = attendancePeriodOptions[0].id;
            vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        }
    }

    vm.updateClassStudentList = function(classStudents) {
        vm.classStudents = [];
        vm.classStudentList = classStudents;
    }

    vm.updateIsMarked = function(isMarked) {
        vm.isMarked = isMarked;
        vm.gridOptions.context.isMarked = isMarked;
    };

    // grid
    vm.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale().then(
            function(localeText) {
                vm.gridOptions.localeText = localeText;
                vm.gridReady = true;
            },
            function(error) {
                vm.gridReady = true;
            }
        );
    }

    vm.setGridData = function() {
        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setRowData(vm.classStudentList);
            vm.countStudentData();
        }
    }

    vm.setColumnDef = function() {
        var columnDefs = [];
        if (vm.selectedDay != -1) {
            columnDefs = InstitutionStudentAttendancesSvc.getSingleDayColumnDefs(vm.selectedAttendancePeriod);
        } else {
            columnDefs = InstitutionStudentAttendancesSvc.getAllDayColumnDefs(vm.dayListOptions, vm.attendancePeriodOptions);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        } else {
            vm.gridOptions.columnDefs = columnDefs;
        }
    }

    // dashboard count
    vm.countStudentData = function() {
        var attendanceType = InstitutionStudentAttendancesSvc.getAttendanceTypeList();
        if (vm.selectedDay != -1) {
            // single day
            vm.totalStudents = vm.classStudentList.length;
            if (vm.isMarked) {
                var presentCount = 0;
                var absenceCount = 0;
                var lateCount = 0;

                if (vm.totalStudents > 0) {
                    angular.forEach(vm.classStudentList, function(obj, key) {
                        if (angular.isDefined(obj['institution_student_absences']) && angular.isDefined(obj['institution_student_absences']['absence_type_code'])) {
                            var code = obj['institution_student_absences']['absence_type_code'];

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
        } else {
            // all day
            var allAttendances = '-';
            var allPresentCount = '-';
            var allAbsenceCount = '-';
            var allLateCount = '-';

            if (vm.totalStudents > 0) {
                allAttendances = 0;
                allPresentCount = 0;
                allAbsenceCount = 0;
                allLateCount = 0;

                angular.forEach(vm.classStudentList, function(obj, studentKey) {
                    if (angular.isDefined(obj.week_attendance) && Object.keys(obj.week_attendance).length > 0) {
                        var weekAttendance = obj.week_attendance;
                        angular.forEach(weekAttendance, function(day, dayKey) {
                            if (Object.keys(day).length > 0) {
                                angular.forEach(day, function(period, periodKey) {
                                    switch(period) {
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
            }

            vm.allAttendances = allAttendances;
            vm.allPresentCount = allPresentCount;
            vm.allAbsenceCount = allAbsenceCount;
            vm.allLateCount = allLateCount;
        }
    }

    // params
    vm.getClassStudentParams = function() {

        vm.excelExportAUrl = vm.exportexcel
                             +'?institution_id='+ vm.institutionId+
                            '&institution_class_id='+ vm.selectedClass+
                            '&academic_period_id='+ vm.selectedAcademicPeriod+
                            '&day_id='+ vm.selectedDay+
                            '&attendance_period_id='+ vm.selectedAttendancePeriod+
                            '&week_start_day='+ vm.selectedWeekStartDate+
                            '&week_end_day='+ vm.selectedWeekEndDate+
                            '&week_id='+ vm.selectedWeek;
        
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod,
            week_start_day: vm.selectedWeekStartDate,
            week_end_day: vm.selectedWeekEndDate,
            week_id: vm.selectedWeek
        };
    }

    vm.getIsMarkedParams = function() {
        return {
            institution_id: vm.institutionId,
            institution_class_id: vm.selectedClass,
            academic_period_id: vm.selectedAcademicPeriod,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod
        };
    }

    vm.getPeriodMarkedParams = function() {
        return {
            institution_id: vm.institutionId,
            academic_period_id: vm.selectedAcademicPeriod,
            institution_class_id: vm.selectedClass,
            day_id: vm.selectedDay,
            attendance_period_id: vm.selectedAttendancePeriod
        };
    }

    // changes
    vm.changeAcademicPeriod = function() {
        console.log("hello");
        //debugger;
        //"var test = "/search?fname="+fname"+"&lname="+lname"
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.updateWeekList(weekListOptions);
            return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId);
        }, vm.error)
        .then(function(dayListOptions) {
            vm.updateDayList(dayListOptions);
            return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(classListOptions) {
            vm.updateClassList(classListOptions);
            return InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(attendancePeriodOptions) {
            var markedParams = vm.getIsMarkedParams();
            console.log('markedParams', markedParams);
            vm.updateAttendancePeriodList(attendancePeriodOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
        }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        UtilsSvc.isAppendLoader(true);
        var weekObj = vm.weekListOptions.find(obj => obj.id == vm.selectedWeek);
        vm.selectedWeekStartDate = weekObj.start_day;
        vm.selectedWeekEndDate = weekObj.end_day;
        vm.gridOptions.context.week = vm.selectedWeek;
        InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek, vm.institutionId)
        .then(function(dayListOptions) {
            vm.updateDayList(dayListOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
        }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
        UtilsSvc.isAppendLoader(true);
        var dayObj = vm.dayListOptions.find(obj => obj.date == vm.selectedDay);
        vm.schoolClosed = (angular.isDefined(dayObj.closed) && dayObj.closed) ? true : false;
        vm.gridOptions.context.schoolClosed = vm.schoolClosed;
        vm.gridOptions.context.date = vm.selectedDay;
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeClass = function() {
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod)
        .then(function(attendancePeriodOptions) {
            vm.updateAttendancePeriodList(attendancePeriodOptions);
            return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
        }, vm.error)
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
        }, vm.error)
        .finally(function() {
            vm.setGridData();
            vm.setColumnDef();
            UtilsSvc.isAppendLoader(false);
        }); 
    }

    vm.changeAttendancePeriod = function() {
        vm.gridOptions.context.period = vm.selectedAttendancePeriod;
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    // button events
    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
        InstitutionStudentAttendancesSvc.savePeriodMarked(vm.getPeriodMarkedParams(), $scope);
    };

    vm.onBackClick = function() {
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            vm.setColumnDef();
            vm.countStudentData();
            AlertSvc.reset($scope);
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    };

    vm.onExcelClick = function() {
        var excelUrlWithQuery = vm.excelUrl + '?' + 
            'institution_id=' + vm.institutionId + '&' + 
            'academic_period_id=' + vm.selectedAcademicPeriod + '&' + 
            'class_id=' + vm.selectedClass;
            
        window.location.href = excelUrlWithQuery;
        return;
    }
}
