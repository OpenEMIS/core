angular.module('institution.student.outcomes.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.outcomes.svc'])
    .controller('InstitutionStudentOutcomesCtrl', InstitutionStudentOutcomesController);

InstitutionStudentOutcomesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentOutcomesSvc'];

function InstitutionStudentOutcomesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentOutcomesSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;
    var suppressSorting = true;
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
    Controller.criterias = [];
    Controller.gradingOptions = [];
    Controller.studentResults = [];
    Controller.studentComments = '';
    // filters
    Controller.studentOptions = [];
    Controller.selectedStudent = null;
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

    angular.element(document).ready(function () {
        InstitutionStudentOutcomesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionStudentOutcomesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.institutionId = response.institution_id;

                return InstitutionStudentOutcomesSvc.getStudentStatusId("CURRENT");
            }, function(error) {
                console.log(error);
            })
            .then(function (response) {
                var enrolledStatusId = response[0].id;
                return InstitutionStudentOutcomesSvc.getClassStudents(Controller.classId, enrolledStatusId);
            }, function(error) {
                console.log(error);
            })
            .then(function (classStudents) {
                Controller.studentOptions = classStudents;
                if (Controller.studentOptions.length > 0) {
                    Controller.selectedStudent = Controller.studentOptions[0].student_id;
                } else {
                    AlertSvc.warning(Controller, "Please setup students for this class");
                }
                return InstitutionStudentOutcomesSvc.getOutcomeTemplate(Controller.academicPeriodId, Controller.outcomeTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (outcomeTemplate) {
                Controller.outcomeTemplateName = outcomeTemplate.code_name;
                Controller.criterias = outcomeTemplate.criterias;
                Controller.periodOptions = outcomeTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                } else {
                    AlertSvc.warning(Controller, "Please setup outcome periods for the selected template");
                }
                Controller.subjectOptions = outcomeTemplate.education_grade.education_subjects;
                if (Controller.subjectOptions.length > 0) {
                    Controller.selectedSubject = outcomeTemplate.education_grade.education_subjects[0].id;
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
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (outcomeResults) {
                Controller.formatResults(outcomeResults);
                return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                    Controller.selectedStudent, Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.selectedSubject, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
            })
            .then(function (outcomeComments) {
                if (outcomeComments.length > 0) {
                    Controller.studentComments = outcomeComments[0].comments;
                }
                return Controller.initGrid();
            }, function (error) {
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
            // Format for the student criteria result will be criteria_id.grading_option_id
            if (studentResults[value.outcome_criteria_id] == undefined) {
                studentResults[value.outcome_criteria_id] = {};
            }
            studentResults[value.outcome_criteria_id] = value.outcome_grading_option_id;
        });
        Controller.studentResults = studentResults;
    }

    function resetColumnDefs(criterias, gradingOptions, period, selectedPeriodStatus, item, student) {
        var response = InstitutionStudentOutcomesSvc.getColumnDefs(item, student, Controller.itemOptions, Controller.studentOptions);

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

                    if (item != null && student != null) {
                        // outcome criterias
                        var rowData = [];
                        angular.forEach(criterias, function (value, key) {
                            if (value.outcome_item_id == item) {
                                var criteriaName = value.name;
                                if (value.code != null && value.code.length > 0) {
                                    criteriaName = value.code + " - " + value.name;
                                }
                                var row = {
                                    student_id: student,
                                    period_editable: selectedPeriodStatus,
                                    outcome_period_id: period,
                                    outcome_item_id: item,
                                    outcome_criteria_name: criteriaName,
                                    outcome_criteria_id: value.id,
                                    grading_options: {},
                                    result: 0,
                                    save_error: {
                                        result: false
                                    }
                                };

                                if (angular.isDefined(gradingOptions[value.outcome_grading_type_id])) {
                                    row['grading_options'] = gradingOptions[value.outcome_grading_type_id];
                                }

                                if (angular.isDefined(Controller.studentResults[value.id])) {
                                    row['result'] = Controller.studentResults[value.id];
                                }
                                this.push(row);
                            }
                        }, rowData);
                        Controller.gridOptions.api.setRowData(rowData);

                        if (rowData.length > 0) {
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                        } else {
                            AlertSvc.warning(Controller, "Please setup outcome criterias for the selected item");
                            Controller.gridOptions.api.hideOverlay();
                            var emptyRow = [{
                                period_editable: false,
                                outcome_criteria_name: 'No Outcome Criterias',
                                result: '',
                                save_error: {
                                    result: false
                                }
                            }];
                            Controller.gridOptions.api.setRowData(emptyRow);
                        }

                        // item comments (pinned row at bottom)
                        var comments = '';
                        if (angular.isDefined(Controller.studentComments['comments'])) {
                            comments = Controller.studentComments['comments'];
                        }
                        var pinnedRowData = [{
                            student_id: student,
                            period_editable: selectedPeriodStatus,
                            outcome_period_id: period,
                            outcome_item_id: item,
                            outcome_criteria_name: commentTranslation,
                            result: comments,
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
                    Controller.itemOptions = value.outcome_items;
                    Controller.selectedItem = value.outcome_items[0].id;
                    Controller.selectedPeriodStatus = value.editable;
                }
            });
        }
        InstitutionStudentOutcomesSvc.getStudentOutcomeResults(
            Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentOutcomesSvc.getStudentOutcomeComments(
                Controller.outcomeTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (comments) {
            Controller.formatComments(comments);
            Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
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
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                suppressContextMenu: true,
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
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    outcome_template_id: Controller.outcomeTemplateId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                suppressContextMenu: true,
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
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent);
                }
            };
        });
    }
}