angular.module('institution.student.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.attendances.svc'])
    .controller('InstitutionStudentAttendancesCtrl', InstitutionStudentAttendancesController);

InstitutionStudentAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentAttendancesSvc'];

function InstitutionStudentAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentAttendancesSvc) {
    var vm = this;

    vm.action = 'view';
    vm.institutionId;

    // Options
    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';

    vm.dayListOptions = [];
    vm.selectedDay = '';

    vm.classListOptions = [];
    vm.selectedClass = '';

    // gridOptions
    vm.gridOptions;

    // Error
    vm.error = function (error) {
        console.log(error);   
    }

    angular.element(document).ready(function () {
        InstitutionStudentAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';

        UtilsSvc.isAppendLoader(true);

        if (vm.institutionId != null) {
            InstitutionStudentAttendancesSvc.getAcademicPeriodOptions(vm.institutionId)
            .then(function(academicPeriodOptions) {
                // console.log('Controller - academicPeriodOptions', academicPeriodOptions);
                if (academicPeriodOptions.length > 0) {
                    vm.selectedAcademicPeriod = academicPeriodOptions[0].id;
                }
                vm.academicPeriodOptions = academicPeriodOptions;

                return InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
            }, vm.error)
            .then(function(weekListOptions) {
                // console.log('Controller - weekListOptions', weekListOptions);
                if (weekListOptions.length > 0) {
                    for (var i = 0; i < weekListOptions.length; ++i) {
                        if (angular.isDefined(weekListOptions[i]['selected']) && weekListOptions[i]['selected']) {
                            vm.selectedWeek = weekListOptions[i].id;
                            break;
                        }
                    }
                    vm.weekListOptions = weekListOptions;
                    return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
                }
            }, vm.error)
            .then(function(dayListOptions) {
                // console.log('Controller - dayListOptions', dayListOptions);
                if (dayListOptions.length > 0) {
                    for (var i = 0; i < dayListOptions.length; ++i) {
                        if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                            vm.selectedDay = dayListOptions[i].id;
                            break;
                        }
                    }
                    vm.dayListOptions = dayListOptions;
                    return InstitutionStudentAttendancesSvc.getClassOptions(vm.institutionId, vm.selectedAcademicPeriod);
                }

            }, vm.error)
            .then(function(classListOptions) {
                console.log('Controller - classListOptions', classListOptions);
                if (classListOptions.length > 0) {
                    vm.selectedClass = classListOptions[0].id;
                }
                vm.classListOptions = classListOptions;
            }, vm.error)
            .finally(function() {
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    vm.changeAcademicPeriod = function() {
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod)
        .then(function(weekListOptions) {
            if (weekListOptions.length > 0) {
                for (var i = 0; i < weekListOptions.length; ++i) {
                    if (angular.isDefined(weekListOptions[i]['selected']) && weekListOptions[i]['selected']) {
                        vm.selectedWeek = weekListOptions[i].id;
                        break;
                    }
                }
                vm.weekListOptions = weekListOptions;
                return InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek);
            }
        }, vm.error)
        .then(function(dayListOptions) {
            if (dayListOptions.length > 0) {
                for (var i = 0; i < dayListOptions.length; ++i) {
                    if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                        vm.selectedDay = dayListOptions[i].id;
                        break;
                    }
                }
                vm.dayListOptions = dayListOptions;
            }
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
}

    vm.changeWeek = function() {
        console.log('change week!', vm.selectedWeek);
        UtilsSvc.isAppendLoader(true);
        InstitutionStudentAttendancesSvc.getDayListOptions(vm.selectedAcademicPeriod, vm.selectedWeek)
        .then(function(dayListOptions) {
            if (dayListOptions.length > 0) {
                for (var i = 0; i < dayListOptions.length; ++i) {
                    if (angular.isDefined(dayListOptions[i]['selected']) && dayListOptions[i]['selected']) {
                        vm.selectedDay = dayListOptions[i].id;
                        break;
                    }
                }
                vm.dayListOptions = dayListOptions;
            }
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    }

    vm.changeDay = function() {
        console.log('change day!', vm.selectedDay);
    }

    vm.changeClass = function() {
        console.log('change class!', vm.selectedClass);
    }

    vm.onEditClick = function() {
        vm.action = 'edit';
        AlertSvc.info($scope, 'Attendances will be automatically saved.');
    };

    vm.onBackClick = function() {
        vm.action = 'view';
        AlertSvc.reset($scope);
    };

    vm.initGrid = function() {

    }
}