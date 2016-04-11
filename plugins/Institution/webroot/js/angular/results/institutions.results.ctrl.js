angular.module('institutions.results.ctrl', ['alert.svc', 'institutions.results.svc'])
.controller('ResultCtrl', function($scope, AlertSvc, ResultSvc) {
    $scope.action = 'view';
    $scope.message = null;
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        $scope.class_id = ResultSvc.requestQuery('class_id');
        $scope.assessment_id = ResultSvc.requestQuery('assessment_id');

        // init
        ResultSvc.init($scope.baseUrl);

        // getAssessment
        ResultSvc.getAssessment($scope.assessment_id).then(function(assessment) {
            $scope.assessment = assessment;
            $scope.academic_period_id = assessment.academic_period_id;
            $scope.education_grade_id = assessment.education_grade_id;

            // getSubjects
            ResultSvc.getSubjects($scope.assessment_id).then(function(subjects) {
                $scope.subjects = subjects;

                // getPeriods
                ResultSvc.getPeriods($scope.assessment_id).then(function(periods) {
                    $scope.periods = periods;

                    // getColumnDefs
                    $scope.columnDefs = ResultSvc.getColumnDefs($scope.action, $scope.periods);

                    var subjects = $scope.subjects;
                    var columnDefs = $scope.columnDefs;
                    if (angular.isObject(subjects) && subjects.length > 0) {
                        var subject = subjects[0];

                        $scope.initGrid(columnDefs, subject);
                    }
                }, function(error) {
                    // No Assessment Periods
                    console.log(error);
                    AlertSvc.warning($scope, error);
                });
            }, function(error) {
                // No Assessment Items
                console.log(error);
                AlertSvc.warning($scope, error);
            });
        }, function(error) {
            // No Assessment
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;

            var columnDefs = ResultSvc.getColumnDefs($scope.action, $scope.periods);
            if ($scope.gridOptions != null) {
                $scope.gridOptions.api.setColumnDefs(columnDefs);
                $scope.resizeColumns();
            }
        }
    });

    $scope.initGrid = function(columnDefs, subject) {
        $scope.gridOptions = {
            context: {
                institution_id: $scope.institution_id,
                class_id: $scope.class_id,
                assessment_id: $scope.assessment_id,
                academic_period_id: $scope.academic_period_id,
                education_grade_id: $scope.education_grade_id,
                education_subject_id: subject.id
            },
            columnDefs: columnDefs,
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            enableColResize: true,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            singleClickEdit: true,
            angularCompileRows: true,
            onCellValueChanged: function(params) {
                $scope.cellValueChanged(params);
            },
            onReady: function() {
                $scope.resizeColumns();
                $scope.reloadRowData(subject);
            }
        };
    };

    $scope.resizeColumns = function() {
        $scope.gridOptions.api.refreshView();
        $scope.gridOptions.api.sizeColumnsToFit();
    };

    $scope.reloadRowData = function(subject) {
        AlertSvc.reset($scope);
        $scope.subject = subject;
        $scope.education_subject_id = subject.id;

        if ($scope.gridOptions != null) {
            // update value in context
            $scope.gridOptions.context.education_subject_id = subject.id;

            // Always reset
            $scope.gridOptions.api.setRowData([]);
        }

        // getRowData
        ResultSvc.isAppendSpinner(true, 'institution-result-table');
        ResultSvc.getRowData($scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.education_subject_id).then(function(rowData) {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            $scope.gridOptions.api.setRowData(rowData);
        }, function(error) {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            // No Students
            console.log(error);
            AlertSvc.warning($scope, error);
        });
    };

    $scope.setRowData = function(data) {
        ResultSvc.setRowData(data, $scope);
    };

    $scope.cellValueChanged = function(params) {
        ResultSvc.cellValueChanged(params, $scope);
    };

    $scope.onEditClick = function() {
        $scope.action = 'edit';
    };

    $scope.onBackClick = function() {
        $scope.action = 'view';
    };

    $scope.onSaveClick = function() {
        ResultSvc.isAppendSpinner(true, 'institution-result-table');

        var assessmentId = $scope.gridOptions.context.assessment_id;
        var educationSubjectId = $scope.gridOptions.context.education_subject_id;
        var institutionId = $scope.gridOptions.context.institution_id;
        var academicPeriodId = $scope.gridOptions.context.academic_period_id;

        ResultSvc.saveRowData(assessmentId, educationSubjectId, institutionId, academicPeriodId).then(function(_results) {
        }, function(_errors) {
        }).finally(function() {
            ResultSvc.isAppendSpinner(false, 'institution-result-table');
            $scope.action = 'view';
        });
    };
});
