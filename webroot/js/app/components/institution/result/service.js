angular.module('institution.result.service', [])
.service('ResultService', function($q, $http, $location) {
    function initValues(_scope) {
        _scope.class_id = parseInt($location.search()['class_id']);
        _scope.assessment_id = parseInt($location.search()['assessment_id']);
    }

    function getAssessment(_scope) {
        var deferred = $q.defer();
        var _url = _scope.url('rest/Assessment-Assessments/' + _scope.assessment_id + '.json');

        $http({
            method: 'GET', // 'POST'
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
            method: 'GET', // 'POST'
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
            method: 'GET', // 'POST'
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var _periods = _response.data.data;
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
                filterParams: _filterParams,
                sort: 'asc'
            });

            angular.forEach(_periods, function(_period, key) {
                var _columnDef = {};
                var _headerName = _period.name + " <span class='divider'></span> " + _period.weight;

                _columnDef.headerName = _headerName;
                _columnDef.field = "period_" + _period.id;

                if (_period.editable) {
                    _columnDef.headerName += " <i class='fa fa-pencil-square-o fa-lg header-icon'></i>";
                    _columnDef.editable = true;
                }

                this.push(_columnDef);
            }, _columnDefs);

            _columnDefs.push({
                headerName: "Total",
                field: "total",
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

        if (_subjects.length > 0) {
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
                onCellValueChanged: cellValueChanged,
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
            method: 'GET', // 'POST'
            url: _url,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function successCallback(_response) {
            var _subjectStudents = _response.data.data;

            var _rowData = [];
            angular.forEach(_subjectStudents, function(_subjectStudent, key) {
                this.push({
                    openemis_id: _subjectStudent.user.openemis_no,
                    name: _subjectStudent.user.name,
                    total: 0
                });
            }, _rowData);

            deferred.resolve(_rowData);
        }, function errorCallback(_error) {

        }, function progressCallback(_response) {

        });

        return deferred.promise;
    }

    function cellValueChanged(params) {
        console.log(params);
    }

    return {
        initValues: initValues,
        getAssessment: getAssessment,
        getSubjects: getSubjects,
        getColumnDefs: getColumnDefs,
        initGrid: initGrid,
        getRowData: getRowData,
        cellValueChanged: cellValueChanged
    }
});
