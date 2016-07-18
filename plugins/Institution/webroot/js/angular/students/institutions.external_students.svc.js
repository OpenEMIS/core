angular
    .module('institutions.external_students.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('InstitutionsExternalStudentsSvc', InstitutionsStudentsSvc);

InstitutionsStudentsSvc.$inject = ['$q', '$filter', 'KdOrmSvc', 'KdSessionSvc'];

function InstitutionsStudentsSvc($q, $filter, KdOrmSvc, KdSessionSvc) {

    var vm = this;

    var service = {
        init: init,
        getStudentRecords: getStudentRecords,
        getInstitutionId: getInstitutionId,
        getDefaultIdentityType: getDefaultIdentityType,
        getAcademicPeriods: getAcademicPeriods,
        getEducationGrades: getEducationGrades,
        getClasses: getClasses,
        getColumnDefs: getColumnDefs,
        getStudentData: getStudentData,
        postEnrolledStudent: postEnrolledStudent,
        getExternalSourceUrl: getExternalSourceUrl
    };

    var models = {
        StudentRecords: 'Institution.Students',
        Students: 'Student.Students',
        StudentStatuses: 'Student.StudentStatuses',
        InstitutionGrades: 'Institution.InstitutionGrades',
        Institutions: 'Institution.Institutions',
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionClasses: 'Institution.InstitutionClasses',
        IdentityTypes: 'FieldOption.IdentityTypes',
        ExternalDataSourceAttributes: 'ExternalDataSourceAttributes'
    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdSessionSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function getExternalSourceUrl()
    {
        return ExternalDataSourceAttributes
            .find('Uri', {record_type: 'user_record_uri'})
            .ajax({defer: true});
    };

    function getStudentRecords(options) {
        var deferred = $q.defer();

        this.getExternalSourceUrl()
        .then(function(sourceUrl) {
            var source = sourceUrl.data;
            var sourceUrl = null;
            if (source.length > 0) {
                sourceUrl = source[0].value;
            }
            var pageParams = {
                limit: options['endRow'] - options['startRow'],
                page: options['endRow'] / (options['endRow'] - options['startRow']),
            };

            var params = {};
            Students.reset();
            Students
                .page(pageParams.page)
                .limit(pageParams.limit);

            if (options.hasOwnProperty('conditions')) {
                for (var key in options['conditions']) {
                    if (typeof options['conditions'][key] == 'string') {
                        options['conditions'][key] = options['conditions'][key].trim();
                        if (options['conditions'][key] !== '') {
                            params[key] = options['conditions'][key];
                        }
                    }
                }
                if (Object.getOwnPropertyNames(params).length !== 0) {
                    Students.orWhere(params);
                }
            }
            
            
            return Students.ajax({defer: true, url: sourceUrl});
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        }).then(function(response) {
            deferred.resolve(response);
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        });

        // this.getExternalSourceUrl()
        // .then(function(sourceUrl) {
        //     var promises = [];
        //     promises.push(sourceUrl.data);
        //     return $q.all(promises);
        // }, function(error) {
        //     console.log('error:');
        //     console.log(error);
        //     deferred.reject(error);
        // })
        // .then(function(returnSource) {
        //     console.log(returnSource);
        //     var params = {
        //         limit: options['endRow'] - options['startRow'],
        //         page: options['endRow'] / (options['endRow'] - options['startRow']),
        //     }

        //     if (options.hasOwnProperty('conditions')) {
        //         for (var key in options['conditions']) {
        //             if (typeof options['conditions'][key] == 'string') {
        //                 options['conditions'][key] = options['conditions'][key].trim();

        //                 if (options['conditions'][key] !== '') {
        //                     params[key] = options['conditions'][key];
        //                 }
        //             }
        //         }
        //     }



        //     Students.reset();
        //     Students
        //         .page(params.page)
        //         .limit(params.limit);
        //     return Students.ajax({defer: true, url: 'http://localhost:8080/school/api/restful/SecurityUsers.json'});
        // });

        return deferred.promise;
    };

    function getStudentData(id) {
        var success = function(response, deferred) {
            var studentData = response.data.data;
            if (angular.isObject(studentData) && studentData.length > 0) {
                deferred.resolve(studentData[0]);
            } else {
                deferred.reject('Student not found');
            }
        };

        Students.reset();
        return Students.select()
            .contain(['Genders'])
            .where(
                {
                    id: id
                }
            )
            .ajax({success: success, defer: true});
    };

    function postEnrolledStudent(data) {
        var deferred = $q.defer();

        this.getInstitutionId()
        .then(function(response) {
            var institutionId = response[0];
            data['institution_id'] = institutionId;
            data['student_status_id'] = 1;

            // console.log('posting...');
            // console.log(data);
            return StudentRecords.save(data)
        }, function(error) {
            deferred.reject(error);
            console.log(error);
        })
        .then(function(response) {
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
            console.log(error);
        });

        return deferred.promise;
    };

    function getInstitutionId() {
        var promises = [];
        promises.push(KdSessionSvc.read('Institution.Institutions.id'));
        return $q.all(promises);
    };

    function getDefaultIdentityType() {
        var success = function(response, deferred) {
            var defaultIdentityType = response.data.data;
            if (angular.isObject(defaultIdentityType) && defaultIdentityType.length > 0) {
                deferred.resolve(defaultIdentityType);
            } else {
                deferred.resolve(defaultIdentityType);
            }
        };

        return IdentityTypes
            .find('DefaultIdentityType')
            .ajax({success: success, defer: true});
    };

    function getAcademicPeriods() {
        var success = function(response, deferred) {
            var periods = response.data.data;
            if (angular.isObject(periods) && periods.length > 0) {
                deferred.resolve(periods);
            } else {
                deferred.reject('You need to configure Assessment Periods first');
            }
        };
        return AcademicPeriods
            .select(['id', 'name', 'current', 'start_date', 'end_date'])
            .where(
                {
                    editable: 1,
                    academic_period_level_id: '1'
                }
            )
            .order(['order'])
            .ajax({success: success, defer: true});
    };

    function getEducationGrades(options) {
        var success = function(response, deferred) {
            var educationGrades = response.data.data;
            if (angular.isObject(educationGrades) && educationGrades.length > 0) {
                deferred.resolve(educationGrades);
            } else {
                deferred.reject('You need to configure Education Grades first');
            }
        };

        InstitutionGrades.select();

        if (typeof options !== "undefined" && options.hasOwnProperty('academicPeriodId')) {
            InstitutionGrades.find('EducationGradeInCurrentInstitution', {academic_period_id: options.academicPeriodId});
        } else {
            InstitutionGrades.find('EducationGradeInCurrentInstitution');
        }

        return InstitutionGrades.ajax({success: success, defer: true});
    };

    function getClasses(options) {
        var success = function(response, deferred) {
            var classes = response.data.data;
            // does not matter if no classes available
            deferred.resolve(classes);
        };
        return InstitutionClasses
            .select()
            .find('ClassOptions', {
                academic_period_id: options.academicPeriodId,
                grade_id: options.gradeId
            })
            .ajax({success: success, defer: true});
    }

    function getColumnDefs() {
        var filterParams = {
            cellHeight: 30
        };
        var columnDefs = [];

        columnDefs.push({
            headerName: "OpenEMIS ID",
            field: "openemis_id",
            filterParams: filterParams
        });
    };
};
