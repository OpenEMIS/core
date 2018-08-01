angular.module('staff.attendances.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'staff.attendances.svc'])
    .controller('StaffAttendancesCtrl', StaffAttendancesController);

StaffAttendancesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'StaffAttendancesSvc'];

function StaffAttendancesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, StaffAttendancesSvc) {
    var vm = this;

    vm.action = 'view';
    vm.institutionId;

    vm.academicPeriodOptions = [];
    vm.selectedAcademicPeriod = '';

    vm.weekListOptions = [];
    vm.selectedWeek = '';

    angular.element(document).ready(function () {
        StaffAttendancesSvc.init(angular.baseUrl);
        vm.action = 'view';
        UtilsSvc.isAppendLoader(true);
        StaffAttendancesSvc.getAcademicPeriodOptions()
        .then(function(academicPeriods) {
            // console.log(academicPeriods);
            vm.setAcademicPeriodList(academicPeriods);
            return StaffAttendancesSvc.getWeekListOptions(vm.selectedAcademicPeriod);
        }, vm.error)
        .then(function(weekList) {
            vm.setWeekList(weekList);
        }, vm.error)
        .finally(function() {
            UtilsSvc.isAppendLoader(false);
        });
    });

    // error
    vm.error = function (error) {
        console.log(error);   
    }

    vm.setAcademicPeriodList = function(academicPeriods) {
        vm.academicPeriodOptions = academicPeriods;
        if (academicPeriods.length > 0) {
            angular.forEach(academicPeriods, function(academicPeriod) {
                if (academicPeriod.current == 1) {
                   vm.selectedAcademicPeriod = academicPeriod.id;
                }
            });
        }
    }

    vm.setWeekList = function(weekList) {
        vm.weekListOptions = weekList;
        if (weekList.length > 0) {
            angular.forEach(weekList, function(week) {
                if (week.selected == true) {
                   vm.selectedWeek = week.id;
                }
            });
        }
    }
}