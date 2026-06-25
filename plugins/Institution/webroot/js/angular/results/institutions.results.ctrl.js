angular
    .module('institutions.results.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.results.svc'])
    .controller('InstitutionsResultsCtrl', InstitutionsResultsController);

InstitutionsResultsController.$inject = ['$q', '$scope', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsResultsSvc'];

function InstitutionsResultsController($q,
                                       $scope,
                                       $filter,
                                       UtilsSvc,
                                       AlertSvc,
                                       AggridLocaleSvc,
                                       InstitutionsResultsSvc) {


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
    $scope.subjectSearchText = ''; //POCOR-7785: Initialize subject search filter
    $scope.filteredSubjects = []; //POCOR-7785: Initialize filtered subjects array
    $scope.studentSearchText = ''; //POCOR-7785: Initialize student search filter
    $scope.allRowData = []; //POCOR-7785: Store original unfiltered student data

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
        InstitutionsResultsSvc.init(angular.baseUrl);

        appendLoader();
        // getAssessment
        InstitutionsResultsSvc.getAssessment($scope.assessment_id)
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
            promise = InstitutionsResultsSvc.getAssessmentTerms($scope.assessment_id);
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
            promise = InstitutionsResultsSvc.getAcademicPeriod();

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

            promise = InstitutionsResultsSvc.getDataSubjects($scope.roles,
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
            //POCOR-7785: Initialize filtered subjects with all subjects for search functionality
            $scope.filteredSubjects = angular.copy(result);

            promise = InstitutionsResultsSvc.getStudentStatusId("CURRENT");
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

        //POCOR-7785: Subject search functionality - filter tabs by subject name
        $scope.filterSubjects = function() {
            var searchText = $scope.subjectSearchText.toLowerCase().trim();

            if (searchText === '') {
                // If search is empty, show all subjects
                $scope.filteredSubjects = angular.copy($scope.subjects);
            } else {
                // Filter subjects based on name match
                $scope.filteredSubjects = $scope.subjects.filter(function(subject) {
                    return subject.name.toLowerCase().indexOf(searchText) !== -1;
                });
            }

            // If the currently selected subject is not in filtered list, select the first available
            if ($scope.filteredSubjects.length > 0) {
                var currentSubjectExists = $scope.filteredSubjects.some(function(subject) {
                    return $scope.subject && subject.id === $scope.subject.id;
                });

                if (!currentSubjectExists) {
                    var firstSubject = $scope.filteredSubjects[0];
                    $scope.onChangeSubject(firstSubject, firstSubject.is_editable);
                }
            } else {
                // No subjects match search, clear the grid
                if ($scope.gridOptions) {
                    $scope.gridOptions.api.setRowData([]);
                }
            }
        };

        $scope.clearSearch = function() {
            $scope.subjectSearchText = ''; //POCOR-7785: Clear subject search
            $scope.filterSubjects();
        };

        //POCOR-7785: Student search functionality - filter grid rows by student name
        $scope.filterStudents = function() {
            $scope.applyStudentFilter();
        };

        $scope.applyStudentFilter = function() {
            var searchText = $scope.studentSearchText.toLowerCase().trim(); //POCOR-7785

            if (!$scope.allRowData || $scope.allRowData.length === 0) {
                return;
            }

            var filteredData;

            if (searchText === '') {
                // If search is empty, show all students
                filteredData = angular.copy($scope.allRowData);
            } else {
                // Filter students based on name match
                filteredData = $scope.allRowData.filter(function(student) {
                    // Check student name (user_name field typically contains student name)
                    var studentName = (student.user_name || '').toLowerCase();
                    // Also check other name fields if available
                    var studentFirstName = (student.first_name || '').toLowerCase();
                    var studentLastName = (student.last_name || '').toLowerCase();
                    var combinedName = (student.name || '').toLowerCase();

                    return studentName.indexOf(searchText) !== -1 ||
                           studentFirstName.indexOf(searchText) !== -1 ||
                           studentLastName.indexOf(searchText) !== -1 ||
                           combinedName.indexOf(searchText) !== -1;
                });
            }

            //POCOR-7785: Update grid with filtered student data
            if ($scope.gridOptions) {
                $scope.gridOptions.api.setRowData(filteredData);
            }
        };

        //POCOR-7785: Watch for changes in subjects (e.g., when academic term changes) to reapply search
        $scope.$watch('subjects', function(newValue, oldValue) {
            if (newValue) {
                // Reapply current search filter to new subjects list
                $scope.filterSubjects();
            }
        }, true); // Deep watch for array changes

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
                institution_class_id: $scope.class_id,
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
            //POCOR-9608 -- Ignore if value didn't actually change 
            if (params.newValue === params.oldValue) {
                return;
            }

            //POCOR-9608 -- Ignore empty / undefined values
            //POCOR-9744 START -- Allow clearing values by entering empty value
            // if (
            //     params.newValue === null ||
            //     params.newValue === undefined ||
            //     params.newValue === ''
            // ) {
            //     return;
            // }
            //POCOR-9744 END

            // Existing logic unchanged
            var index = params.colDef.field.replace(/period_(\d+)/, '$1');

            if (angular.isUndefined($scope.results[params.data.student_id])) {
                $scope.results[params.data.student_id] = {};
            }

            if (angular.isUndefined($scope.results[params.data.student_id][index])) {
                $scope.results[params.data.student_id][index] = {marks: ''};
            }

            $scope.results[params.data.student_id][index]['marks'] = params.newValue;

            params.data.total_mark = InstitutionsResultsSvc.calculateTotal(params.data);
            params.data.is_dirty = true;

            var subject = $scope.subject;
            var gradingTypes = $scope.gradingTypes;
            var extra = {
                subject: subject,
                gradingTypes: gradingTypes
            };

            InstitutionsResultsSvc.saveSingleRecordData(params, extra)
                .then(function (response) {
                    params.data.save_error[params.colDef.field] = false;
                    AlertSvc.info($scope, 'Student result will be saved after the result has been entered.');
                    params.api.refreshCells({
                        rowNodes: [params.node],
                        columns: [params.colDef.field, 'total_mark'],
                        force: true
                    });
                }, function (error) {
                    params.data.save_error[params.colDef.field] = true;
                    console.error(error);
                    AlertSvc.error($scope, 'There was an error when saving the result1');
                    params.api.refreshCells({
                        rowNodes: [params.node],
                        columns: [params.colDef.field],
                        force: true
                    });
                });
        }

        AggridLocaleSvc.getTranslatedGridLocale()
            .then(handleSuccessGetTranslatedGridLocale, handleErrorGetTranslatedGridLocale);

        function handleErrorGetTranslatedGridLocale(error) {
            console.error('handleErrorGetTranslatedGridLocale');
            console.error(JSON.stringify(error));

        }

        function handleSuccessGetTranslatedGridLocale(localeText) {
            // console.log('handleSuccessGetTranslatedGridLocale');
            // console.log(JSON.stringify(localeText));
            $scope.gridOptions.localeText = localeText;
        }
    };

    $scope.resetColumnDefs = function (action, subject, periods, gradingTypes) {
        var response = InstitutionsResultsSvc.getColumnDefs(action, subject, periods, gradingTypes, $scope.results, $scope.enrolledStatus);
        // console.log(JSON.stringify(response));
        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if ($scope.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function (value, key) {
                    if (value.headerName == 'Total Mark' && $scope.dynamicTotalMarkHeader) { //POCOR-8146
                        value.headerName = $scope.dynamicTotalMarkHeader;
                    }
                    textToTranslate.push(value.headerName);
                });
                InstitutionsResultsSvc.translate(textToTranslate)
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

        //POCOR-7785: Clear student search state when changing subject
        $scope.allRowData = [];
        $scope.studentSearchText = '';

        // console.log('$scope.gridOptions == null' + $scope.gridOptions);

        appendLoader();
        // getPeriods
        InstitutionsResultsSvc.getPermissions()
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
            //POCOR-7785: Store original data for student filtering
            $scope.allRowData = angular.copy(result);
            //POCOR-7785: Apply any current student filter
            $scope.applyStudentFilter();
        }

        function handleResetColumnDefs(result) {
            // console.log('handleGetPermissions');
            // console.log(JSON.stringify(result));
            var promise;
//                return InstitutionsResultsSvc.getRowData($scope.gradingTypes, $scope.periods, $scope.institution_id, $scope.class_id, $scope.assessment_id, $scope.academic_period_id, $scope.subject.education_subject_id, $scope.education_grade_id);
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
            promise = InstitutionsResultsSvc.getNewRowData(
                options);
            return promise.then(function (result) {
                // console.log(result);
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
            promise = InstitutionsResultsSvc.getSubjectEditPermission(options);
            return promise.then(function (result) {
                return result;
            });
        }

        function handleGetSubjectEditPermissionSetPeriods(result) {
            // console.log('handleGetSubjectEditPermissionSetPeriods');
            // console.log(JSON.stringify(result));
             var promise;
            $scope.editPermissionForSelectedSubject = result;
            promise = InstitutionsResultsSvc.getPeriods(
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

            promise = InstitutionsResultsSvc.getCopyGradingTypes($scope.assessment_id,
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


        InstitutionsResultsSvc.getSubjectEditPermission(options)
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
