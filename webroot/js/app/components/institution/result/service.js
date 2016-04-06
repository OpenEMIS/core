angular.module('institution.result.service', [])
.service('ResultSvc', function($q, $http, $location) {
    return {
        initValues: function(scope) {
            scope.class_id = parseInt($location.search()['class_id']);
            scope.assessment_id = parseInt($location.search()['assessment_id']);
        },

        getAssessment: function(scope) {
            var deferred = $q.defer();
            var url = scope.url('rest/Assessment-Assessments/' + scope.assessment_id + '.json');

            $http({
                method: 'GET',
                url: url,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(function successCallback(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var assessment = response.data.data[0];

                    scope.academic_period_id = assessment.academic_period_id;
                    scope.education_grade_id = assessment.education_grade_id;

                    deferred.resolve(assessment);                    
                }
            }, function errorCallback(error) {
                deferred.reject(error);
            }, function progressCallback(response) {

            });

            return deferred.promise;
        },

        getSubjects: function(scope) {
            var deferred = $q.defer();
            var url = scope.url('rest/Assessment-AssessmentItems.json?_contain=EducationSubjects&assessment_id=' + scope.assessment_id);

            $http({
                method: 'GET',
                url: url,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(function successCallback(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var items = response.data.data;

                    if (angular.isObject(items) && items.length > 0) {
                        var subjects = [];
                        angular.forEach(items, function(item, key) {
                            this.push(item.education_subject);
                        }, subjects);

                        deferred.resolve(subjects);
                    } else {
                        deferred.reject('You need to configure Assessment Items first');
                    }
                }
            }, function errorCallback(error) {
                deferred.reject(error);
            }, function progressCallback(response) {

            });

            return deferred.promise;
        },

        getColumnDefs: function(scope) {
            var deferred = $q.defer();
            var url = scope.url('rest/Assessment-AssessmentPeriods.json?assessment_id=' + scope.assessment_id);

            $http({
                method: 'GET',
                url: url,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(function successCallback(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var periods = response.data.data;

                    if (angular.isObject(periods) && periods.length > 0) {
                        scope.periods = periods;

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

                        var renderTotal = '';
                        angular.forEach(periods, function(period, key) {
                            var headerName = period.name + " <span class='divider'></span> " + period.weight;
                            var periodField = 'period_' + period.id;
                            if (renderTotal != '') {
                                renderTotal += ' + ';
                            }
                            renderTotal += 'data.' + periodField;

                            var columnDef = {
                                headerName: headerName,
                                field: periodField,
                                filter: 'number',
                                cellStyle: function(params) {
                                    if (parseInt(params.value) < 40) {
                                        return {color: '#CC5C5C'};
                                    } else {
                                        return {color: '#333'};
                                    }
                                }
                            };

                            if (scope.action == 'edit' && period.editable) {
                                columnDef.headerName += " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>";
                                columnDef.cellClass = 'ag-cell-highlight';
                                // columnDef.editable = true;
                                columnDef.cellRenderer = function(params) {
                                    var inputElement = document.createElement("input");

                                    inputElement.setAttribute('class', 'ag-cell-edit-input oe-cell-editable');
                                    inputElement.setAttribute('type', 'number');
                                    inputElement.setAttribute('ng-pattern', '/^[0-9]+(\.[0-9]{1,2})?$/');
                                    inputElement.setAttribute('step', '0.01');
                                    inputElement.setAttribute('ng-model', 'data.'+params.colDef.field);
                                    inputElement.setAttribute('oe-student', parseInt(params.data.student_id));
                                    inputElement.setAttribute('oe-period', period.id);
                                    inputElement.setAttribute('oe-original', params.value);

                                    return inputElement;
                                };
                            }

                            this.push(columnDef);
                        }, columnDefs);

                        columnDefs.push({
                            headerName: "Total",
                            field: "total",
                            filter: "number",
                            cellRenderer: function(params) {
                                return '{{' + renderTotal + '}}';
                            },
                            filterParams: filterParams
                        });

                        deferred.resolve(columnDefs);
                    } else {
                        deferred.reject('You need to configure Assessment Periods first');
                    }
                }
            }, function errorCallback(error) {
                deferred.reject(error);
            }, function progressCallback(response) {

            });

            return deferred.promise;
        },

        initGrid: function(scope) {
            var subjects = scope.subjects;
            var columnDefs = scope.columnDefs;
            var subject = subjects[0];

            scope.gridOptions = {
                context: {
                    institution_id: scope.institution_id,
                    class_id: scope.class_id,
                    assessment_id: scope.assessment_id,
                    academic_period_id: scope.academic_period_id,
                    education_grade_id: scope.education_grade_id,
                    education_subject_id: subject.id
                },
                columnDefs: columnDefs,
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                singleClickEdit: true,
                angularCompileRows: true,
                onCellValueChanged: function(params) {
                    scope.cellValueChanged(params);
                },
                onReady: function() {
                    scope.resizeColumns();
                    scope.reloadRowData(subject);
                }
            };
        },

        getRowData: function(scope) {
            var deferred = $q.defer();
            scope.education_subject_id = scope.subject.id;
            // update value in context
            scope.gridOptions.context.education_subject_id = scope.education_subject_id;

            // Always reset
            scope.gridOptions.api.setRowData([]);

            var urlStr = 'rest/Institution-InstitutionSubjectStudents.json';
            urlStr += '?_contain=Users';
            urlStr += '&_finder=Results[';
                urlStr += 'institution_id:' + scope.institution_id;
                urlStr += ';class_id:' + scope.class_id;
                urlStr += ';assessment_id:' + scope.assessment_id;
                urlStr += ';academic_period_id:' + scope.academic_period_id;
                urlStr += ';subject_id:' + scope.education_subject_id;
            urlStr += ']';
            urlStr += '&institution_class_id=' + scope.class_id;

            var url = scope.url(urlStr);

            $http({
                method: 'GET',
                url: url,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(function successCallback(response) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var subjectStudents = response.data.data;

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
                                    openemis_id: subjectStudent.openemis_no,
                                    name: subjectStudent.name,
                                    student_id: currentStudentId,
                                    total: 0
                                };
                                studentId = currentStudentId;
                            }
                            var marks = parseFloat(subjectStudent.marks);
                            if (!isNaN(marks)) {
            				    studentResults['period_' + parseInt(subjectStudent.assessment_period_id)] = marks;
                            }
                        }, rowData);

                        if (studentResults.hasOwnProperty('student_id')) {
                            rowData.push(studentResults);
                        }

                        deferred.resolve(rowData);
                    } else {
                        deferred.reject('No Students');
                    }
                }
            }, function errorCallback(error) {
                deferred.reject(error);
            }, function progressCallback(response) {

            });

            return deferred.promise;
        },

        cellValueChanged: function(params, scope) {
            var field = params.colDef.field;
            var assessmentPeriodId = field.replace('period_', '');

            var data = {
                "marks" : parseInt(params.newValue),
                "assessment_id" : params.context.assessment_id,
                "education_subject_id" : params.context.education_subject_id,
                "student_id" : params.data.student_id,
                "institution_id" : params.context.institution_id,
                "academic_period_id" : params.context.academic_period_id,
                "assessment_period_id" : parseInt(assessmentPeriodId)
            };

            this.setRowData(scope, data);
        },

        setRowData: function(data, scope) {
            var deferred = $q.defer();
            var url = scope.url('rest/Assessment-AssessmentItemResults.json');

            $http({
                method: 'POST',
                url: url,
                headers: {
                    'Content-Type': 'application/json'
                },
                data: data
            }).then(function successCallback(response) {
                deferred.resolve(response.data.data);
            }, function errorCallback(error) {
                deferred.reject(error);
            }, function progressCallback(response) {

            });

            return deferred.promise;
        },

        saveRowData: function(scope) {
            var httpPromises = [];
            var url = scope.url('rest/Assessment-AssessmentItemResults.json');

            angular.forEach(angular.element('.oe-cell-editable'), function(obj, key) {
                var paramsContext = angular.element(obj).scope().gridOptions.context;

                var newValue = parseFloat(obj.value);
                var oldValue = obj.attributes['oe-original'].value;

                var data = {
                    "marks" : newValue,
                    "assessment_id" : paramsContext.assessment_id,
                    "education_subject_id" : paramsContext.education_subject_id,
                    "student_id" : obj.attributes['oe-student'].value,
                    "institution_id" : paramsContext.institution_id,
                    "academic_period_id" : paramsContext.academic_period_id,
                    "assessment_period_id" : parseInt(obj.attributes['oe-period'].value)
                };

                httpPromises.push($http({
                    method: 'POST',
                    url: url,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    data: data
                }));
            });

            return $q.all(httpPromises);
        },

        switchAction: function(scope) {
            this.getColumnDefs(scope).then(function(columnDefs) {
                if (scope.gridOptions != null) {
                    scope.gridOptions.api.setColumnDefs(columnDefs);
                    scope.resizeColumns();
                }
            });
        },

        isAppendSpinner: function(_isAppend, _querySelector) {
            var spinnerId = _querySelector + '-spinner';
            var hasClass = angular.element(document.getElementById(spinnerId)).hasClass('spinner-wrapper');
            if(_isAppend){
                if(!hasClass){
                    var spinnerElement = angular.element('<div id="'+ spinnerId +'" ' + 'class="spinner-wrapper"><div class="spinner-text"><div class="spinner lt-ie9"></div></div></div>');
                    angular.element(document.getElementById(_querySelector)).prepend(spinnerElement);
                }        
            }else{
                angular.element(document.getElementById(spinnerId)).remove('.spinner-wrapper');
            }
        }
    }
});
