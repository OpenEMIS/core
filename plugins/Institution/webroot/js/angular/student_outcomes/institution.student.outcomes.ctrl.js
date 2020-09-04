angular.module('institution.student.outcomes.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.outcomes.svc'])
    .controller('InstitutionStudentOutcomesCtrl', InstitutionStudentOutcomesController);

InstitutionStudentOutcomesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentOutcomesSvc'];

function InstitutionStudentOutcomesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentOutcomesSvc) {

    var Controller = this;

    // Constants
    Controller.dataReady = false;

    // Variables
    Controller.classId = null;
    Controller.outcomeTemplateId = null;

    Controller.gridOptions = {};
    Controller.className = '';
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.institutionId = null;
    Controller.outcomeTemplateName = '';
    Controller.educationGradeId = null;
    Controller.gradingOptions = [];
    Controller.studentResults = [];
    Controller.studentComments = '';

    // Filters
    Controller.studentOptions = [];
    Controller.selectedStudent = null;
    Controller.selectedStudentStatusCode = null;
    Controller.selectedStudentStatus = null;
    Controller.periodOptions = [];
    Controller.selectedPeriod = null;
    Controller.selectedPeriodStatus = null;
    Controller.subjectOptions = [];
    Controller.selectedSubject = null;

    // Function mapping
    Controller.initGrid = initGrid;
    Controller.formatResults = formatResults;
    Controller.resetColumnDefs = resetColumnDefs;
    Controller.changeOutcomeOptions = changeOutcomeOptions;
    Controller.changeStudentOptions = changeStudentOptions;

    angular.element(document).ready(function () {
        InstitutionStudentOutcomesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        if (Controller.classId != null && Controller.outcomeTemplateId != null) {
            InstitutionStudentOutcomesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.institutionId = response.institution_id;
                return InstitutionStudentOutcomesSvc.getClassStudents(Controller.classId);
            }, function(error) {
                console.log(error);
            })
            .then(function (classStudents) {
                Controller.studentOptions = classStudents;
                if (Controller.studentOptions.length > 0) {
                    Controller.selectedStudent = Controller.studentOptions[0].student_id;
                    Controller.selectedStudentStatusCode = Controller.studentOptions[0].student_status.code;
                    Controller.selectedStudentStatus = Controller.studentOptions[0].student_status.name;
                } else {
                    AlertSvc.warning(Controller, "Please setup students for this class");
                }
                return InstitutionStudentOutcomesSvc.getOutcomeTemplate(Controller.academicPeriodId, Controller.outcomeTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (outcomeTemplate) {
                Controller.outcomeTemplateName = outcomeTemplate.code_name;
                Controller.educationGradeId = outcomeTemplate.education_grade_id;

                Controller.periodOptions = outcomeTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                } else {
                    AlertSvc.warning(Controller, "Please setup outcome periods for the selected template");
                }

                return InstitutionStudentOutcomesSvc.getSubjectOptions(Controller.classId, Controller.institutionId, Controller.academicPeriodId, Controller.educationGradeId);
            }, function (error) {
                console.log(error);
            })
            .then(function (subjectOptions) {
                if (subjectOptions.length > 0) {
                    var options = [];
                    for (var i = 0; i < subjectOptions.length; ++i) {
                        options.push(subjectOptions[i].education_subject);
                    }

                    Controller.subjectOptions = options;
                    Controller.selectedSubject = subjectOptions[0].education_subject.id;
                } else {
                    AlertSvc.warning(Controller, "Please setup subjects for the selected template");
                }

                return InstitutionStudentOutcomesSvc.getOutcomeGradingTypes();
            }, function (error) {
                console.log(error);
            })
            .then(function (gradingTypes) {
                angular.forEach(gradingTypes, function(value, key) {
                    Controller.gradingOptions[value.id] = value.grading_options;
                });
                return InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (outcomeResults) {
                Controller.formatResults(outcomeResults);
                return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (outcomeComments) {
                Controller.studentComments = outcomeComments.length > 0 ? outcomeComments[0].comments : '';
                return Controller.initGrid();
            }, function (error) {
                console.log(error);
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }
    });

    function formatResults(outcomeResults) {
        var studentResults = [];
        angular.forEach(outcomeResults, function (value, key) {
            if (studentResults[value.outcome_criteria_id] == undefined) {
                studentResults[value.outcome_criteria_id] = 0;
            }
            studentResults[value.outcome_criteria_id] = value.outcome_grading_option_id;
        });
        Controller.studentResults = studentResults;
    }

    function resetColumnDefs(gradingOptions, period, selectedPeriodStatus, subject, student, studentStatusCode) {
        var response = InstitutionStudentOutcomesSvc.getColumnDefs(period, subject, student, Controller.periodOptions, Controller.subjectOptions, Controller.studentOptions, Controller.studentResults);

        if (angular.isDefined(response.error)) {
            // No Grading Options
            AlertSvc.warning($scope, response.error);
            return false;
        } else {
            if (Controller.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function(value, key) {
                    textToTranslate.push(value.headerName);
                });
                textToTranslate.push('Comments'); // translate comments title in pinned row

                InstitutionStudentOutcomesSvc.translate(textToTranslate)
                .then(function(res){
                    var commentTranslation = res.pop();
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    Controller.gridOptions.api.setColumnDefs(response.data);

                    if (period != null && subject != null && student != null) {
                        var limit = 40;
                        var dataSource = {
                            pageSize: limit,
                            getRows: function (params) {
                                var page = parseInt(params.startRow / limit) + 1;

                                UtilsSvc.isAppendSpinner(true, 'institution-student-outcome-table');
                                var defaultRow = {
                                    student_id: student,
                                    student_status: studentStatusCode,
                                    outcome_period_id: period,
                                    period_editable: selectedPeriodStatus,
                                    education_subject_id: subject,
                                    outcome_criteria_id: 0,
                                    outcome_criteria_name: '',
                                    grading_options: {},
                                    result: 0,
                                    save_error: {
                                        result: false
                                    }
                                };

                                InstitutionStudentOutcomesSvc.getRowData(Controller.outcomeTemplateId, subject, defaultRow, gradingOptions, Controller.studentResults, limit, page)
                                .then(function(response) {
                                    // console.log('response data source', response.data);
                                    var lastRowIndex = response.data.total;

                                    if (lastRowIndex > 0) {
                                        var rows = response.data.data;
                                        AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");

                                        params.successCallback(rows, lastRowIndex);
                                    } else {
                                        // No Students
                                        var emptyRow = [{
                                            period_editable: false,
                                            outcome_criteria_name: 'No Outcome Criterias',
                                            result: '',
                                            save_error: {
                                                result: false
                                            }
                                        }];
                                        params.successCallback(emptyRow, 1);
                                        AlertSvc.warning(Controller, "Please setup outcome criterias for the selected subject");
                                    }
                                }, function(error) {
                                    console.log(error);
                                })
                                .finally(function() {
                                    UtilsSvc.isAppendSpinner(false, 'institution-student-outcome-table');
                                });
                            }
                        };

                        Controller.gridOptions.api.setDatasource(dataSource);

                        // subject comments (pinned row at bottom)
                        var pinnedRowData = [{
                            student_id: student,
                            student_status: studentStatusCode,
                            outcome_period_id: period,
                            period_editable: selectedPeriodStatus,
                            education_subject_id: subject,
                            outcome_criteria_name: commentTranslation,
                            result: Controller.studentComments,
                            save_error: {
                                result: false
                            }
                        }];
                        Controller.gridOptions.api.setPinnedBottomRowData(pinnedRowData);
                    }

                    Controller.gridOptions.api.sizeColumnsToFit();
                }, function(error){
                    console.log(error);
                });
                return true;
            } else {
                return true;
            }
        }
    }

    function changeOutcomeOptions(periodChange) {
        if (periodChange) {
            angular.forEach(Controller.periodOptions, function(value, key) {
                if (value.id == Controller.selectedPeriod) {
                    Controller.selectedPeriodStatus = value.editable;
                }
            });
        }

        InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
            Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (outcomeComments) {
            Controller.studentComments = outcomeComments.length > 0 ? outcomeComments[0].comments : '';
            Controller.resetColumnDefs(Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent, Controller.selectedStudentStatusCode);
        }, function (error) {
        });
    }

    function changeStudentOptions(studentChange) {
        if (studentChange) {
            angular.forEach(Controller.studentOptions, function(value, key) {
                if (value.student_id == Controller.selectedStudent) {
                    Controller.selectedStudentStatusCode = value.student_status.code;
                    Controller.selectedStudentStatus = value.student_status.name;
                }
            });
        }

        InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
            Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.educationGradeId, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (outcomeComments) {
            Controller.studentComments = outcomeComments.length > 0 ? outcomeComments[0].comments : '';
            Controller.resetColumnDefs(Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent, Controller.selectedStudentStatusCode);
        }, function (error) {
        });
    }

    function initGrid() {
        return AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    outcome_template_id: Controller.outcomeTemplateId,
                    education_grade_id: Controller.educationGradeId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 40,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: false,
                unSortIcon: true,
                enableFilter: false,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                rowModelType: 'infinite',

                // Added options
                suppressContextMenu: false,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                pagination: true,
                paginationPageSize: 40,
                rowBuffer:40,
                maxBlocksInCache: 1,
                cacheBlockSize: 40,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent, Controller.selectedStudentStatusCode);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    outcome_template_id: Controller.outcomeTemplateId,
                    education_grade_id: Controller.educationGradeId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 58,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: false,
                unSortIcon: true,
                enableFilter: false,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                suppressContextMenu: false,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                localeText: localeText,
                domLayout: 'autoHeight',
                onGridSizeChanged: function(e) {
                    this.api.sizeColumnsToFit();
                },
                getRowStyle: function(params) {
                    if (params.node.rowPinned) {
                        return {'font-weight': 'bold'}
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedSubject, Controller.selectedStudent, Controller.selectedStudentStatusCode);
                }
            };
        });
    }
}