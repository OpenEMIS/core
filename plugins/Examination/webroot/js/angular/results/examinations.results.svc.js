angular
    .module('examinations.results.svc', ['kd.orm.svc'])
    .service('ExaminationsResultsSvc', ExaminationsResultsSvc);

ExaminationsResultsSvc.$inject = ['$q', 'KdOrmSvc'];

function ExaminationsResultsSvc($q, KdOrmSvc) {
    const resultTypes = {MARKS: 'MARKS', GRADES: 'GRADES'};

    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        ExaminationsTable: 'Examination.Examinations',
        ExaminationItemsTable: 'Examination.ExaminationItems',
        ExaminationCentresTable: 'Examination.ExaminationCentres'
    };    

    var service = {
        init: init,
        getAcademicPeriods: getAcademicPeriods,
        getExaminations: getExaminations,
        getExaminationCentres: getExaminationCentres,
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        renderMarks: renderMarks,
        renderGrades: renderGrades
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function getAcademicPeriods() {
        return AcademicPeriodsTable
            .select()
            .find('years')
            .find('visible')
            .find('editable', {isEditable: true})
            .ajax({defer: true});
    };

    function getExaminations(academicPeriodId) {
        return ExaminationsTable
            .select()
            .where({academic_period_id: academicPeriodId})
            .ajax({defer: true});
    };

    function getExaminationCentres(academicPeriodId, examinationId) {
        return ExaminationCentresTable
            .select()
            .where({
                academic_period_id: academicPeriodId,
                examination_id: examinationId
            })
            .ajax({defer: true});
    };

    function getSubjects(examinationId) {
        var success = function(response, deferred) {
            var examinationSubjects = response.data.data;

            if (angular.isObject(examinationSubjects) && examinationSubjects.length > 0) {
                var subjects = [];
                angular.forEach(examinationSubjects, function(examinationSubject, key) 
                {
                    educationSubject = examinationSubject.education_subject;
                    educationSubject.examination_grading_type = examinationSubject.examination_grading_type;
                    educationSubject.weight = examinationSubject.weight;

                    this.push(educationSubject);
                }, subjects);

                deferred.resolve(subjects);
            } else {
                deferred.reject('You need to configure Examination Items first');
            }   
        };

        return ExaminationItemsTable
            .select()
            .contain(['EducationSubjects', 'ExaminationGradingTypes.GradingOptions'])
            .where({examination_id: examinationId})
            .ajax({success: success, defer: true});
    };

function getColumnDefs(action, subject) {
        var deferred = $q.defer();

        if (subject.examination_grading_type.grading_options.length == 0) {
            // No Grading Options
            // return {error: 'You need to configure Examination Grading Options first'};
            var errorMsg = 'You need to configure Examination Grading Options first';
            deferred.reject(errorMsg);
        } else {
            var filterParams = {
                cellHeight: 30
            };

            var columnDefs = [];

            columnDefs.push({
                headerName: "OpenEMIS ID",
                field: "openemis_id",
                filterParams: filterParams
            });
            columnDefs.push({
                headerName: "Name",
                field: "name",
                sort: 'asc',
                filterParams: filterParams
            });
            columnDefs.push({
                headerName: "student id",
                field: "student_id",
                hide: true,
                filterParams: filterParams
            });

            var resultType = subject.examination_grading_type.result_type;
            var itemWeight = subject.weight;
            var isMarksType = (resultType == resultTypes.MARKS) ? true : false;

            var allowEdit = action == 'edit';
            var headerLabel = "Mark <span class='divider'></span> " + itemWeight;
            var headerName = allowEdit ? headerLabel + " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : headerLabel;

            var columnDef = {
                headerName: headerName,
                field: 'mark',
                filterParams: filterParams
            };

            var extra = {};
            if (isMarksType) {
                if (subject.examination_grading_type != null) {
                    extra = {
                        minMark: 0,
                        passMark: subject.examination_grading_type.pass_mark,
                        maxMark: subject.examination_grading_type.max
                    };
                }

                columnDef = this.renderMarks(allowEdit, columnDef, extra);
            } else {
                if (subject.examination_grading_type != null) {
                    var gradingOptions = {
                        0 : {
                            id: 0,
                            code: '',
                            name: '-- Select --'
                        }
                    };

                    angular.forEach(subject.examination_grading_type.grading_options, function(obj, key) {
                        gradingOptions[obj.id] = obj;
                    });

                    extra = {
                        gradingOptions: gradingOptions
                    };
                }

                columnDef = this.renderGrades(allowEdit, columnDef, extra);
            }

            columnDefs.push(columnDef);

            columnDefs.push({
                headerName: "weight",
                field: 'weight',
                hide: true
            });

            columnDefs.push({
                headerName: "Total Mark",
                field: "total_mark",
                filter: "number",
                valueGetter: function(params) {
                    var value = params.data[params.colDef.field];

                    if (!isNaN(parseFloat(value))) {
                        return $filter('number')(value, 2);
                    } else {
                        return '';
                    }
                },
                filterParams: filterParams
            });

            deferred.resolve(columnDefs);
        }

        return deferred.promise;
    };

    function renderMarks(allowEdit, cols, extra) {
        var minMark = extra.minMark;
        var passMark = extra.passMark;
        var maxMark = extra.maxMark;

        cols = angular.merge(cols, {
            filter: 'number',
            cellStyle: function(params) {
                if (!isNaN(parseFloat(params.value)) && parseFloat(params.value) < passMark) {
                    return {color: '#CC5C5C'};
                } else {
                    return {color: '#333'};
                }
            },
            valueGetter: function(params) {
                var value = params.data[params.colDef.field];

                if (!isNaN(parseFloat(value))) {
                    return $filter('number')(value, 2);
                } else {
                    return '';
                }
            }
        });

        if (allowEdit) {
            cols = angular.merge(cols, {
                editable: true,
                cellClass: 'oe-cell-highlight',
                newValueHandler: function(params) {
                    var valueAsFloat = parseFloat(params.newValue);

                    if (params.newValue.length > 0 && (isNaN(valueAsFloat) || (valueAsFloat < minMark || valueAsFloat > maxMark))) {
                        params.data[params.colDef.field] = '';
                    } else {
                        params.data[params.colDef.field] = params.newValue;
                    }
                }
            });
        }

        return cols;
    };

    function renderGrades(allowEdit, cols, extra) {
        var gradingOptions = extra.gradingOptions;
        // var period = extra.period;

        if (allowEdit) {
            cols = angular.merge(cols, {
                cellClass: 'oe-cell-highlight',
                cellRenderer: function(params) {
                    if (params.value.length == 0) {
                        params.value = 0;
                    }

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
                    });

                    eCell.appendChild(eSelect);

                    return eCell;
                },
                suppressMenu: true
            });
        } else {
            cols = angular.merge(cols, {
                cellRenderer: function(params) {
                    var cellValue = '';
                    if (params.value.length != 0 && params.value != 0) {
                        cellValue = gradingOptions[params.value]['name'];
                        if (gradingOptions[params.value]['code'].length > 0) {
                            cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
                        }
                    }
                    // var cellValue = (params.value.length != 0 && params.value != 0) ? gradingOptions[params.value]['name'] : '';

                    var eCell = document.createElement('div');
                    var eLabel = document.createTextNode(cellValue);
                    eCell.appendChild(eLabel);

                    return eCell;
                },
                suppressMenu: true
            });
        }

        return cols;
    };
}
