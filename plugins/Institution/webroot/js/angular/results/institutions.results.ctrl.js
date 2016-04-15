angular.module('institutions.results.ctrl', ['utils.svc', 'alert.svc', 'institutions.results.svc'])
.controller('InstitutionsResultsCtrl', function($scope, $filter, UtilsSvc, AlertSvc, InstitutionsResultsSvc) {
    $scope.action = 'view';
    $scope.message = null;
    $scope.results = {};
    $scope.gridOptions = null;

    angular.element(document).ready(function () {
        $scope.class_id = UtilsSvc.requestQuery('class_id');
        $scope.assessment_id = UtilsSvc.requestQuery('assessment_id');

        // init
        InstitutionsResultsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        // getAssessment
        InstitutionsResultsSvc.getAssessment($scope.assessment_id)
        .then(function(assessment) {
            $scope.assessment = assessment;
            $scope.academic_period_id = assessment.academic_period_id;
            $scope.education_grade_id = assessment.education_grade_id;
            
            return InstitutionsResultsSvc.getSubjects($scope.assessment_id);
        }, function(error) {
            // No Assessment
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getSubjects
        .then(function(subjects) {
            $scope.subjects = subjects;
            if (angular.isObject(subjects) && subjects.length > 0) {
                var subject = subjects[0];

                $scope.initGrid(subject);
            }
        }, function(error) {
            // No Assessment Items
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .finally(function(obj) {
            UtilsSvc.isAppendLoader(false);
        })
        ;
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods);
        }
    });

    $scope.initGrid = function(subject) {
        $scope.gridOptions = {
            context: {
                institution_id: $scope.institution_id,
                class_id: $scope.class_id,
                assessment_id: $scope.assessment_id,
                academic_period_id: $scope.academic_period_id,
                education_grade_id: $scope.education_grade_id,
                education_subject_id: 0
            },
            columnDefs: [],
            rowData: [],
            headerHeight: 38,
            rowHeight: 38,
            enableColResize: true,
            enableSorting: true,
            unSortIcon: true,
            enableFilter: true,
            suppressMenuHide: true,
            singleClickEdit: true,
            onCellValueChanged: function(params) {
                if (params.newValue != params.oldValue) {
                    var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                    if (angular.isUndefined($scope.results[params.data.student_id])) {
                        $scope.results[params.data.student_id] = {};
                    }

                    if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                        $scope.results[params.data.student_id][index] = {marks: ''};
                    }

                    $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                    params.data.total_mark = InstitutionsResultsSvc.calculateTotal(params.data);
                    // marked as dirty
                    params.data.is_dirty = true;
                    // Important: to refresh the grid after data is modified
                    $scope.gridOptions.api.refreshView();
                }
            },
            onGridReady: function() {
                $scope.onChangeSubject(subject);
            }
        };
    };

    $scope.resetColumnDefs = function(action, subject, periods) {
        var columnDefs = InstitutionsResultsSvc.getColumnDefs(action, subject, periods);
        if ($scope.gridOptions != null) {
            $scope.gridOptions.api.setColumnDefs(columnDefs);
            $scope.gridOptions.api.sizeColumnsToFit();
        }
    };

    $scope.onChangeSubject = function(subject) {
        AlertSvc.reset($scope);
        $scope.subject = subject;
        $scope.education_subject_id = subject.id;
        if ($scope.gridOptions != null) {
            // update value in context
            $scope.gridOptions.context.education_subject_id = subject.id;
            // Always reset
            $scope.gridOptions.api.setRowData([]);
        }

        UtilsSvc.isAppendSpinner(true, 'institution-result-table');
        // getPeriods
        InstitutionsResultsSvc.getPeriods($scope.assessment_id)
        .then(function(periods) {
            $scope.periods = periods;
            $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods);

            return InstitutionsResultsSvc.getRowData($scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.education_subject_id);
        }, function(error) {
            // No Assessment Periods
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getRowData
        .then(function(rows) {
            $scope.gridOptions.api.setRowData(rows);
        }, function(error) {
            // No Students
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'institution-result-table');
        });
    };

    $scope.onEditClick = function() {
        $scope.action = 'edit';
    };

    $scope.onBackClick = function() {
        $scope.action = 'view';
    };

    $scope.onSaveClick = function() {
        if ($scope.gridOptions != null) {
            var assessmentId = $scope.gridOptions.context.assessment_id;
            var educationSubjectId = $scope.gridOptions.context.education_subject_id;
            var institutionId = $scope.gridOptions.context.institution_id;
            var academicPeriodId = $scope.gridOptions.context.academic_period_id;
            var classId = $scope.gridOptions.context.class_id;

            UtilsSvc.isAppendSpinner(true, 'institution-result-table');
            InstitutionsResultsSvc.saveRowData($scope.results, assessmentId, educationSubjectId, institutionId, academicPeriodId)
            .then(function(response) {
                console.log(response);
            }, function(error) {
                console.log(error);
            })
            .finally(function() {
                $scope.gridOptions.api.forEachNode(function(row) {
                    if (row.data.is_dirty) {
                        InstitutionsResultsSvc.saveTotal(row.data, row.data.student_id, classId, institutionId, academicPeriodId, educationSubjectId);
                        // reset dirty flag
                        row.data.is_dirty = false;
                    }
                });

                $scope.action = 'view';
                // reset results object
                $scope.results = {};
                UtilsSvc.isAppendSpinner(false, 'institution-result-table');
            });
        } else {
            $scope.action = 'view';
        }
    };
});
