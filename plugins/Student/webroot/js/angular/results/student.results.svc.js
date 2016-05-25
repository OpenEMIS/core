angular
    .module('student.results.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('StudentResultsSvc', StudentResultsSvc);

StudentResultsSvc.$inject = ['$q', 'KdOrmSvc', 'KdSessionSvc'];

function StudentResultsSvc($q, KdOrmSvc, KdSessionSvc) {

    var models = {
        AcademicPeriodsTable: 'AcademicPeriod.AcademicPeriods',
        AssessmentsTable: 'Assessment.Assessments',
        AssessmentItemsTable: 'Assessment.AssessmentItems',
        AssessmentPeriodsTable: 'Assessment.AssessmentPeriods',
        AssessmentItemResultsTable: 'Assessment.AssessmentItemResults'
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
        console.log(getColumnDefs);

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
                filterParams: filterParams
            };

            this.push(columnDef);
        }, columnDefs);

        columnDefs.push({
            headerName: "Total Mark",
            field: "total_mark",
            filter: "number"
        });

        return {data: columnDefs};
    };

    function getRowData() {
        var success = function(response, deferred) {
            if (angular.isDefined(response.data.error)) {
                deferred.reject(response.data.error);
            } else {
                var studentResults = response.data.data;

                var rowData = [];
                deferred.resolve(rowData);
            }
        };

        return AssessmentItemResultsTable
            .select()
            .ajax({success: success, defer: true})
            ;
    };
}
