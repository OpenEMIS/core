angular
    .module('examinations.results.ctrl', ['utils.svc', 'alert.svc', 'examinations.results.svc'])
    .controller('ExaminationsResultsCtrl', ExaminationsResultsController);

ExaminationsResultsController.$inject = ['$scope', '$anchorScroll', '$filter', '$q', 'UtilsSvc', 'AlertSvc', 'ExaminationsResultsSvc'];

function ExaminationsResultsController($scope, $anchorScroll, $filter, $q, UtilsSvc, AlertSvc, ExaminationsResultsSvc) {
    var vm = this;
    $scope.action = 'view';
    vm.noOptions = [{text: 'No options', value: 0}];
    vm.education_subject = {};

    // Functions
    vm.onChangeAcademicPeriod = onChangeAcademicPeriod;
    vm.onChangeExamination = onChangeExamination;
    vm.onChangeExaminationCentre = onChangeExaminationCentre;
    vm.initGrid = initGrid;
    vm.onChangeSubject = onChangeSubject;
    vm.onChangeColumnDefs = onChangeColumnDefs;
    vm.onEditClick = onEditClick;
    vm.onBackClick = onBackClick;
    vm.onSaveClick = onSaveClick;

    // Initialisation
    angular.element(document).ready(function() {
        ExaminationsResultsSvc.init(angular.baseUrl);

        vm.academicPeriodOptions = vm.noOptions;
        vm.academicPeriodId = 0;
        vm.examinationOptions = vm.noOptions;
        vm.examinationId = 0;
        vm.examinationCentreOptions = vm.noOptions;
        vm.examinationCentreId = 0;

        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getAcademicPeriods()
        .then(function(response)
        {
            var academicPeriods = response.data;
            if (angular.isObject(academicPeriods) && academicPeriods.length > 0) 
            {
                var options = [];
                angular.forEach(academicPeriods, function(academicPeriod, i)
                {
                    this.push({text: academicPeriods[i].name.toString(), value: academicPeriods[i].id});
                }, options);

                vm.academicPeriodOptions = options;
                vm.academicPeriodId = options[0].value;

                vm.onChangeAcademicPeriod(vm.academicPeriodId);
            } else {
                vm.academicPeriodOptions = vm.noOptions;
                vm.academicPeriodId = 0;
            }
        }, function(error)
        {
            // No Academic Periods
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            vm.onChangeColumnDefs($scope.action, vm.education_subject);
        }
    });

    function onChangeAcademicPeriod(academicPeriodId) {
        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getExaminations(academicPeriodId)
        .then(function(response)
        {
            var examinations = response.data;
            if (angular.isObject(examinations) && examinations.length > 0) 
            {
                var options = [];
                angular.forEach(examinations, function(academicPeriod, i)
                {
                    options.push({text: examinations[i].name.toString(), value: examinations[i].id});
                }, options);

                vm.examinationOptions = options;
                vm.examinationId = options[0].value;
                vm.onChangeExamination(academicPeriodId, vm.examinationId);
            } else {
                vm.examinationOptions = vm.noOptions;
                vm.examinationId = 0;
            }
        }, function(error)
        {
            // No Examinations
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    }

    function onChangeExamination(academicPeriodId, examinationId) {
        UtilsSvc.isAppendLoader(true);
        ExaminationsResultsSvc.getExaminationCentres(academicPeriodId, examinationId)
        // getExaminationCentres
        .then(function(response)
        {
            var examinationCentres = response.data;
            if (angular.isObject(examinationCentres) && examinationCentres.length > 0) 
            {
                var options = [];
                for(i = 0; i < examinationCentres.length; i++) 
                {   
                    options.push({text: examinationCentres[i].name.toString(), value: examinationCentres[i].id});
                }

                vm.examinationCentreOptions = options;
                vm.examinationCentreId = options[0].value;
                vm.onChangeExaminationCentre(academicPeriodId, examinationId, vm.examinationCentreId);

                return ExaminationsResultsSvc.getSubjects(examinationId);
            } else {
                vm.examinationCentreOptions = vm.noOptions;
                vm.examinationCentreId = 0;
            }
        }, function(error)
        {
            // No Examination Centres
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        // getSubjects
        .then(function(subjects)
        {
            vm.subjects = subjects;
            if (angular.isObject(subjects) && subjects.length > 0) {
                var subject = subjects[0];

                vm.initGrid(subject);
            }
        }, function(error)
        {
            // No Examination Centres
            console.log(error);
            AlertSvc.warning(vm, error);
        })
        .finally(function(){
            UtilsSvc.isAppendLoader(false);
        });
    }

    function onChangeExaminationCentre(academicPeriodId, examinationId, examinationCentreId) {

    }

    function initGrid(subject) {
        vm.gridOptions = {
            context: {
                education_subject_id: 0
            },
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            minColWidth: 200,
            enableColResize: false,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            suppressCellSelection: true,
            suppressMovableColumns: true,
            singleClickEdit: true,
            onCellValueChanged: function(params) {
                if (params.newValue != params.oldValue) {
                    // Important: to refresh the grid after data is modified
                    vm.gridOptions.api.refreshView();
                }
            },
            onGridReady: function() {
                vm.onChangeSubject(subject);
            }
        };
    }

    function onChangeSubject(subject) {
        AlertSvc.reset(vm);
        vm.education_subject = subject;

        if (vm.gridOptions != null) {
            // update value in context
            vm.gridOptions.context.education_subject_id = subject.id;
            // Always reset
            vm.gridOptions.api.setRowData([]);
        }

        vm.onChangeColumnDefs($scope.action, subject);
    }

    function onChangeColumnDefs(action, subject) {
        ExaminationsResultsSvc.getColumnDefs(action, subject)
        .then(function(cols)
        {
            if (vm.gridOptions != null) {
                vm.gridOptions.api.setColumnDefs(cols);
                if (Object.keys(cols).length < 15) {
                    vm.gridOptions.api.sizeColumnsToFit();
                }
            }
        }, function(error) {
            // No Grading Options
            console.log(error);
            AlertSvc.warning(vm, error);
        });
    }

    function onEditClick() {
        $scope.action = 'edit';
    }

    function onBackClick() {
        $scope.action = 'view';
    }

    function onSaveClick() {
    }
}
