angular
    .module('institution.student.competencies.svc', ['kd.data.svc'])
    .service('InstitutionStudentCompetenciesSvc', InstitutionStudentCompetenciesSvc);

InstitutionStudentCompetenciesSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionStudentCompetenciesSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        getClassDetails: getClassDetails,
        getCompetencyTemplate: getCompetencyTemplate,
        translate: translate,
        saveCompetencyResults: saveCompetencyResults,
        saveCompetencyComments: saveCompetencyComments,
        getStudentCompetencyResults: getStudentCompetencyResults,
        getStudentCompetencyComments: getStudentCompetencyComments,
        getColumnDefs: getColumnDefs,
        renderInput: renderInput,
        renderText: renderText
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        CompetencyTemplates: 'Competency.CompetencyTemplates',
        InstitutionCompetencyResults: 'Institution.InstitutionCompetencyResults',
        CompetencyItemComments: 'Institution.InstitutionCompetencyItemComments'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentCompetencies');
        KdDataSvc.init(models);
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getClassDetails(classId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionClasses
            .get(classId)
            .find('translateItem')
            .contain(['AcademicPeriods', 'ClassStudents.Users', 'ClassStudents.StudentStatuses'])
            .ajax({success: success, defer:true});
    }

    function getStudentCompetencyResults(templateId, periodId, itemId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionCompetencyResults
            .find('studentResults', {
                competency_template_id: templateId,
                competency_period_id: periodId,
                competency_item_id: itemId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getStudentCompetencyComments(templateId, periodId, itemId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyItemComments
            .find('studentComments', {
                competency_template_id: templateId,
                competency_period_id: periodId,
                competency_item_id: itemId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getCompetencyTemplate(academicPeriodId, competencyTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: competencyTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyTemplates
            .get(primaryKey)
            .contain(['Periods.CompetencyItems', 'Criterias.GradingTypes.GradingOptions', 'InstitutionCompetencyResults', 'Items'])
            .ajax({success: success, defer:true});
    }

    function getColumnDefs(criterias, item, direction) {
        if (direction == 'ltr') {
            direction = 'left';
        } else {
            direction = 'right';
        }
        var menuTabs = [ "filterMenuTab" ];
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        });
        columnDefs.push({
            headerName: "Student Name",
            field: "name",
            sort: 'asc',
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        });
        columnDefs.push({
            headerName: "student id",
            field: "student_id",
            hide: true,
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
        });
        columnDefs.push({
            headerName: "Student Status",
            field: "student_status_name",
            filterParams: filterParams,
            pinned: direction,
            menuTabs: menuTabs,
            filter: 'text'
        });

        var ResultsSvc = this;

        // comments column
        if (angular.isDefined(item)) {
            var extra = {};
            var columnDef = {
                headerName: "Comments",
                field: "comments",
                filterParams: filterParams,
                pinned: direction,
                filter: 'text',
                menuTabs: menuTabs
            };
            columnDef = ResultsSvc.renderText(columnDef, extra);
            columnDefs.push(columnDef);
        }

        angular.forEach(criterias, function(criteria, key) {
            if (criteria.competency_item_id == item) {
                var isMarksType = true; // default is MARKS type
                var isGradesType = false;
                if (criteria.grading_type.grading_options.length == 0) {
                    // return error if No Grading Options
                    return {error: 'You need to configure Grading Options first'};
                }
                var headerLabel = criteria.name;
                if (criteria.code != null && criteria.code.length > 0) {
                    headerLabel = criteria.code + " <span class='divider'></span> " + criteria.name;
                }

                var field = 'competency_criteria_id_' + criteria.id;
                var columnDef = {
                    headerName: headerLabel,
                    field: field,
                    filterParams: filterParams,
                    filter: 'text',
                    menuTabs: menuTabs
                };

                var extra = {};
                var gradingOptions = {
                    0 : {
                        id: 0,
                        code: '',
                        name: '-- Select --'
                    }
                };

                angular.forEach(criteria.grading_type.grading_options, function(obj, key) {
                    gradingOptions[obj.id] = obj;
                });

                extra = {
                    gradingOptions: gradingOptions,
                    criteria: criteria
                };
                columnDef = ResultsSvc.renderInput(columnDef, extra);
                this.push(columnDef);
            }
        }, columnDefs);

        return {data: columnDefs};
    }

    function renderInput(cols, extra) {
        var gradingOptions = extra.gradingOptions;
        var vm = this;

        cols = angular.merge(cols, {
            cellRenderer: function(params) {
                var studentStatusCode = params.data.student_status_code;
                var periodEditable = params.data.period_editable;

                if (studentStatusCode == 'CURRENT' && periodEditable) {

                    var oldValue = params.value;
                    var studentId = params.data.student_id;
                    // var periodId = period.id;

                    var eCell = document.createElement('div');
                    eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

                    var eSelect = document.createElement("select");

                    angular.forEach(gradingOptions, function(obj, key) {
                        var eOption = document.createElement("option");
                        var labelText = obj.name;
                        if (obj.code.length > 0) {
                            labelText = obj.code + ' - ' + labelText;
                        }
                        eOption.setAttribute("value", key);
                        eOption.innerHTML = labelText;
                        eSelect.appendChild(eOption);
                    });
                    eSelect.value = params.value;

                    eSelect.addEventListener('change', function () {
                        var newValue = eSelect.value;
                        params.data[params.colDef.field] = newValue;
                        vm.saveCompetencyResults(params)
                        .then(function(response) {
                        }, function(error) {
                            console.log(error);
                        });
                    });

                    eCell.appendChild(eSelect);

                } else {
                    // don't allow input if student is not enrolled
                    var cellValue = '';
                    if (angular.isDefined(params.value) && params.value.length != 0 && params.value != 0) {
                        cellValue = gradingOptions[params.value]['name'];
                        if (gradingOptions[params.value]['code'].length > 0) {
                            cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
                        }
                    }

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);
                }

                return eCell;
            },
            suppressMenu: true
        });

        return cols;
    }

    function renderText(cols, extra) {
        cols = angular.merge(cols, {
            cellClass: function(params) {
                var studentStatusCode = params.node.data.student_status_code;
                var periodEditable = params.node.data.period_editable;
                var highlightClass = 'oe-cell-highlight';
                return (studentStatusCode == 'CURRENT' && periodEditable) ? highlightClass : false;
            },
            editable: function(params) {
                // only enrolled student is editable
                var studentStatusCode = params.node.data.student_status_code;
                var periodEditable = params.node.data.period_editable;
                return (studentStatusCode == 'CURRENT' && periodEditable);
            }
        });
        return cols;
    };

    function saveCompetencyResults(params) {
        var field = params.colDef.field;
        var competencyTemplateId = params.context.competency_template_id;
        var competencyItemId = params.data.competency_item_id;
        var competencyCriteriaId = field.replace('competency_criteria_id_', '');
        var competencyPeriodId = params.data.competency_period_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;
        var competencyGradingOptionId = params.data[field];
        var studentId = params.data.student_id;

        var saveObj = {
            competency_grading_option_id: parseInt(competencyGradingOptionId),
            student_id: studentId,
            competency_template_id: competencyTemplateId,
            competency_item_id: competencyItemId,
            competency_criteria_id: parseInt(competencyCriteriaId),
            competency_period_id: competencyPeriodId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return InstitutionCompetencyResults.save(saveObj);
    }

    function saveCompetencyComments(params) {
        var competencyTemplateId = params.context.competency_template_id;
        var competencyItemId = params.data.competency_item_id;
        var competencyPeriodId = params.data.competency_period_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;
        var itemComments = params.data.comments;
        var studentId = params.data.student_id;

        var saveObj = {
            comments: itemComments,
            student_id: studentId,
            competency_template_id: competencyTemplateId,
            competency_item_id: competencyItemId,
            competency_period_id: competencyPeriodId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return CompetencyItemComments.save(saveObj);
    }
};