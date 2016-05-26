angular
    .module('student.results.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('StudentResultsSvc', StudentResultsSvc);

StudentResultsSvc.$inject = ['$q', '$filter', 'KdOrmSvc', 'KdSessionSvc'];

function StudentResultsSvc($q, $filter, KdOrmSvc, KdSessionSvc) {

    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        AssessmentsTable: 'Assessment.Assessments',
        AssessmentItemsTable: 'Assessment.AssessmentItems',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults',
        InstitutionSubjectStudentsTable: 'Institution.InstitutionSubjectStudents'
    };

    var service = {
        init: init,
        getSessions: getSessions,
        getAcademicPeriods: getAcademicPeriods,
        getAssessments: getAssessments,
        getAssessmentPeriods: getAssessmentPeriods,
        getColumnDefs: getColumnDefs,
        getRowData: getRowData
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdSessionSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function getSessions() {
        var promises = [];

        promises.push(KdSessionSvc.read('Institution.Institutions.id'));
        promises.push(KdSessionSvc.read('Student.Students.id'));

        return $q.all(promises);
    };

    function getAcademicPeriods() {
        var success = function(response, deferred) {
            var academicPeriodResults = response.data.data;

            if (angular.isObject(academicPeriodResults) && academicPeriodResults.length > 0) {
                var academicPeriods = [];

                angular.forEach(academicPeriodResults, function(academicPeriod, key) {
                    if (academicPeriod.parent_id != 0) {
                        this.push({text: academicPeriod.name, value: academicPeriod.id});
                    }
                }, academicPeriods);

                deferred.resolve(academicPeriods);
            } else {
                deferred.reject('No Academic Periods');
            }
        };

        return AcademicPeriodsTable
            .select()
            .order(['academic_period_level_id', 'order'])
            .ajax({success: success, defer: true});
    };

    function getAssessments(periodId) {
        var success = function(response, deferred) {
            var assessmentResults = response.data.data;

            if (angular.isObject(assessmentResults) && assessmentResults.length > 0) {
                var assessments = [];

                angular.forEach(assessmentResults, function(assessment, key) {
                    this.push({text: assessment.name, value: assessment.id});
                }, assessments);

                deferred.resolve(assessments);
            } else {
                deferred.reject('No Assessments');
            }
        };

        return AssessmentsTable
            .select()
            .where({academic_period_id: periodId})
            .order(['code', 'name'])
            .ajax({success: success, defer: true});
    };

    function getAssessmentPeriods(assessmentId) {
        var success = function(response, deferred) {
            var assessmentPeriods = response.data.data;

            if (angular.isObject(assessmentPeriods) && assessmentPeriods.length > 0) {
                deferred.resolve(assessmentPeriods);
            } else {
                deferred.reject('You need to configure Assessment Periods first');
            }   
        };

        return AssessmentPeriodsTable
            .select()
            .where({assessment_id: assessmentId})
            .ajax({success: success, defer: true});
    };

    function getColumnDefs(assessmentPeriods) {
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "Subject",
            field: "subject",
            filterParams: filterParams
        });

        angular.forEach(assessmentPeriods, function(assessmentPeriod, key) {
            var assessmentPeriodField = 'period_' + assessmentPeriod.id;

            var columnDef = {
                headerName: assessmentPeriod.name,
                field: assessmentPeriodField,
                filter: "number",
                filterParams: filterParams,
                valueGetter: function(params) {
                    var value = params.data[params.colDef.field];

                    if (!isNaN(parseFloat(value))) {
                        return $filter('number')(value, 2);
                    } else {
                        return '';
                    }
                }
            };

            this.push(columnDef);
        }, columnDefs);

        columnDefs.push({
            headerName: "Total Mark",
            field: "total_mark",
            filter: "number",
            filterParams: filterParams,
            valueGetter: function(params) {
                var value = params.data[params.colDef.field];

                if (!isNaN(parseFloat(value))) {
                    return $filter('number')(value, 2);
                } else {
                    return '';
                }
            }
        });

        return {data: columnDefs};
    };

    function getRowData(periodId, assessmentId) {
        var deferred = $q.defer();

        this.getSessions()
        .then(function(response) {
            var institutionId = response[0];
            var studentId = response[1];

            var success = function(response, deferred) {
                if (angular.isDefined(response.data.error)) {
                    deferred.reject(response.data.error);
                } else {
                    var subjectStudents = response.data.data;

                    if (angular.isObject(subjectStudents) && subjectStudents.length > 0) {
                        var subjectId = null;
                        var currentSubjectId = null;
                        var studentResults = {};
                        var rowData = [];

                        angular.forEach(subjectStudents, function(subjectStudent, key) {
                            currentSubjectId = parseInt(subjectStudent.education_subject_id);

                            if (subjectId != currentSubjectId) {
                                if (subjectId != null) {
                                    this.push(studentResults);
                                }

                                studentResults = {
                                    education_subject_id: subjectStudent.education_subject_id,
                                    subject: subjectStudent.education_subject_name,
                                    total_mark: subjectStudent.total_mark
                                };

                                subjectId = currentSubjectId;
                            }

                            var marks = parseFloat(subjectStudent.marks);
                            if (!isNaN(marks)) {
                                studentResults['period_' + parseInt(subjectStudent.assessment_period_id)] = marks;
                            }
                        }, rowData);

                        if (studentResults.hasOwnProperty('education_subject_id')) {
                            rowData.push(studentResults);
                        }

                        deferred.resolve(rowData);
                    } else {
                        deferred.reject('No Results');
                    }
                    // var studentResults = response.data.data;
                    // var rowData = [];

                    // rowData.push({
                    //     subject: 'Social Studies',
                    //     period_2: 88.66,
                    //     period_3: 99.22,
                    //     total_mark: 77.33
                    // });

                    // deferred.resolve(rowData);
                }
            };

            return InstitutionSubjectStudentsTable
                .select()
                .contain(['EducationSubjects'])
                .find('ResultsByStudent', {
                    institution_id: institutionId,
                    academic_period_id: periodId,
                    student_id: studentId,
                    assessment_id: assessmentId
                })
                // .where({
                //     institution_id: institutionId,
                //     academic_period_id: periodId,
                //     student_id: studentId
                // })
                .ajax({success: success, defer: true})
                ;
            // return AssessmentItemResultsTable
            //     .select()
            //     .where({
            //         institution_id: institutionId,
            //         student_id: studentId,
            //         academic_period_id: periodId,
            //         assessment_id: assessmentId
            //     })
            //     .ajax({success: success, defer: true})
            //     ;
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        })
        // Student Results
        .then(function(response) {
            deferred.resolve(response);
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        });

        return deferred.promise;
    };
}
