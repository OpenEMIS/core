angular
    .module('institution.student.competency_comments.svc', ['kd.data.svc'])
    .service('InstitutionStudentCompetencyCommentsSvc', InstitutionStudentCompetencyCommentsSvc);

InstitutionStudentCompetencyCommentsSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc'];

function InstitutionStudentCompetencyCommentsSvc($http, $q, $filter, KdDataSvc) {

    var service = {
        init: init,
        translate: translate,
        getClassDetails: getClassDetails,
        getCompetencyTemplate: getCompetencyTemplate,
        getCompetencyPeriodComments: getCompetencyPeriodComments,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData,
        renderText: renderText,
        saveCompetencyPeriodComments: saveCompetencyPeriodComments,
    };

    var models = {
        InstitutionClasses: 'Institution.InstitutionClasses',
        CompetencyTemplates: 'Competency.CompetencyTemplates',
        InstitutionCompetencyPeriodComments: 'Institution.InstitutionCompetencyPeriodComments'
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('StudentCompetencyComments');
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

    function getCompetencyTemplate(academicPeriodId, competencyTemplateId) {
        var primaryKey = KdDataSvc.urlsafeB64Encode(JSON.stringify({academic_period_id: academicPeriodId, id: competencyTemplateId}));
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return CompetencyTemplates
            .get(primaryKey)
            .contain(['Periods'])
            .ajax({success: success, defer:true});
    }

    function getCompetencyPeriodComments(templateId, institutionId, academicPeriodId) {
        var success = function(response, deferred) {
            deferred.resolve(response.data.data);
        };
        return InstitutionCompetencyPeriodComments
            .find('studentComments', {
                competency_template_id: templateId,
                institution_id: institutionId,
                academic_period_id: academicPeriodId
            })
            .ajax({success: success, defer:true});
    }

    function getColumnDefs(periods, direction) {
        if (direction == 'ltr') {
            direction = 'left';
        } else {
            direction = 'right';
        }
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams,
            pinned: direction
        });
        columnDefs.push({
            headerName: "Student Name",
            field: "name",
            sort: 'asc',
            filterParams: filterParams,
            pinned: direction
        });
        columnDefs.push({
            headerName: "student id",
            field: "student_id",
            hide: true,
            filterParams: filterParams,
            pinned: direction
        });
        columnDefs.push({
            headerName: "Student Status",
            field: "student_status_name",
            filterParams: filterParams,
            pinned: direction
        });

        var ResultsSvc = this;
        angular.forEach(periods, function(period, key) {
            var extra = {
                period: period
            };
            var columnDef = {
                headerName: period.name,
                field: 'period_' + period.id,
                filterParams: filterParams
            };

            columnDef = ResultsSvc.renderText(columnDef, extra);
            this.push(columnDef);
        }, columnDefs);

        return {data: columnDefs};
    }

    function renderText(cols, extra) {
        var periodEditable = extra.period.editable;

        cols = angular.merge(cols, {
            cellClassRules: {
                'oe-cell-highlight': function(params) {
                    var studentStatusCode = params.node.data.student_status_code;
                    return (studentStatusCode == 'CURRENT' && periodEditable);
                },
                'oe-cell-error': function(params) {
                    return params.data.save_error[params.colDef.field];
                }
            },
            editable: function(params) {
                // only enrolled student is editable
                var studentStatusCode = params.node.data.student_status_code;
                return (studentStatusCode == 'CURRENT' && periodEditable);
            }
        });
        return cols;
    };

    function getRowData(studentList, periods, studentComments) {
        var deferred = $q.defer();

        var commentResults = {};
        angular.forEach(studentComments, function(value, key) {
            if (angular.isUndefined(commentResults[value.student_id])) {
                commentResults[value.student_id] = {}
            }
            commentResults[value.student_id][value.competency_period_id] = value.comments;
        });

        var rowData = [];
        if (studentList.length > 0) {
            angular.forEach(studentList, function(student, key) {
                var row = {
                    openemis_id: student.user.openemis_no,
                    student_id: student.student_id,
                    name: student.user.name,
                    student_status_name: student.student_status.name,
                    student_status_code: student.student_status.code,
                    save_error: {}
                };

                angular.forEach(periods, function(period, key) {
                    var value = '';
                    if (angular.isDefined(commentResults[student.student_id]) && angular.isDefined(commentResults[student.student_id][period.id])) {
                        value = commentResults[student.student_id][period.id];
                    }
                    row['period_' + parseInt(period.id)] = value;
                    row['save_error']['period_' + parseInt(period.id)] = false;
                });

                this.push(row);
            }, rowData);
        }

        deferred.resolve(rowData);
        return deferred.promise;
    }

    function saveCompetencyPeriodComments(params) {
        var competencyTemplateId = params.context.competency_template_id;
        var institutionId = params.context.institution_id;
        var academicPeriodId = params.context.academic_period_id;
        var studentId = params.data.student_id;
        var periodFieldName = params.colDef.field;
        var competencyPeriodId = periodFieldName.replace('period_', '')
        var periodComments = params.data[periodFieldName];

        var saveObj = {
            comments: periodComments,
            student_id: studentId,
            competency_template_id: competencyTemplateId,
            competency_period_id: competencyPeriodId,
            institution_id: institutionId,
            academic_period_id: academicPeriodId
        };
        return InstitutionCompetencyPeriodComments.save(saveObj);
    }
};