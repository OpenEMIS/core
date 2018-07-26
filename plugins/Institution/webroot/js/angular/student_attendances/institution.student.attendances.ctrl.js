angular.module('institution.student.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.attendances.svc'])
    .controller('InstitutionStudentAttendancesCtrl', InstitutionStudentAttendancesController);

InstitutionStudentAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentAttendancesSvc'];

function InstitutionStudentAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentAttendancesSvc) {
    var vm = this;

    vm.action = 'view';
    vm.institutionId;

    vm.absenceTypeOptions = [];
    vm.studentAbsenceReasonOptions = [];
    vm.isMarked = false;

    // Dashboards
    vm.totalStudents = '-';
    vm.presentCount = '-';
    vm.absenceCount = '-'

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

    // gridOptions
    vm.gridReady = false;
    vm.classStudentList = [];
    vm.gridOptions = {
        columnDefs: [],
        rowData: [],
        headerHeight: 38,
        rowHeight: 135,
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
            absenceType: vm.absenceTypeOptions,
            studentAbsenceReason: vm.studentAbsenceReasonOptions,
            date: vm.selectedDay,
            week: vm.selectedWeek,
            period: vm.selectedAttendancePeriod,
            absenceType: vm.absenceType,
            isMarked: vm.isMarked,
            mode: vm.action
        },
    };

    // error
    vm.error = function (error) {
        console.log(error);   
    }

    angular.element(document).ready(function () {
        InstitutionStudentAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';
        vm.gridOptions.context.mode = vm.action;

        UtilsSvc.isAppendLoader(true);
        if (vm.institutionId != null) {
            InstitutionStudentAttendancesSvc.getAbsenceTypeOptions()
            .then(function(absenceType) {
                vm.absenceType = absenceType;
                vm.gridOptions.context.absenceType = vm.absenceType;
                return InstitutionStudentAttendancesSvc.getStudentAbsenceReasonOptions();
            }, vm.error)
            .then(function(studentAbsenceReasonOptions) {
                vm.studentAbsenceReasonOptions = studentAbsenceReasonOptions;
                vm.gridOptions.context.studentAbsenceReason = vm.studentAbsenceReasonOptions;
                return InstitutionStudentAttendancesSvc.getAcademicPeriodOptions(vm.institutionId);
            }, vm.error)
            .then(function(academicPeriodOptions) {
                // console.log('Controller - academicPeriodOptions', academicPeriodOptions);
                vm.updateAcademicPeriodList(academicPeriodOptions);
                return InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(weekListOptions) {
                // console.log('Controller - weekListOptions', weekListOptions);
                vm.updateWeekList(weekListOptions);
                return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
            }, vm.error)
            .then(function(dayListOptions) {
                // console.log('Controller - dayListOptions', dayListOptions);
                vm.updateDayList(dayListOptions);
                return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(classListOptions) {
                // console.log('Controller - classListOptions', classListOptions);
                vm.updateClassList(classListOptions);
                return InstitutionStudentAttendancesSvc.getPeriodOptions(vm.selectedClass, vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(attendancePeriodOptions) {
                // console.log('Controller - attendancePeriodOptions', attendancePeriodOptions);
                vm.updateAttendancePeriodList(attendancePeriodOptions);
                return InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams());
            }, vm.error)
            .then(function(isMarked) {
                // console.log('Controller - isMarked', isMarked);
                vm.updateIsMarked(isMarked);
                return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
            }, vm.error)
            .then(function(classStudents) {
                vm.updateClassStudentList(classStudents);
                vm.initGrid();
            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    // update data
    vm.updateAcademicPeriodList = function(academicPeriodOptions) {
        vm.academicPeriodOptions = academicPeriodOptions;
        if (academicPeriodOptions.length > 0) {
            vm.selectedAcademicPeriod = academicPeriodOptions[0].id;
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
                    break;
                }
            }
        }
    }

    vm.updateDayList = function(dayListOptions) {
        vm.dayListOptions = dayListOptions;
        if (dayListOptions.length > 0) {
            for (var i = 0; i < dayListOptions.length; ++i) {
                if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                    vm.selectedDay = dayListOptions[i].date;
                    vm.gridOptions.context.date = vm.selectedDay;
                    break;
                }
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
            // vm.gridOptions.headerHeight = vm.groupColumnHeight;
            columnDefs = InstitutionStudentAttendancesSvc.getAllDayColumnDefs(vm.dayListOptions, vm.attendancePeriodOptions);
        }

        if (angular.isDefined(vm.gridOptions.api)) {
            // console.log('vm.classStudentList', vm.classStudentList);
            vm.gridOptions.api.setColumnDefs(columnDefs);
            vm.gridOptions.api.sizeColumnsToFit();
        }
    }

    // dashboard count
    vm.countStudentData = function() {
        vm.totalStudents = vm.classStudentList.length;

        if (vm.isMarked) {
            var presentCount = 0;
            var absenceCount = 0;

            if (vm.totalStudents > 0) {
                angular.forEach(vm.classStudentList, function(obj, key) {
                    if (angular.isDefined(obj['institution_student_absences']) && angular.isDefined(obj['institution_student_absences']['absence_type_code'])) {
                        var code = obj['institution_student_absences']['absence_type_code'];

                        switch (code) {
                            case null:
                            case 'PRESENT':
                            case 'LATE':
                                ++presentCount;
                                break;
                            case 'UNEXCUSED':
                            case 'EXCUSED':
                                ++absenceCount;
                                break;
                        }
                    } 
                });
            }

            vm.presentCount = presentCount;
            vm.absenceCount = absenceCount;
        } else {
            vm.presentCount = '-';
            vm.absenceCount = '-';
        }
    }

    // params
    vm.getClassStudentParams = function() {
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

    // changes
    vm.changeAcademicPeriod = function() {
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            vm.updateWeekList(weekListOptions);
            return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
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
            // console.log('classStudents', classStudents);
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeWeek = function() {
        console.log('change week!', vm.selectedWeek);
        UtilsSvc.isAppendLoader(true);
        vm.selectedWeekStartDate = vm.weekListOptions[vm.selectedWeek].start_day;
        vm.selectedWeekEndDate = vm.weekListOptions[vm.selectedWeek].end_day;
        InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek)
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
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
        console.log('change day!', vm.selectedDay);
        UtilsSvc.isAppendLoader(true);
        vm.gridOptions.context.date = vm.selectedDay;
        InstitutionStudentAttendancesSvc.getIsMarked(vm.getIsMarkedParams())
        .then(function(isMarked) {
            vm.updateIsMarked(isMarked);
            return InstitutionStudentAttendancesSvc.getClassStudent(vm.getClassStudentParams());
        }, vm.error)
        .then(function(classStudents) {
            vm.updateClassStudentList(classStudents);
            vm.setGridData();
            vm.setColumnDef();
            }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeClass = function() {
        console.log('change class!', vm.selectedClass);

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
            vm.setGridData();
            vm.setColumnDef();
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        }); 
    }

    vm.changeAttendancePeriod = function() {
        console.log('change attendance!', vm.selectedAttendancePeriod);
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

    vm.onEditClick = function() {
        vm.action = 'edit';
        vm.gridOptions.context.mode = vm.action;
        vm.setColumnDef();
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
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
}