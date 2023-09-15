angular
    .module('institutions.results.archive.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.results.archive.svc'])
    .controller('InstitutionsAssessmentArchiveCtrl', InstitutionsResultsArchiveCtrl);

InstitutionsResultsArchiveCtrl.$inject = ['$q', '$scope', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsResultsArchiveSvc'];

function InstitutionsResultsArchiveCtrl($q,
                                        $scope,
                                        $filter,
                                        UtilsSvc,
                                        AlertSvc,
                                        AggridLocaleSvc,
                                        InstitutionsResultsArchiveSvc) {


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
        InstitutionsResultsArchiveSvc.init(angular.baseUrl);

        appendLoader();
        // getAssessment
        InstitutionsResultsArchiveSvc.getAssessment($scope.assessment_id)
            .then(
                handleSuccessGetAssessment,
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

        function handleSuccessGetAssessment(result) {
            // console.log('handleSuccessGetAssessment');
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
            promise = InstitutionsResultsArchiveSvc.getAssessmentTerms($scope.assessment_id);
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
            //promises[0] = InstitutionsResultsArchiveSvc.getSubjects($scope.roles, $scope.assessment_id, $scope.class_id);
            promise = InstitutionsResultsArchiveSvc.getAcademicPeriod();

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

            promise = InstitutionsResultsArchiveSvc.getDataSubjects($scope.roles,
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

            promise = InstitutionsResultsArchiveSvc.getStudentStatusId("CURRENT");
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

    $scope.initGrid = function (subject) {
        // console.log('$scope.initGrid');
        // console.log(JSON.stringify(subject));
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
            localeText: null,

            // Removed options - Issues in ag-Grid AG-828
            // suppressCellSelection: true,

            // Added options
            suppressContextMenu: true,
            stopEditingWhenGridLosesFocus: true,
            ensureDomOrder: true,

            onCellValueChanged: onAggridCellValueChanged,
            onGridReady: onAggridGridReady
        };

        function onAggridGridReady() {
            // console.log('onAggridGridReady');
            // console.log(JSON.stringify(subject));
            $scope.onChangeSubject(subject);
        }

        function onAggridCellValueChanged(params) {
            // console.log('onCellValueChanged')
            // console.log(JSON.stringify(params));
            if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                var index = params.colDef.field.replace(/period_(\d+)/, '$1');

                if (angular.isUndefined($scope.results[params.data.student_id])) {
                    $scope.results[params.data.student_id] = {};
                }

                if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                    $scope.results[params.data.student_id][index] = {marks: ''};
                }

                $scope.results[params.data.student_id][index]['marks'] = params.newValue;

                params.data.total_mark = InstitutionsResultsArchiveSvc.calculateTotal(params.data);
                // marked as dirty
                params.data.is_dirty = true;

                var subject = $scope.subject;
                var gradingTypes = $scope.gradingTypes;
                var extra = {
                    subject: subject,
                    gradingTypes: gradingTypes
                };

                InstitutionsResultsArchiveSvc.saveSingleRecordData(params, extra)
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
                        console.error(error);
                        AlertSvc.error($scope, 'There was an error when saving the result');
                        params.api.refreshCells({
                            rowNodes: [params.node],
                            columns: [params.colDef.field],
                            force: true
                        });
                    });
            }
        }

        AggridLocaleSvc.getTranslatedGridLocale()
            .then(handleSuccessGetTranslatedGridLocale, handleErrorGetTranslatedGridLocale);

        function handleErrorGetTranslatedGridLocale(error) {
            console.log('handleErrorGetTranslatedGridLocale');
            console.error(JSON.stringify(error));

        }

        function handleSuccessGetTranslatedGridLocale(localeText) {
            // console.log('handleSuccessGetTranslatedGridLocale');
            // console.log(JSON.stringify(localeText));
            $scope.gridOptions.localeText = localeText;
        }
    };

    $scope.resetColumnDefs = function (action, subject, periods, gradingTypes) {
        var response = InstitutionsResultsArchiveSvc.getColumnDefs(action, subject, periods, gradingTypes, $scope.results, $scope.enrolledStatus);
        // console.log(JSON.stringify(response));
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
                InstitutionsResultsArchiveSvc.translate(textToTranslate)
                    .then(function (res) {
                        angular.forEach(res, function (value, key) {
                            response.data[key]['headerName'] = value;
                        });
                        $scope.gridOptions.api.setColumnDefs(response.data);
                        if (Object.keys(response.data).length < 15) {
                            $scope.gridOptions.api.sizeColumnsToFit();
                        }
                    }, function (error) {
                        console.error(error);
                    });
            }
            return true;
        }
    };

    $scope.changeAcademicTerm = function () {
        // console.log('changeAcademicTerm');
        $scope.onChangeSubject();
    };

    $scope.onChangeSubject = function (subject = undefined, editable = undefined) {
        // console.log('onChangeSubject');
        //
        // console.log($scope.selectedAcademicTerm);

        AlertSvc.reset($scope);
        $scope.action = 'view';

        if (typeof editable !== 'undefined') {
            $scope.editPermission = editable;
        }

        if (typeof subject !== 'undefined') {
            $scope.subject = subject;
        }

        if (typeof subject === 'undefined') {
            // $scope.subject = undefined;
            $scope.education_subject_id = undefined;
            // return;
        }

        $scope.education_subject_id = $scope.subject.id;

        if ($scope.gridOptions != null) {
            // console.log('$scope.gridOptions != null' + $scope.gridOptions);
            // update value in context
            $scope.gridOptions.context.education_subject_id = $scope.subject.education_subject_id;
            // Always reset
            $scope.gridOptions.api.setRowData([]);
        }

        // console.log('$scope.gridOptions == null' + $scope.gridOptions);

        appendLoader();
        // getPeriods
        InstitutionsResultsArchiveSvc.getPermissions()
            .then(handleGetPermissions, handleError)
            .then(handleGetSubjectEditPermissionSetPeriods, handleError)
            .then(handleGetPeriodsSetCopyGradingTypes, handleError)
            .then(handleGetGradingTypes, handleError)
            .then(handleResetColumnDefs, handleError)
            // getRowData
            .then(handleGetRowData, handleError)
            .finally(function () {
                removeLoader();
            });

        function handleGetRowData(result) {
            // console.log('handleGetRowData');
            // console.log(JSON.stringify(result));
            $scope.gridOptions.api.setRowData(result);
        }

        function handleResetColumnDefs(result) {
            // console.log('handleGetPermissions');
            // console.log(JSON.stringify(result));
            var promise;
//                return InstitutionsResultsArchiveSvc.getRowData($scope.gradingTypes, $scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.subject.education_subject_id, $scope.education_grade_id);
            var options = {
                grading_types: $scope.gradingTypes,
                periods: $scope.periods,
                institution_id: $scope.institution_id,
                institution_class_id: $scope.class_id,
                assessment_id: $scope.assessment_id,
                academic_period_id: $scope.academic_period_id,
                institution_subject_id: $scope.education_subject_id,
                education_grade_id: $scope.education_grade_id
            };
            promise = InstitutionsResultsArchiveSvc.getNewRowData(
                options);
            return promise.then(function (result) {
                return result;
            });
        }

        function handleGetPermissions(result) {
            // console.log('handleGetPermissions');
            // console.log(JSON.stringify(result));
            var promise;
            var is_super_admin = result[0];
            var security_user_id = result[1];
            var options = {
                subject_id: $scope.subject.education_subject_id,
                class_id: $scope.class_id,
                academic_period_id: $scope.academic_period_id,
                institution_id: $scope.institution_id,
                security_user_id: security_user_id,
                is_super_admin: is_super_admin,
            };
            promise = InstitutionsResultsArchiveSvc.getSubjectEditPermission(options);
            return promise.then(function (result) {
                return result;
            });
        }

        function handleGetSubjectEditPermissionSetPeriods(result) {
            // console.log('handleGetSubjectEditPermissionSetPeriods');
            // console.log(JSON.stringify(result));
            var promise;
            $scope.editPermissionForSelectedSubject = result;
            promise = InstitutionsResultsArchiveSvc.getPeriods(
                $scope.assessment_id,
                $scope.selectedAcademicTerm)
            return promise.then(function (result) {

                return result;
            });
        }

        function handleGetPeriodsSetCopyGradingTypes(result) {
            // console.log('handleGetPeriodsSetCopyGradingTypes');
            // console.log(JSON.stringify(result));
            var promise;
            $scope.periods = result;

            promise = InstitutionsResultsArchiveSvc.getCopyGradingTypes($scope.assessment_id,
                $scope.subject.education_subject_id);
            return promise.then(function (result) {
                return result;
            });
        }

        function handleGetGradingTypes(result) {
            // console.log('handleGetGradingTypes');
            // console.log(JSON.stringify(result));
            var promise;
            $scope.gradingTypes = result;

            promise = $scope.resetColumnDefs($scope.action, $scope.subject, $scope.periods, $scope.gradingTypes);
            if (typeof promise.then === 'function') {
                return promise.then(function (result) {
                    return result;
                });
            }

            if (typeof promise.then != 'function') {
                return promise;
            }
        }

    };

    $scope.onEditClick = function () {
        if ($scope.subject === undefined) {
            return;
        }
        var options = {
            subject_id: $scope.subject.education_subject_id,
            class_id: $scope.class_id,
            academic_period_id: $scope.academic_period_id,
            institution_id: $scope.institution_id,
            security_user_id: undefined,
            is_super_admin: undefined,
        };


        InstitutionsResultsArchiveSvc.getSubjectEditPermission(options)
            .then(function (hasPermission) {
                if (hasPermission) {
                    $scope.action = 'edit';
                    AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                } else {
                    $scope.action = 'view';
                    AlertSvc.warning($scope, 'You have no permission for this subject.');
                }
            }, function (error) {
                console.error(error);
            });
    };

    $scope.onBackClick = function () {
        $scope.action = 'view';
        $scope.onChangeSubject($scope.subject);
        AlertSvc.reset($scope);
    };
}
