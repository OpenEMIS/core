angular.module('institution.result.service', [])
.factory('ResultService', function($q, $http, $location) {
    function initValues(_scope) {
        _scope.class_id = parseInt($location.search()['class_id']);
        _scope.assessment_id = parseInt($location.search()['assessment_id']);
    }

    function getAssessment(_scope) {
        var deferred = $q.defer();
        var _url = _scope.url('rest/Assessment-Assessments/' + _scope.assessment_id + '.json');

        $http({
            method: 'GET',
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var _assessment = _response.data.data[0];

            _scope.academic_period_id = _assessment.academic_period_id;
            _scope.education_grade_id = _assessment.education_grade_id;

            deferred.resolve(_assessment);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function getSubjects(_scope) {
        var deferred = $q.defer();
        var _url = _scope.url('rest/Assessment-AssessmentItems.json?_contain=EducationSubjects&assessment_id=' + _scope.assessment_id);

        $http({
            method: 'GET',
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var _items = _response.data.data;

            var _subjects = [];
            angular.forEach(_items, function(_item, key) {
                this.push(_item.education_subject);
            }, _subjects);

            deferred.resolve(_subjects);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function getColumnDefs(_scope) {
        var deferred = $q.defer();
        var _url = _scope.url('rest/Assessment-AssessmentPeriods.json?assessment_id=' + _scope.assessment_id);

        $http({
            method: 'GET',
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            _scope.periods = _response.data.data;
            var _filterParams = {
                cellHeight: 30
            };
            var _columnDefs = [];

            _columnDefs.push({
                headerName: "OpenEMIS ID",
                field: "openemis_id",
                filterParams: _filterParams
            });
            _columnDefs.push({
                headerName: "Name",
                field: "name",
                sort: 'asc',
                filterParams: _filterParams
            });
            _columnDefs.push({
                headerName: "student_id",
                field: "student_id",
                hide: true,
                filterParams: _filterParams
            });

            angular.forEach(_scope.periods, function(_period, key) {
                var _headerName = _period.name + " <span class='divider'></span> " + _period.weight;
                var _columnDef = {
                    headerName: _headerName,
                    field: 'period_' + _period.id,
                    filter: 'number',
                    cellStyle: function(params) {
                        if (parseInt(params.value) < 40) {
                            return {color: 'red'};
                        } else {
                            return {color: 'black'};
                        }
                    }
                };

                if (_period.editable && _scope.editMode) {
                    _columnDef.headerName += " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>";
                    _columnDef.editable = true;
                    _columnDef.cellClass = 'ag-cell-highlight';
                }

                this.push(_columnDef);
            }, _columnDefs);

            _columnDefs.push({
                headerName: "Total",
                field: "total",
                'filter': "number",
                filterParams: _filterParams
            });

            deferred.resolve(_columnDefs);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function initGrid(_scope) {
        var _subjects = _scope.subjects;
        var _columnDefs = _scope._columnDefs;

        if (angular.isDefined(_subjects) && _subjects.length > 0) {
            var _subject = _subjects[0];

            _scope.gridOptions = {
                context: {
                    institution_id: _scope.institution_id,
                    class_id: _scope.class_id,
                    assessment_id: _scope.assessment_id,
                    academic_period_id: _scope.academic_period_id,
                    education_grade_id: _scope.education_grade_id,
                    education_subject_id: _subject.id,
                },
                columnDefs: _columnDefs,
                rowData: [],
                headerHeight: 38,
                rowHeight: 38,
                enableColResize: true,
                enableSorting: true,
                unSortIcon: true,
                enableFilter: true,
                suppressMenuHide: true,
                singleClickEdit: true,
                onCellValueChanged: function(params) {
                    cellValueChanged(params, _scope);
                },
                onReady: function() {
                    _scope.gridOptions.api.refreshView();
                    _scope.gridOptions.api.sizeColumnsToFit();
                    _scope.reloadData(_subject);
                }
            };
        }
    }

    function getRowData(_scope) {
        var deferred = $q.defer();
        var _subject = _scope.subject;
        _scope.education_subject_id = _subject.id;

        // Always reset
        _scope.gridOptions.api.setRowData([]);

        var _urlStr = 'rest/Institution-InstitutionSubjectStudents.json';
        _urlStr += '?_contain=Users';
        _urlStr += '&_finder=Results[';
            _urlStr += 'institution_id:' + _scope.institution_id;
            _urlStr += ';class_id:' + _scope.class_id;
            _urlStr += ';assessment_id:' + _scope.assessment_id;
            _urlStr += ';academic_period_id:' + _scope.academic_period_id;
            _urlStr += ';subject_id:' + _scope.education_subject_id;
        _urlStr += ']';
        _urlStr += '&institution_class_id=' + _scope.class_id;

        var _url = _scope.url(_urlStr);

        $http({
            method: 'GET',
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var _subjectStudents = _response.data.data;

            var studentId = null;
            var currentStudentId = null;
            var _studentResults = {};
            var _rowData = [];
            angular.forEach(_subjectStudents, function(_subjectStudent, key) {
                currentStudentId = parseInt(_subjectStudent.student_id);

                if (studentId != currentStudentId) {
                    if (studentId != null) {
                        this.push(_studentResults);   
                    }
                    
                    _studentResults = {
                        openemis_id: _subjectStudent.openemis_no,
                        name: _subjectStudent.name,
                        student_id: currentStudentId,
                        total: 0
                    };
                    studentId = currentStudentId;
                }
                var marks = parseInt(_subjectStudent.marks);
                if (!isNaN(marks)) {
				    _studentResults['period_' + parseInt(_subjectStudent.assessment_period_id)] = parseInt(_subjectStudent.marks);
                }
            }, _rowData);

            if (_studentResults.hasOwnProperty('student_id')) {
                _rowData.push(_studentResults);
            }

            deferred.resolve(_rowData);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function cellValueChanged(params, _scope) {
        var _field = params.colDef.field;
        var _assessmentPeriodId = _field.replace('period_', '');

        var _data = {
            "marks" : parseInt(params.newValue),
            "assessment_id" : params.context.assessment_id,
            "education_subject_id" : params.context.education_subject_id,
            "student_id" : params.data.student_id,
            "institution_id" : params.context.institution_id,
            "academic_period_id" : params.context.academic_period_id,
            "assessment_period_id" : parseInt(_assessmentPeriodId)
        };

        setRowData(_scope, _data);
    }

    function setRowData(_scope, _data) {
        var deferred = $q.defer();
        var _url = _scope.url('rest/Assessment-AssessmentItemResults.json');

        $http({
            method: 'POST',
            url: _url,
            headers: {
                'Content-Type': 'application/json'
            },
            data: _data
        }).then(function successCallback(_response) {
            deferred.resolve(_response.data.data);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function switchMode(_scope) {
        getColumnDefs(_scope).then(function successCallback(_columnDefs) {
            if (_scope.gridOptions != null) {
                _scope.gridOptions.api.setColumnDefs(_columnDefs);
            }
        });
    }

    return {
        initValues: initValues,
        getAssessment: getAssessment,
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        initGrid: initGrid,
        getRowData: getRowData,
        cellValueChanged: cellValueChanged,
        setRowData: setRowData,
        switchMode: switchMode
    }
});
