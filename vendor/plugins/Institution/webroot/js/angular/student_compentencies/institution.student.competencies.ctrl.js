angular.module('institution.student.competencies.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.competencies.svc'])
    .controller('InstitutionStudentCompetenciesCtrl', InstitutionStudentCompetenciesController);

InstitutionStudentCompetenciesController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentCompetenciesSvc'];

function InstitutionStudentCompetenciesController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentCompetenciesSvc) {

    var Controller = this;

    // Constants
    var suppressMenu = true;
    var suppressSorting = true;
    Controller.dataReady = false;

    // Variables
    Controller.gridOptions = {};
    Controller.classId = null;
    Controller.className = '';
    Controller.competencyTemplateId = null;
    Controller.competencyTemplateName = '';
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.institutionId = null;
    Controller.criterias = [];
    Controller.gradingOptions = [];
    Controller.studentResults = {};
    Controller.studentComments = {};
    // filters
    Controller.periodOptions = [];
    Controller.selectedPeriod = null;
    Controller.selectedPeriodStatus = null;
    Controller.itemOptions = [];
    Controller.selectedItem = null;
    Controller.studentOptions = [];
    Controller.selectedStudent = null;
    Controller.selectedStudentStatusCode = null;
    Controller.selectedStudentStatus = null;

    // Function mapping
    Controller.initGrid = initGrid;
    Controller.formatResults = formatResults;
    Controller.formatComments = formatComments;
    Controller.resetColumnDefs = resetColumnDefs;
    Controller.changeCompetencyOptions = changeCompetencyOptions;
    Controller.changeStudentOptions = changeStudentOptions;

    angular.element(document).ready(function () {
        InstitutionStudentCompetenciesSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);
        if (Controller.classId != null) {
            InstitutionStudentCompetenciesSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.institutionId = response.institution_id;
                return InstitutionStudentCompetenciesSvc.getClassStudents(Controller.classId);
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
                return InstitutionStudentCompetenciesSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            .then(function (competencyTemplate) {
                Controller.competencyTemplateId = competencyTemplate.id;
                Controller.competencyTemplateName = competencyTemplate.code_name;
                Controller.criterias = competencyTemplate.criterias;
                Controller.periodOptions = competencyTemplate.periods;
                if (Controller.periodOptions.length > 0) {
                    Controller.selectedPeriod = Controller.periodOptions[0].id;
                    Controller.selectedPeriodStatus = Controller.periodOptions[0].editable;
                    Controller.itemOptions = Controller.periodOptions[0].competency_items;
                    if (Controller.itemOptions.length > 0) {
                        Controller.selectedItem = Controller.periodOptions[0].competency_items[0].id;
                    } else {
                        AlertSvc.warning(Controller, "Please setup competency items for the selected period");
                    }
                } else {
                    AlertSvc.warning(Controller, "Please setup competency periods for the selected template");
                }

                return InstitutionStudentCompetenciesSvc.getCompetencyGradingTypes();
            }, function (error) {
                console.log(error);
            })
            .then(function (gradingTypes) {
                angular.forEach(gradingTypes, function(value, key) {
                    Controller.gradingOptions[value.id] = value.grading_options;
                });

                return InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            .then(function (competencyResults) {
                Controller.formatResults(competencyResults);
                return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                    Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
            })
            .then(function (competencyItemComments) {
                Controller.formatComments(competencyItemComments);
                return Controller.initGrid();
            }, function (error) {
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

    });

    function resetColumnDefs(criterias, gradingOptions, period, selectedPeriodStatus, item, student, studentStatusCode) {

        var response = InstitutionStudentCompetenciesSvc.getColumnDefs(item, student, Controller.itemOptions, Controller.studentOptions);

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
                textToTranslate.push('Overall Comment'); // translate comments title in pinned row

                InstitutionStudentCompetenciesSvc.translate(textToTranslate)
                .then(function(res){
                    var commentTranslation = res.pop();
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    Controller.gridOptions.api.setColumnDefs(response.data);

                    if (item != null && student != null) {
                        // competency criterias
                        var rowData = [];
                        angular.forEach(criterias, function (value, key) {
                            if (value.competency_item_id == item) {
                                var criteriaName = value.name;
                                if (value.code != null && value.code.length > 0) {
                                    criteriaName = value.code + " - " + value.name;
                                }
                                var row = {
                                    student_id: student,
                                    student_status: studentStatusCode,
                                    period_editable: selectedPeriodStatus,
                                    competency_period_id: period,
                                    competency_item_id: item,
                                    competency_criteria_name: criteriaName,
                                    competency_criteria_id: value.id,
                                    grading_options: {},
                                    result: '',
                                    save_error: {
                                        result: false,
                                        comments: false
                                    }
                                };

                                if (angular.isDefined(gradingOptions[value.competency_grading_type_id])) {
                                    row['grading_options'] = gradingOptions[value.competency_grading_type_id];
                                }

                                if (angular.isDefined(Controller.studentResults[value.id]) &&
                                    angular.isDefined(Controller.studentResults[value.id]['grading_option'])) {
                                    row['result'] = Controller.studentResults[value.id]['grading_option'];
                                }

                                if (angular.isDefined(Controller.studentResults[value.id]) &&
                                    angular.isDefined(Controller.studentResults[value.id]['comments'])) {
                                    row['comments'] = Controller.studentResults[value.id]['comments'];
                                }
                                this.push(row);
                            }
                        }, rowData);
                        Controller.gridOptions.api.setRowData(rowData);

                        if (rowData.length > 0) {
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                        } else {
                            AlertSvc.warning(Controller, "Please setup competency criterias for the selected item");
                            Controller.gridOptions.api.hideOverlay();
                            var emptyRow = [{
                                period_editable: false,
                                competency_criteria_name: 'No Competency Criterias',
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
                            student_status: studentStatusCode,
                            period_editable: selectedPeriodStatus,
                            competency_period_id: period,
                            competency_item_id: item,
                            result: commentTranslation + ':',
                            comments: comments,
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

    function formatResults(competencyResults) {
        var studentResults = {};
        angular.forEach(competencyResults, function (value, key) {
            // Format for the student criteria result will be criteria_id.grading_option_id
            if (angular.isUndefined(studentResults[value.competency_criteria_id])) {
                studentResults[value.competency_criteria_id] = {};
            }

            if (angular.isDefined(value.competency_grading_option_id) && value.competency_grading_option_id != null) {
                studentResults[value.competency_criteria_id]['grading_option'] = value.competency_grading_option_id;
            }

            if (angular.isDefined(value.comments) && value.comments != null) {
                studentResults[value.competency_criteria_id]['comments'] = value.comments;
            }
        });
        Controller.studentResults = studentResults;
    }

    function formatComments(competencyItemComments) {
        var studentComments = {};
        angular.forEach(competencyItemComments, function (value, key) {
            studentComments['comments'] = value.comments;
        });
        Controller.studentComments = studentComments;
    }

    function changeCompetencyOptions(periodChange) {
        if (periodChange) {
            angular.forEach(Controller.periodOptions, function(value, key) {
                if (value.id == Controller.selectedPeriod) {
                    Controller.itemOptions = value.competency_items;
                    Controller.selectedItem = value.competency_items[0].id;
                    Controller.selectedPeriodStatus = value.editable;
                }
            });
        }
        InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
            Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (comments) {
            Controller.formatComments(comments);
            Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent, Controller.selectedStudentStatusCode);
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
        InstitutionStudentCompetenciesSvc.getStudentCompetencyResults(
            Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId)
        .then(function (results) {
            Controller.formatResults(results);
            return InstitutionStudentCompetenciesSvc.getStudentCompetencyComments(
                Controller.competencyTemplateId, Controller.selectedPeriod, Controller.selectedItem, Controller.selectedStudent, Controller.institutionId, Controller.academicPeriodId);
        }, function (error) {
        })
        .then(function (comments) {
            Controller.formatComments(comments);
            Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent, Controller.selectedStudentStatusCode);
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
                    competency_template_id: Controller.competencyTemplateId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 58,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
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
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent, Controller.selectedStudentStatusCode);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId,
                    _controller: Controller
                },
                columnDefs: [],
                rowData: [],
                headerHeight: 38,
                rowHeight: 58,
                minColWidth: 100,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                // Removed options - Issues in ag-Grid AG-828
                // suppressCellSelection: true,

                // Added options
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
                    Controller.resetColumnDefs(Controller.criterias, Controller.gradingOptions, Controller.selectedPeriod, Controller.selectedPeriodStatus, Controller.selectedItem, Controller.selectedStudent, Controller.selectedStudentStatusCode);
                }
            };
        });
    }
}