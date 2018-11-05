angular
    .module('examinations.results.svc', ['kd.orm.svc'])
    .service('ExaminationsResultsSvc', ExaminationsResultsSvc);

ExaminationsResultsSvc.$inject = ['$filter', '$q', 'KdOrmSvc'];

function ExaminationsResultsSvc($filter, $q, KdOrmSvc) {
    const resultTypes = {MARKS: 'MARKS', GRADES: 'GRADES'};

    var models = {
        ExaminationCentresExaminationsSubjectsTable: 'Examination.ExaminationCentresExaminationsSubjects',
        ExaminationCentresExaminationsTable: 'Examination.ExaminationCentresExaminations',
        ExaminationCentreStudentsTable: 'Examination.ExamCentreStudents',
        ExaminationItemResultsTable: 'Examination.ExaminationItemResults',
    };

    var service = {
        init: init,
        translate: translate,
        getExaminationCentreExaminations: getExaminationCentreExaminations,
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        renderMarks: renderMarks,
        renderGrades: renderGrades,
        getRowData: getRowData,
        getGrading: getGrading,
        calculateTotal: calculateTotal,
        saveRowData: saveRowData,
        translate: translate
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('ExamResults');
        KdOrmSvc.init(models);
    };

    function translate(data) {
        KdOrmSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    };

    function getExaminationCentreExaminations(examinationCentreId, examinationId) {
        return ExaminationCentresExaminationsTable
            .select()
            .where({examination_centre_id: examinationCentreId, examination_id: examinationId})
            .contain(['AcademicPeriods', 'Examinations', 'ExaminationCentres'])
            .ajax({defer: true});
    };

    function getSubjects(examinationId, examinationCentreId) {
        var success = function(response, deferred) {
            var examinationSubjects = response.data.data;

            if (angular.isObject(examinationSubjects) && examinationSubjects.length > 0) {
                var subjects = [];
                angular.forEach(examinationSubjects, function(examinationSubject, key)
                {
                    if (examinationSubject.hasOwnProperty('examination_item')) {
                        if (examinationSubject.examination_item.weight > 0) {
                            this.push(examinationSubject.examination_item);
                        }
                    }
                }, subjects);

                if (subjects.length > 0) {
                    deferred.resolve(subjects);
                } else {
                    deferred.reject('You need to configure weight in Examination Items first');
                }
            } else {
                deferred.reject('You need to configure Examination Items first');
            }
        };

        return ExaminationCentresExaminationsSubjectsTable
            .select()
            .contain(['ExaminationItems.ExaminationGradingTypes.GradingOptions', 'EducationSubjects'])
            .where({examination_id: examinationId, examination_centre_id: examinationCentreId})
            .order(['EducationSubjects.order', 'ExaminationItems.code', 'ExaminationItems.name'])
            .ajax({success: success, defer: true});
    };

    function getColumnDefs(action, subject, _results) {
        var isMobile = document.querySelector("html").classList.contains("mobile") || navigator.userAgent.indexOf("Android") != -1 || navigator.userAgent.indexOf("iOS") != -1;
        var isRtl = document.querySelector("html").classList.contains("rtl");
        var direction = 'left';
        if (isMobile) {
            direction = '';
        } else if (isRtl) {
            direction = 'right';
        }
        var menuTabs = [ "filterMenuTab" ];
        var deferred = $q.defer();

        if (subject.examination_grading_type.grading_options.length == 0) {
            // No Grading Options
            var errorMsg = 'You need to configure Examination Grading Options first';
            deferred.reject(errorMsg);
        } else {
            var filterParams = {
                cellHeight: 30
            };

            var columnDefs = [];

            columnDefs.push({
                headerName: "Registration Number",
                field: "registration_no",
                filterParams: {
                    filterOptions: ['contains'],
                },
                filter: "text",
                menuTabs: menuTabs,
            });
            columnDefs.push({
                headerName: "OpenEMIS ID",
                field: "openemis_id",
                filterParams: {
                    filterOptions: ['contains'],
                },
                filter: "text",
                menuTabs: menuTabs,
            });
            columnDefs.push({
                headerName: "Name",
                field: "name",
                sort: 'asc',
                filterParams: {
                    filterOptions: ['contains'],
                },
                filter: "text",
                menuTabs: menuTabs,
            });
            columnDefs.push({
                headerName: "student id",
                field: "student_id",
                hide: true,
                filterParams: filterParams
            });
            columnDefs.push({
                headerName: "institution id",
                field: "institution_id",
                hide: true,
                filterParams: filterParams
            });

            var resultType = subject.examination_grading_type.result_type;
            var itemMax = subject.examination_grading_type.max;
            var itemWeight = subject.weight;
            var isMarksType = (resultType == resultTypes.MARKS) ? true : false;

            var allowEdit = action == 'edit';
            var headerLabel = "Mark <span class='divider'></span> " + itemMax;
            var headerName = allowEdit ? headerLabel + " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>" : headerLabel;

            var columnDef = {
                headerName: headerName,
                field: 'mark',
                filterParams: filterParams,
                filter: "number",
                menuTabs: [],
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

                columnDef = this.renderMarks(allowEdit, columnDef, extra, _results);
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

                columnDef = this.renderGrades(allowEdit, columnDef, extra, _results);
            }

            columnDefs.push(columnDef);

            columnDefs.push({
                headerName: "weight",
                field: 'weight',
                hide: true
            });

            var visibility = isMarksType ? false : true;
            columnDefs.push({
                headerName: "Total Mark",
                field: "total_mark",
                filter: "number",
                hide: visibility,
                valueGetter: function(params) {
                    if(params.data != undefined){
                        var value = params.data[params.colDef.field];
                    }

                    if (!isNaN(parseFloat(value))) {
                        return $filter('number')(value, 2);
                    } else {
                        return '';
                    }
                },
                filterParams: filterParams,
                menuTabs: [],
            });

            var bodyDir = getComputedStyle(document.body).direction;
            if (bodyDir == 'rtl') {
                columnDefs.reverse();
            }

            deferred.resolve(columnDefs);
        }

        return deferred.promise;
    };

    function renderMarks(allowEdit, cols, extra, _results) {
        var minMark = extra.minMark;
        var passMark = extra.passMark;
        var maxMark = extra.maxMark;

        cols = angular.merge(cols, {
            filter: 'number',
            menuTabs: [],
            cellStyle: function(params) {
                if (!isNaN(parseFloat(params.value)) && parseFloat(params.value) < passMark) {
                    return {color: '#CC5C5C'};
                } else {
                    return {color: '#333'};
                }
            },
            valueGetter: function(params) {
                if(params.data != undefined){
                    var value = params.data[params.colDef.field];
                }

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

    function renderGrades(allowEdit, cols, extra, _results) {
        var gradingOptions = extra.gradingOptions;

        if (allowEdit) {
            cols = angular.merge(cols, {
                cellClass: 'oe-cell-highlight',
                cellRenderer: function(params) {
                    if (angular.isDefined(params.value)) {
                        if (params.value.length == 0) {
                            params.value = 0;
                        }

                        var oldValue = params.value;
                        var studentId = params.data.student_id;
                        var institutionId = params.data.institution_id;

                        var eCell = document.createElement('div');
                        eCell.setAttribute("class", "oe-cell-editable oe-select-wrapper");

                        var eSelect = document.createElement("select");

                        var isAnswerValid = false;
                        angular.forEach(gradingOptions, function(obj, key) {
                            var eOption = document.createElement("option");
                            var labelText = obj.name;
                            if (obj.code.length > 0) {
                                labelText = obj.code + ' - ' + labelText;
                            }
                            eOption.setAttribute("value", key);
                            eOption.innerHTML = labelText;
                            eSelect.appendChild(eOption);
                            if (oldValue == obj.id) {
                                isAnswerValid = true;
                            }
                        });

                        // set selected value only when it is a valid option from gradingOptions list
                        if (isAnswerValid) {
                            eSelect.value = params.value;
                        }

                        eSelect.addEventListener('change', function () {
                            var newValue = eSelect.value;
                            params.data[params.colDef.field] = newValue;

                            if (angular.isUndefined(_results[studentId])) {
                                _results[studentId] = {};
                            }

                            if (angular.isUndefined(_results[studentId][institutionId])) {
                                _results[studentId][institutionId] = {gradingOptionId: ''};
                            }

                            _results[studentId][institutionId]['gradingOptionId'] = newValue;
                        });

                        eCell.appendChild(eSelect);

                        return eCell;
                    }
                },
                suppressMenu: true
            });
        } else {
            cols = angular.merge(cols, {
                menuTabs: [],
                cellRenderer: function(params) {
                    if (angular.isDefined(params.value)) {
                        var cellValue = '';
                        if (params.value.length != 0 && params.value != 0) {
                            // show option code and name only when it is a valid option from gradingOptions list
                            if (angular.isDefined(gradingOptions[params.value])) {
                                cellValue = gradingOptions[params.value]['name'];
                                if (gradingOptions[params.value]['code'].length > 0) {
                                    cellValue = gradingOptions[params.value]['code'] + ' - ' + cellValue;
                                }
                            }
                        }
                        // var cellValue = (params.value.length != 0 && params.value != 0) ? gradingOptions[params.value]['name'] : '';

                        var eCell = document.createElement('div');
                        var eLabel = document.createTextNode(cellValue);
                        eCell.appendChild(eLabel);

                        return eCell;
                    }
                },
                suppressMenu: true
            });
        }

        return cols;
    };

    function getRowData(academicPeriodId, examinationId, examinationCentreId, subject, limit, page, filterModel) {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var subjectStudents = response.data.data;

                var resultType = subject.examination_grading_type.result_type;
                var itemWeight = subject.weight;
                var isMarksType = (resultType == resultTypes.MARKS) ? true : false;

                if (angular.isObject(subjectStudents) && subjectStudents.length > 0) {
                    var studentId = null;
                    var currentStudentId = null;
                    var studentResults = {};
                    var rowData = [];

                    angular.forEach(subjectStudents, function(subjectStudent, key) {
                        currentStudentId = parseInt(subjectStudent.student_id);

                        if (studentId != currentStudentId) {
                            if (studentId != null) {
                                this.push(studentResults);
                            }

                            studentResults = {
                                registration_no: subjectStudent.registration_number,
                                openemis_id: subjectStudent._matchingData.Users.openemis_no,
                                name: subjectStudent._matchingData.Users.name,
                                student_id: currentStudentId,
                                institution_id: subjectStudent.institution_id,
                                mark: '',
                                weight: 0,
                                total_mark: subjectStudent.ExaminationCentresExaminationsSubjectsStudents.total_mark
                            };

                            studentId = currentStudentId;
                        }

                        if (isMarksType) {
                            studentResults['weight'] = itemWeight;
                            var marks = parseFloat(subjectStudent.ExaminationItemResults.marks);
                            if (!isNaN(marks)) {
                                studentResults['mark'] = marks;
                            }
                        } else {
                            if (subjectStudent.ExaminationItemResults.examination_grading_option_id != null) {
                                studentResults['mark'] = subjectStudent.ExaminationItemResults.examination_grading_option_id;
                            }
                        }
                    }, rowData);

                    if (studentResults.hasOwnProperty('student_id')) {
                        rowData.push(studentResults);
                    }

                    response.data.data = rowData;
                    deferred.resolve(response);
                } else {
                    deferred.resolve(response);
                }
            }
        };

        var finderOptions = {
            examination_id: examinationId,
            examination_centre_id: examinationCentreId,
            examination_item_id: subject.id,
        };

        for (var field in filterModel) {
            var filterKey = 'filter.' + field;
            finderOptions[filterKey] = filterModel[field]['filter'];
        }

        return ExaminationCentreStudentsTable
            .select()
            .find('Results', finderOptions)
            .limit(limit)
            .page(page)
            .ajax({success: success, defer: true});
    };

    function getGrading(subject, marks) {
        var gradingOptions = subject.examination_grading_type.grading_options;
        var gradingResults = {
            id: null,
            code: '',
            name: ''
        };

        angular.forEach(gradingOptions, function(gradingOption, key) {
            if (marks >= gradingOption.min && marks <= gradingOption.max) {
                this.id = gradingOption.id;
                this.code = gradingOption.code;
                this.name = gradingOption.name;
            }
        }, gradingResults);

        return gradingResults;
    };

    function calculateTotal(data) {
        var totalMark = '';
        if (!isNaN(parseFloat(data['mark'])) && !isNaN(parseFloat(data['weight']))) {
            totalMark = isNaN(parseFloat(totalMark)) ? 0 : totalMark;
            totalMark += data['mark'] * (data['weight']);
        }

        if (!isNaN(parseFloat(totalMark))) {
            return $filter('number')(totalMark, 2);
        } else {
            return '';
        }
    };

    function saveRowData(results, subject, academicPeriodId, examinationId, examinationCentreId, educationSubjectId, examinationItemId) {
        var promises = [];

        angular.forEach(results, function(result, studentId) {
            angular.forEach(result, function(obj, institutionId) {
                var resultType = subject.examination_grading_type.result_type;
                var isMarksType = (resultType == resultTypes.MARKS) ? true : false;

                var marks = null;
                var gradingOptionId = null;

                if (resultType == resultTypes.MARKS) {
                    if (!isNaN(parseFloat(obj.marks))) {
                        marks = $filter('number')(obj.marks, 2);
                        var gradingObj = this.getGrading(subject, marks);
                        gradingOptionId = gradingObj.id;
                    }
                } else {
                    if (obj.gradingOptionId != 0) {
                        gradingOptionId = obj.gradingOptionId;
                    }
                }

                var data = {
                    "marks" : marks,
                    "examination_grading_option_id" : gradingOptionId,
                    "academic_period_id" : academicPeriodId,
                    "examination_id" : examinationId,
                    "education_subject_id" : educationSubjectId,
                    "examination_item_id" : examinationItemId,
                    "examination_centre_id" : examinationCentreId,
                    "institution_id" : institutionId,
                    "student_id" : parseInt(studentId)
                };

                promises.push(ExaminationItemResultsTable.save(data));
            }, this);
        }, this);

        return $q.all(promises);
    };
}
