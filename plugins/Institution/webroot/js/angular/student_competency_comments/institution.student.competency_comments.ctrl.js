angular.module('institution.student.competency_comments.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institution.student.competency_comments.svc'])
    .controller('InstitutionStudentCompetencyCommentsCtrl', InstitutionStudentCompetencyCommentsController);

InstitutionStudentCompetencyCommentsController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionStudentCompetencyCommentsSvc'];

function InstitutionStudentCompetencyCommentsController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionStudentCompetencyCommentsSvc) {
    var Controller = this;

    // Variables
    Controller.dataReady = false;
    Controller.bodyDir = getComputedStyle(document.body).direction;
    Controller.gridOptions = {};
    Controller.classId = null;
    Controller.competencyTemplateId = null;
    Controller.className = '';
    Controller.institutionId = null;
    Controller.academicPeriodId = null;
    Controller.academicPeriodName = '';
    Controller.studentList = {};
    Controller.competencyTemplateName = '';
    Controller.competencyPeriods = {};
    Controller.studentComments = {};

    // Functions
    Controller.initGrid = initGrid;
    Controller.resetColumnDefs = resetColumnDefs;

    angular.element(document).ready(function () {
        InstitutionStudentCompetencyCommentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        if (Controller.classId != null) {
            InstitutionStudentCompetencyCommentsSvc.getClassDetails(Controller.classId)
            .then(function(response) {
                Controller.className = response.name;
                Controller.institutionId = response.institution_id;
                Controller.academicPeriodId = response.academic_period_id;
                Controller.academicPeriodName = response.academic_period.name;
                Controller.studentList = response.class_students;

                return InstitutionStudentCompetencyCommentsSvc.getCompetencyTemplate(Controller.academicPeriodId, Controller.competencyTemplateId);
            }, function(error) {
                console.log(error);
            })
            // getCompetencyTemplate
            .then(function (competencyTemplate) {
                Controller.competencyTemplateId = competencyTemplate.id;
                Controller.competencyTemplateName = competencyTemplate.code_name;
                Controller.competencyPeriods = competencyTemplate.periods;

                return InstitutionStudentCompetencyCommentsSvc.getCompetencyPeriodComments(
                    Controller.competencyTemplateId, Controller.institutionId, Controller.academicPeriodId);
            }, function (error) {
                console.log(error);
            })
            // getCompetencyPeriodComments
            .then(function (competencyPeriodComments) {
                Controller.studentComments = competencyPeriodComments;
                return Controller.initGrid();
            }, function (error) {
            })
            .finally(function(){
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
        }

    });

    function initGrid() {
        return AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institutionId,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId
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
                suppressCellSelection: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                localeText: localeText,
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                        var newVal = params.newValue;
                        var format = /[ `/'"=%]/;
                        if(format.test(newVal.charAt(0))) {
                            AlertSvc.warning(Controller, 'Special character not allow at first character of text');
                            return false
                        } else {
                            AlertSvc.info(Controller, 'Changes will be automatically saved when any value is changed');
                        } 

                        InstitutionStudentCompetencyCommentsSvc.saveCompetencyPeriodComments(params)
                        .then(function(response) {
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field],
                                force: true
                            });

                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error(Controller, "There was an error when saving the comments");
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field],
                                force: true
                            });
                        });
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.competencyPeriods, Controller.selectedPeriodStatus);
                }
            };
        }, function(error){
            Controller.gridOptions = {
                context: {
                    institution_id: Controller.institution_id,
                    academic_period_id: Controller.academicPeriodId,
                    competency_template_id: Controller.competencyTemplateId
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
                suppressCellSelection: true,
                suppressMovableColumns: true,
                singleClickEdit: true,
                localeText: localeText,
                suppressContextMenu: true,
                stopEditingWhenGridLosesFocus: true,
                ensureDomOrder: true,
                onCellValueChanged: function(params) {
                    if (params.newValue != params.oldValue || params.data.save_error[params.colDef.field]) {
                        InstitutionStudentCompetencyCommentsSvc.saveCompetencyPeriodComments(params)
                        .then(function(response) {
                            params.data.save_error[params.colDef.field] = false;
                            AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field],
                                force: true
                            });

                        }, function(error) {
                            params.data.save_error[params.colDef.field] = true;
                            console.log(error);
                            AlertSvc.error(Controller, "There was an error when saving the comments");
                            params.api.refreshCells({
                                rowNodes: [params.node],
                                columns: [params.colDef.field],
                                force: true
                            });
                        });
                    }
                },
                onGridReady: function() {
                    Controller.resetColumnDefs(Controller.competencyPeriods, Controller.selectedPeriodStatus);
                }
            };
        });
    }

    function resetColumnDefs(periods, selectedPeriodStatus) {
        var response = InstitutionStudentCompetencyCommentsSvc.getColumnDefs(periods, Controller.bodyDir);

        if (angular.isDefined(response.error)) {
            console.log(response.error);
            return false;
        } else {
            if (Controller.gridOptions != null) {
                var textToTranslate = [];
                angular.forEach(response.data, function(value, key) {
                    textToTranslate.push(value.headerName);
                });

                InstitutionStudentCompetencyCommentsSvc.translate(textToTranslate)
                .then(function(res){
                    angular.forEach(res, function(value, key) {
                        response.data[key]['headerName'] = value;
                    });
                    Controller.gridOptions.api.setColumnDefs(response.data);

                    if (response.data.length > 4) {
                        AlertSvc.info(Controller, "Changes will be automatically saved when any value is changed");
                    } else {
                        AlertSvc.warning(Controller, "Please setup competency periods for the selected template");
                    }

                    return InstitutionStudentCompetencyCommentsSvc.getRowData(Controller.studentList, Controller.competencyPeriods, Controller.studentComments);
                }, function(error){
                    console.log(error);
                })
                // getRowData
                .then(function(rowData) {
                    Controller.gridOptions.api.setRowData(rowData);
                }, function(error) {
                    // No Class Students
                    console.log(error);
                    AlertSvc.warning($scope, error);
                }).finally(function(){
                    Controller.gridOptions.api.sizeColumnsToFit();
                });
                return true;
            } else {
                return true;
            }
        }
    }
}