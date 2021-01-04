angular
    .module('institutions.results.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.results.svc'])
    .controller('InstitutionsResultsCtrl', InstitutionsResultsController);

InstitutionsResultsController.$inject = ['$q', '$scope', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsResultsSvc'];

function InstitutionsResultsController($q, $scope, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsResultsSvc) {
    $scope.action = 'view';
    $scope.message = null;
    $scope.gradingTypes = null;
    $scope.resultType = null;
    $scope.results = {};
    $scope.gridOptions = null;
    $scope.roles = [];
    $scope.enrolledStatus = null;
    $scope.academicTermOptions = [];
    $scope.selectedAcademicTerm = undefined;
    $scope.editPermissionForSelectedSubject = false;

    angular.element(document).ready(function () {
        // init
        InstitutionsResultsSvc.init(angular.baseUrl);

        UtilsSvc.isAppendLoader(true);
        // getAssessment
        InstitutionsResultsSvc.getAssessment($scope.assessment_id)
        .then(function(response) {
            var promises = [];
            var assessment = response.data;

            $scope.assessment = assessment;
            $scope.academic_period_id = assessment.academic_period_id;
            $scope.education_grade_id = assessment.education_grade_id;
            
            //promises[0] = InstitutionsResultsSvc.getSubjects($scope.roles, $scope.assessment_id, $scope.class_id);
            promises[0] = InstitutionsResultsSvc.getDataSubjects($scope.roles, $scope.assessment_id, $scope.class_id,$scope.academic_period_id,$scope.institution_id);
            promises[1] = InstitutionsResultsSvc.getAssessmentTerms($scope.assessment_id);
            
            return $q.all(promises);
        }, function(error) {
            // No Assessment
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getSubjects
        .then(function(promises) {
            $scope.academicTermOptions = promises[1];
            if ($scope.academicTermOptions.length > 0) {
                $scope.selectedAcademicTerm = $scope.academicTermOptions[0]['name'];
            }

            $scope.subjects = promises[0];
            if (angular.isObject($scope.subjects) && $scope.subjects.length > 0) {
                var subject = $scope.subjects[0];

                $scope.initGrid(subject);
            }

            return InstitutionsResultsSvc.getStudentStatusId("CURRENT");
        }, function(error) {
            // No Assessment Items
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getStudentStatusId (enrolled)
        .then(function(response) {
            $scope.enrolledStatus = response.data[0].id;
        }, function(error) {
            // No enrolled status
            console.log(error);
        })
        .finally(function(obj) {
            UtilsSvc.isAppendLoader(false);
        })
        ;
    });

    $scope.$watch('action', function(newValue, oldValue) {
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
        }
    });

    $scope.initGrid = function(subject) {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            $scope.gridOptions = {
                context: {
                    institution_id: $scope.institution_id,
                    class_id: $scope.class_id,
                    assessment_id: $scope.assessment_id,
                    academic_period_id: $scope.academic_period_id,
                    education_grade_id: $scope.education_grade_id,
                    education_subject_id: 0,
                    _scope: $scope
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
                suppressMovableColumns: true,
                singleClickEdit: true,
                localeText: localeText,

                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,

                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
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

                        var subject = $scope.subject;
                        var gradingTypes = $scope.gradingTypes;
                        var extra = {
                            subject: subject,
                            gradingTypes: gradingTypes
                        };

                        InstitutionsResultsSvc.saveSingleRecordData(params, extra)
                        .then(function(response) {                            
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                            // refreshCells function updated parameters
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field, 'total_mark'],
                                force: true
                            });
                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error($scope, 'There was an error when saving the result');
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field],
                                force: true
                            });
                        });
                    }
                },
                onGridReady: function() {
                    $scope.onChangeSubject(subject);
                }
            };
        }, function(error){
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
                minColWidth: 200,
                enableColResize: false,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,

                // Removed options
                // suppressCellSelection: false,

                // Added options
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,

                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
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

                        var subject = $scope.subject;
                        var gradingTypes = $scope.gradingTypes;
                        var extra = {
                            subject: subject,
                            gradingTypes: gradingTypes
                        };

                        InstitutionsResultsSvc.saveSingleRecordData(params, extra)
                        .then(function(response) {
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                            params.api.refreshCells([params.node], [params.colDef.field, 'total_mark']);

                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error($scope, 'There was an error when saving the result');
                            params.api.refreshCells([params.node], [params.colDef.field]);
                        });
                    }
                },
                onGridReady: function() {
                    $scope.onChangeSubject(subject);
                }
            };
        });
    };

    $scope.resetColumnDefs = function (action, subject, periods, gradingTypes) {
        var response = InstitutionsResultsSvc.getColumnDefs(action, subject, periods, gradingTypes, $scope.results, $scope.enrolledStatus);

        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if ($scope.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function(value, key) {
                    textToTranslate.push(value.headerName);
                });
                InstitutionsResultsSvc.translate(textToTranslate)
                .then(function(res){
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    $scope.gridOptions.api.setColumnDefs(response.data);
                    if (Object.keys(response.data).length < 15) {
                        $scope.gridOptions.api.sizeColumnsToFit();
                    }
                }, function(error){
                    console.log(error);
                });
            }
            return true;
        }
    };

    $scope.changeAcademicTerm = function() {
        $scope.onChangeSubject();
    };

    $scope.onChangeSubject = function(subject = undefined) {
        AlertSvc.reset($scope);
        $scope.action = 'view';

        if ($scope.action == 'edit') {
            AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
        }

        if (typeof subject !== "undefined") {
            $scope.subject = subject;
        }
        
        $scope.education_subject_id = $scope.subject.id;
        if ($scope.gridOptions != null) {
            // update value in context
            $scope.gridOptions.context.education_subject_id = $scope.subject.education_subject_id;
            // Always reset
            $scope.gridOptions.api.setRowData([]);
        }

        UtilsSvc.isAppendSpinner(true, 'institution-result-table');
        // getPeriods
        InstitutionsResultsSvc.getSubjectEditPermission($scope.subject.education_subject_id, $scope.class_id, $scope.academic_period_id, $scope.institution_id)
        .then(function(hasPermission) {
            $scope.editPermissionForSelectedSubject = hasPermission;
            return InstitutionsResultsSvc.getPeriods($scope.assessment_id, $scope.selectedAcademicTerm)
        }, function(error) {
            console.log(error);
        })
        .then(function(periods) {
            if (periods) {
                $scope.periods = periods;
                return InstitutionsResultsSvc.getCopyGradingTypes($scope.assessment_id, $scope.subject.education_subject_id);
            }
        }, function(error) {
            // No Assessment Periods
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // getGradingTypes
        .then(function(gradingTypes) {
            if (gradingTypes) {
                $scope.gradingTypes = gradingTypes;
                return $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
            }
        }, function(error) {
            // No Assessment Items Grading Types
            console.log(error);
            AlertSvc.warning($scope, error);
        })
        // resetColumnDefs
        .then(function(response) {
            if (response) {
                return InstitutionsResultsSvc.getRowData($scope.gradingTypes, $scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.subject.education_subject_id, $scope.education_grade_id);
                //return InstitutionsResultsSvc.getNewRowData($scope.gradingTypes, $scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.education_subject_id, $scope.education_grade_id);
            }
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
        InstitutionsResultsSvc.getSubjectEditPermission($scope.subject.education_subject_id, $scope.class_id, $scope.academic_period_id, $scope.institution_id)
        .then(function(hasPermission) {
            if(hasPermission) {
                $scope.action = 'edit';
                AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
            } else {
                $scope.action = 'view';
                AlertSvc.warning($scope, 'You have no permission for this subject.');
            }
        }, function(error) {
            console.log(error);
        })
    };

    $scope.onBackClick = function() {
        $scope.action = 'view';
        $scope.onChangeSubject($scope.subject);
        AlertSvc.reset($scope);
    };
}
