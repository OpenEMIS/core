angular
    .module('institutions.results.archive.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.results.archive.svc'])
    .controller('InstitutionsAssessmentArchiveCtrl', InstitutionsResultsArchiveController);

InstitutionsResultsArchiveController.$inject = ['$q', '$scope', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsAssessmentArchiveSvc'];

function InstitutionsResultsArchiveController($q, $scope, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsAssessmentArchiveSvc) {
    var vm = this;
    vm.exportexcel = '';
    vm.excelExportAUrl = '';
    $scope.action = 'view';
    $scope.message = null;
    $scope.gradingTypes = null;
    $scope.resultType = null;
    $scope.results = {};
    $scope.gridOptions = null;
    $scope.roles = [];
    $scope.enrolledStatus = null;
    $scope.academicTermOptions = [];
    $scope.academicPeriodOptions = [];
    $scope.selectedAcademicTerm = undefined;
    $scope.selectedAcademicPeriod = undefined;
    $scope.editPermissionForSelectedSubject = false;
    $scope.editPermission = 0;

    function handleError(error) {
        // No Assessment
        removeLoader();
        console.error(error);
        AlertSvc.warning($scope, error);
    }

    function removeLoader() {
        UtilsSvc.isAppendLoader(false);
    }

    function appendLoader() {
        UtilsSvc.isAppendLoader(true);
    }

    angular.element(document).ready(function () {
        // init
        InstitutionsAssessmentArchiveSvc.init(angular.baseUrl);

        appendLoader();
        // getAssessment
        InstitutionsAssessmentArchiveSvc.getAssessment($scope.assessment_id)
            .then(
                setAssessment,
                handleError)
            .then(
                getAssessmentTerms,
                handleError)
            // getSubjects
            .then(
                handleSuccessGetAssessmentTerms,
                handleError)
            .then(
                handleSuccessGetAcademicPeriod,
                handleError)
            .then(
                handleSuccessGetDataSubjects,
                handleError)
            .then(handleSuccessGetStudentStatusId,
                handleError)
            .finally(removeLoader)
        ;

        function setAssessment(result) {
            // console.log('setAssessment');
            // console.log(JSON.stringify(result));
            var assessment = result.data;
            $scope.assessment = assessment;
            $scope.academic_period_id = assessment.academic_period_id;
            $scope.education_grade_id = assessment.education_grade_id;
        }

        function getAssessmentTerms() {
            // console.log('getAssessmentTerms');
            // console.log(JSON.stringify(result));
            var promise;
            promise = InstitutionsAssessmentArchiveSvc.getAssessmentTerms($scope.assessment_id);
            return promise.then(function (result) {
                return result;
            });
        }

        function handleSuccessGetAssessmentTerms(result) {
            // console.log('handleSuccessGetAssessmentTerms');
            // console.log(JSON.stringify(result));

            var promise;
            $scope.academicTermOptions = result;

            if ($scope.academicTermOptions.length > 0) {
                $scope.selectedAcademicTerm = $scope.academicTermOptions[0]['name'];
            }
            //promises[0] = InstitutionsResultsSvc.getSubjects($scope.roles, $scope.assessment_id, $scope.class_id);
            promise = InstitutionsAssessmentArchiveSvc.getAcademicPeriod();

            return promise.then(function (result) {
                return result;
            });
        }

        function handleSuccessGetAcademicPeriod(result) {
            // console.log('handleSuccessGetAcademicPeriod');
            // console.log(JSON.stringify(result));
            var promise;
            $scope.academicPeriodOptions = result;
            if ($scope.academicPeriodOptions.length > 0) {
                $scope.selectedAcademicPeriod = $scope.academicPeriodOptions[0]['name'];
            }

            promise = InstitutionsAssessmentArchiveSvc.getDataSubjects($scope.roles,
                $scope.assessment_id,
                $scope.class_id,
                $scope.academic_period_id,
                $scope.institution_id);

            return promise.then(function (result) {
                return result;
            });
        }

        function handleSuccessGetDataSubjects(result) {
            // console.log('handleSuccessGetDataSubjects');
            // console.log(JSON.stringify(result));

            var promise;

            $scope.editPermission = result[0].is_editable;
            $scope.subjects = result;

            promise = InstitutionsAssessmentArchiveSvc.getStudentStatusId("CURRENT");
            return promise.then(function (result) {
                return result;
            });
        }

        function handleSuccessGetStudentStatusId(result) {
            // console.log('handleSuccessGetStudentStatusId');
            // console.log(JSON.stringify(result));
            $scope.enrolledStatus = result.data[0].id;
            if (angular.isObject($scope.subjects) && $scope.subjects.length > 0) {
                var subject = $scope.subjects[0];
                $scope.initGrid(subject);
            }

        }

    });

    $scope.$watch('action', watchAction);

    function watchAction(newValue, oldValue) {
        // console.log('watchAction');
        // console.log(JSON.stringify(newValue));
        // console.log(JSON.stringify(oldValue));
        if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
            $scope.action = newValue;
            $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
        }
    }

    // angular.element(document).ready(function () {
    //     // init
    //     InstitutionsAssessmentArchiveSvc.init(angular.baseUrl);
    //
    //     UtilsSvc.isAppendLoader(true);
    //     // getAssessment
    //     InstitutionsAssessmentArchiveSvc.getAssessment($scope.assessment_id)
    //     .then(function(response) {
    //         var promises = [];
    //         var assessment = response.data;
    //
    //         $scope.assessment = assessment;
    //         $scope.academic_period_id = assessment.academic_period_id;
    //         $scope.education_grade_id = assessment.education_grade_id;
    //
    //         //promises[0] = InstitutionsAssessmentArchiveSvc.getSubjects($scope.roles, $scope.assessment_id, $scope.class_id);
    //         promises[0] = InstitutionsAssessmentArchiveSvc.getDataSubjects($scope.roles, $scope.assessment_id, $scope.class_id,$scope.academic_period_id,$scope.institution_id);
    //         promises[1] = InstitutionsAssessmentArchiveSvc.getAssessmentTerms($scope.assessment_id);
    //         promises[2] = InstitutionsAssessmentArchiveSvc.getAcademicPeriod();
    //
    //         return $q.all(promises);
    //     }, function(error) {
    //         // No Assessment
    //         console.log(error);
    //         AlertSvc.warning($scope, error);
    //     })
    //     // getSubjects
    //     .then(function(promises) {
    //         $scope.academicTermOptions = promises[1];
    //
    //         if ($scope.academicTermOptions.length > 0) {
    //             $scope.selectedAcademicTerm = $scope.academicTermOptions[0]['name'];
    //         }
    //
    //         $scope.academicPeriodOptions = promises[2];
    //         if ($scope.academicPeriodOptions.length > 0) {
    //             $scope.selectedAcademicPeriod = $scope.academicPeriodOptions[0]['name'];
    //         }
    //
    //
    //         $scope.editPermission=promises[0][0].is_editable;
    //
    //          $scope.subjects = promises[0];
    //         if (angular.isObject($scope.subjects) && $scope.subjects.length > 0) {
    //             var subject = $scope.subjects[0];
    //
    //             $scope.initGrid(subject);
    //         }
    //
    //         return InstitutionsAssessmentArchiveSvc.getStudentStatusId("CURRENT");
    //     }, function(error) {
    //         // No Assessment Items
    //         console.log(error);
    //         AlertSvc.warning($scope, error);
    //     })
    //     // getStudentStatusId (enrolled)
    //     .then(function(response) {
    //         $scope.enrolledStatus = response.data[0].id;
    //     }, function(error) {
    //         // No enrolled status
    //         console.log(error);
    //     })
    //     .finally(function(obj) {
    //         UtilsSvc.isAppendLoader(false);
    //     })
    //     ;
    // });
    //
    // $scope.$watch('action', function(newValue, oldValue) {
    //     if (angular.isDefined(newValue) && angular.isDefined(oldValue) && newValue != oldValue) {
    //         $scope.action = newValue;
    //         $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
    //     }
    // });

    $scope.initGrid = function (subject) {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
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

                    onCellValueChanged: function (params) {
                        if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                            var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                            if (angular.isUndefined($scope.results[params.data.student_id])) {
                                $scope.results[params.data.student_id] = {};
                            }

                            if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                                $scope.results[params.data.student_id][index] = {marks: ''};
                            }

                            $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                            params.data.total_mark = InstitutionsAssessmentArchiveSvc.calculateTotal(params.data);
                            // marked as dirty
                            params.data.is_dirty = true;

                            var subject = $scope.subject;
                            var gradingTypes = $scope.gradingTypes;
                            var extra = {
                                subject: subject,
                                gradingTypes: gradingTypes
                            };

                            InstitutionsAssessmentArchiveSvc.saveSingleRecordData(params, extra)
                                .then(function (response) {
                                    params.data.save_error[params.colDef.field] = false;
                                    AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                                    // refreshCells function updated parameters
                                    params.api.refreshCells({
                                        rowNodes: [params.node],
                                        columns: [params.colDef.field, 'total_mark'],
                                        force: true
                                    });
                                }, function (error) {
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
                    onGridReady: function () {
                        $scope.onChangeSubject(subject);
                    }
                };
            }, function (error) {
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

                    onCellValueChanged: function (params) {
                        if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                            var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                            if (angular.isUndefined($scope.results[params.data.student_id])) {
                                $scope.results[params.data.student_id] = {};
                            }

                            if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                                $scope.results[params.data.student_id][index] = {marks: ''};
                            }

                            $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                            params.data.total_mark = InstitutionsAssessmentArchiveSvc.calculateTotal(params.data);
                            // marked as dirty
                            params.data.is_dirty = true;

                            var subject = $scope.subject;
                            var gradingTypes = $scope.gradingTypes;
                            var extra = {
                                subject: subject,
                                gradingTypes: gradingTypes
                            };

                            InstitutionsAssessmentArchiveSvc.saveSingleRecordData(params, extra)
                                .then(function (response) {
                                    params.data.save_error[params.colDef.field] = false;
                                    AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                                    params.api.refreshCells([params.node], [params.colDef.field, 'total_mark']);

                                }, function (error) {
                                    params.data.save_error[params.colDef.field] = true;
                                    console.log(error);
                                    AlertSvc.error($scope, 'There was an error when saving the result');
                                    params.api.refreshCells([params.node], [params.colDef.field]);
                                });
                        }
                    },
                    onGridReady: function () {
                        $scope.onChangeSubject(subject);
                    }
                };
            });
    };

    $scope.resetColumnDefs = function (action, subject, periods, gradingTypes) {
        var response = InstitutionsAssessmentArchiveSvc.getColumnDefs(action, subject, periods, gradingTypes, $scope.results, $scope.enrolledStatus);

        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if ($scope.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function (value, key) {
                    textToTranslate.push(value.headerName);
                });
                InstitutionsAssessmentArchiveSvc.translate(textToTranslate)
                    .then(function (res) {
                        angular.forEach(res, function (value, key) {
                            response.data[key]['headerName'] = value;
                        });
                        $scope.gridOptions.api.setColumnDefs(response.data);
                        if (Object.keys(response.data).length < 15) {
                            $scope.gridOptions.api.sizeColumnsToFit();
                        }
                    }, function (error) {
                        console.log(error);
                    });
            }
            return true;
        }
    };

    $scope.changeAcademicTerm = function () {
        $scope.onChangeSubject();
    };

    $scope.onChangeSubject = function (subject = undefined, editable) {
        //console.log(editable);
        if (editable != undefined) {
            $scope.editPermission = 0;
        }
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

        appendLoader();
        // getPeriods
        InstitutionsAssessmentArchiveSvc.getSubjectEditPermission($scope.subject.education_subject_id, $scope.class_id, $scope.academic_period_id, $scope.institution_id)
            .then(function (hasPermission) {
                $scope.editPermissionForSelectedSubject = hasPermission;
                return InstitutionsAssessmentArchiveSvc.getPeriods($scope.assessment_id, $scope.selectedAcademicTerm)
            }, handleError)
            .then(function (periods) {
                if (periods) {
                    $scope.periods = periods;
                    return InstitutionsAssessmentArchiveSvc.getCopyGradingTypes($scope.assessment_id, $scope.subject.education_subject_id);
                }
            }, handleError)
            // getGradingTypes
            .then(function (gradingTypes) {
                if (gradingTypes) {
                    $scope.gradingTypes = gradingTypes;
                    return $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
                }
            }, handleError)
            // resetColumnDefs
            .then(function (response) {
                if (response) {
                    return InstitutionsAssessmentArchiveSvc.getNewRowData($scope.gradingTypes, $scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.education_subject_id, $scope.education_grade_id);
                }
            }, handleError)
            // getRowData
            .then(function (rows) {
                $scope.gridOptions.api.setRowData(rows);
            }, handleError)
            .finally(function () {
                removeLoader();
            });
    };

    $scope.onEditClick = function () {
        InstitutionsAssessmentArchiveSvc.getSubjectEditPermission($scope.subject.education_subject_id, $scope.class_id, $scope.academic_period_id, $scope.institution_id)
            .then(function (hasPermission) {
                if (hasPermission) {
                    $scope.action = 'edit';
                    AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                } else {
                    $scope.action = 'view';
                    AlertSvc.warning($scope, 'You have no permission for this subject.');
                }
            }, function (error) {
                console.log(error);
            })
    };

    $scope.onBackClick = function () {
        $scope.action = 'view';
        $scope.onChangeSubject($scope.subject);
        AlertSvc.reset($scope);
    };
}
