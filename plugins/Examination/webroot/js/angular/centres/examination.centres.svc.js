angular
    .module('examination.centres.svc', ['kd.orm.svc', 'kd.session.svc'])
    .service('ExaminationCentresSvc', ExaminationCentresSvc);

ExaminationCentresSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc', 'KdSessionSvc'];

function ExaminationCentresSvc($http, $q, $filter, KdOrmSvc, KdSessionSvc) {

    var externalSource = null;
    var externalToken = null;

    var service = {
        init: init,
        getStudentRecords: getStudentRecords,
        getExternalStudentRecords: getExternalStudentRecords,
        getInstitutionId: getInstitutionId,
        getDefaultIdentityType: getDefaultIdentityType,
        getAcademicPeriods: getAcademicPeriods,
        getEducationGrades: getEducationGrades,
        getClasses: getClasses,
        getColumnDefs: getColumnDefs,
        getStudentData: getStudentData,
        postEnrolledStudent: postEnrolledStudent,
        getExternalSourceUrl: getExternalSourceUrl,
        addUser: addUser,
        formatDate: formatDate,
        getUserRecord: getUserRecord,
        getGenderRecord: getGenderRecord,
        importIdentities: importIdentities,
        getInternalIdentityTypes: getInternalIdentityTypes,
        addIdentityType: addIdentityType,
        setExternalSourceUrl: setExternalSourceUrl,
        getAccessToken: getAccessToken,
        resetExternalVariable: resetExternalVariable,
        getGenders: getGenders,
        getOpenEmisId: getOpenEmisId
    };

    var models = {
        Genders: 'User.Genders',
        StudentRecords: 'Institution.Students',
        Students: 'Student.Students',
        StudentUser: 'Institution.StudentUser',
        StudentStatuses: 'Student.StudentStatuses',
        InstitutionGrades: 'Institution.InstitutionGrades',
        Institutions: 'Institution.Institutions',
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionClasses: 'Institution.InstitutionClasses',
        IdentityTypes: 'FieldOption.IdentityTypes',
        ExternalDataSourceAttributes: 'ExternalDataSourceAttributes',
        Identities: 'User.Identities',

    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdSessionSvc.base(baseUrl);
        KdOrmSvc.init(models);
    };

    function resetExternalVariable()
    {
        externalSource = null;
        externalToken = null;
    }

    function getAccessToken()
    {
        var deferred = $q.defer();

        ExternalDataSourceAttributes
            .select()
            .where({
                external_data_source_type: 'Openemis Identities'
            })
            .ajax({defer: true})
            .then(function(response) {
                var data = response.data;
                var externalDataSourceObject = new Object;
                for(var i = 0; i < data.length; i++) {
                    externalDataSourceObject[data[i].attribute_field] = data[i].value;
                }
                if (externalDataSourceObject.hasOwnProperty('authentication_uri')) {
                    var authenticationUri = externalDataSourceObject.authentication_uri;

                    if (authenticationUri != '') {
                        delete externalDataSourceObject.authentication_uri;
                        delete externalDataSourceObject.user_record_uri;
                        delete externalDataSourceObject.redirect_uri;
                        var postData = 'grant_type=refresh_token';
                        var log = [];
                        angular.forEach(externalDataSourceObject, function(value, key) {
                          postData = postData + '&' + key + '=' + value;
                        }, log);
                        $http({
                            method: 'POST',
                            url: authenticationUri,
                            data: postData,
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                        }).then(function(res) {
                            deferred.resolve(res.data.access_token);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    } else {
                        var error = 'No authentication URI';
                        deferred.reject(error);
                    }
                }
            }, function(error){
                deferred.reject(error);
            });

        return deferred.promise;
    }

    function getExternalSourceUrl()
    {
        return ExternalDataSourceAttributes
            .find('Uri', {record_type: 'user_record_uri'})
            .ajax({defer: true});
    };

    function setExternalSourceUrl(url)
    {
        var externalSource = url;
    };

    function getExternalStudentRecords(options) {
        var deferred = $q.defer();
        var vm = this;

        this.getExternalSourceUrl()
        .then(function(sourceUrl) {
            var source = sourceUrl.data;
            vm.getAccessToken()
            .then(function(token){
                var sourceUrl = null;
                if (source.length > 0) {
                    sourceUrl = source[0].value;
                    externalSource = sourceUrl;
                    externalToken = token;
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
                                params[key] = '_' + options['conditions'][key] + '_';
                            }
                        }
                    }
                    if (Object.getOwnPropertyNames(params).length !== 0) {
                        Students.orWhere(params);
                    }
                }
                var authorizationHeader = 'Bearer ' + token;

                return Students.ajax({defer: true, url: sourceUrl, authorizationHeader: authorizationHeader});
            }, function(error){
                deferred.reject(error);
            })
            .then(function(response){
                deferred.resolve(response);
            }, function(error){
                deferred.reject(error);
            });
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getStudentRecords(options) {
        var deferred = $q.defer();

        this.getInstitutionId()
        .then(function(response) {
            var institutionId = response[0];
            var params = {
                limit: options['endRow'] - options['startRow'],
                page: options['endRow'] / (options['endRow'] - options['startRow']),
            }

            if (options.hasOwnProperty('conditions')) {
                for (var key in options['conditions']) {
                    if (typeof options['conditions'][key] == 'string') {
                        options['conditions'][key] = options['conditions'][key].trim();

                        if (options['conditions'][key] !== '') {
                            params[key] = options['conditions'][key];
                        }
                    }
                }
            }

            Students.reset();
            Students.find('Students', params);

            return Students.ajax({defer: true});
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        }).then(function(response) {
            deferred.resolve(response);
        }, function(error) {
            console.log(error);
            deferred.reject(error);
        });

        return deferred.promise;
    };

    function getStudentData(id) {
        var sourceUrl = externalSource;
        var success = function(response, deferred) {
            var studentData = response.data.data;
            if (angular.isObject(studentData) && studentData.length > 0) {
                deferred.resolve(studentData[0]);
            } else {
                deferred.reject('Student not found');
            }
        };

        Students.select();
        var settings = {success: success, defer: true, url: sourceUrl};
        if (externalSource != null && externalToken !=null) {
            var authorizationHeader = 'Bearer ' + externalToken;
            settings.authorizationHeader = authorizationHeader;
        }
        return Students
            .contain(['Genders', 'Identities.IdentityTypes'])
            .where({
                id: id
            })
            .ajax(settings);
    };

    function importIdentities(userId, identitiesRecord)
    {
        var deferred = $q.defer();
        var vm = this;
        vm.getInternalIdentityTypes()
        .then(function(response) {
            var data = response.data;
            var promises = [];
            for (var i = 0; i < identitiesRecord.length; i++) {
                var identityTypeId = null;
                for(var j = 0; j < data.length ; j++) {
                    if (identitiesRecord[i].identity_type.name == data[j].name) {
                        identityTypeId = data[j].id;
                    }
                }
                if (identityTypeId != null) {
                    promises.push(identityTypeId);
                } else {
                    promises.push(vm.addIdentityType(identitiesRecord[i].identity_type));
                }
            }
            return $q.all(promises);
        }, function(error) {
            deferred.reject(error);
        })
        .then(function(identityTypeIds){
            var promises = [];
            for (var i = 0; i < identitiesRecord.length; i++) {
                identitiesRecord[i].identity_type_id = identityTypeIds[i];
                delete(identitiesRecord[i]['id'])
                delete(identitiesRecord[i]['identity_type']);
                delete(identitiesRecord[i]['created']);
                delete(identitiesRecord[i]['modified']);
                delete(identitiesRecord[i]['modified_user_id']);
                delete(identitiesRecord[i]['created_user_id']);
                identitiesRecord[i]['security_user_id'] = userId;
                if (identitiesRecord[i]['issue_date'] != null) {
                    identitiesRecord[i]['issue_date'] = vm.formatDate(identitiesRecord[i]['issue_date']);
                }
                if (identitiesRecord[i]['expiry_date'] != null) {
                    identitiesRecord[i]['expiry_date'] = vm.formatDate(identitiesRecord[i]['expiry_date']);
                }
                promises.push(Identities.save(identitiesRecord[i]));
            }
            return $q.all(promises);
        }, function(error) {
            deferred.reject(error);
        })
        .then(function(response){
            deferred.resolve(response);
        }, function(error){
            deferred.reject(error);
        });

        return deferred.promise;
    };

    function addIdentityType(identityType)
    {
        var deferred = $q.defer();
        delete(identityType['id']);
        delete(identityType['created']);
        delete(identityType['modified']);
        delete(identityType['modified_user_id']);
        delete(identityType['created_user_id']);
        IdentityTypes.save(identityType)
        .then(function(response) {
            deferred.resolve(response.data.data.id);
        }, function(error) {
            deferred.reject(error);
            console.log(error);
        });
        return deferred.promise;
    }

    function getInternalIdentityTypes()
    {
        return IdentityTypes.select().ajax({defer:true});
    };

    function getOpenEmisId()
    {
        return StudentUser
                .select()
                .find('GetOpenEmisId')
                .ajax({defer: true});
    }

    function addUser(userRecord)
    {
        var deferred = $q.defer();
        var vm = this;
        if (externalSource == null) {
            delete userRecord['gender'];
            userRecord['is_student'] = 1;
            StudentUser.reset();
            StudentUser.save(userRecord)
            .then(function(studentRecord) {
                deferred.resolve(studentRecord.data);
            }, function(error) {
                deferred.reject(error);
                console.log(error);
            });
        } else {
            vm.getUserRecord(userRecord['openemis_no'])
            .then(function(response) {
                if (response.data.length > 0) {
                    userData = response.data[0];
                    modifiedUser = {id: userData.id, is_student: 1};
                    Students.save(modifiedUser)
                    .then(function(response) {
                        deferred.resolve(response.data);
                    }, function(error) {
                        deferred.reject(error);
                        console.log(error);
                    });
                } else {
                    delete userRecord['id'];
                    delete userRecord['username'];
                    delete userRecord['password'];
                    delete userRecord['last_login'];
                    delete userRecord['address_area_id'];
                    delete userRecord['birthplace_area_id'];
                    delete userRecord['created'];
                    delete userRecord['modified'];
                    delete userRecord['modified_user_id'];
                    delete userRecord['created_user_id'];
                    userRecord['date_of_birth'] = vm.formatDate(userRecord['date_of_birth']);
                    userRecord['is_student'] = 1;
                    vm.getGenderRecord(userRecord['gender']['code'])
                    .then(function(genderRecord) {
                        if (genderRecord.data.length > 0) {
                            delete userRecord['gender'];
                            var identitiesRecord = userRecord['identities'];
                            delete userRecord['identities'];
                            userRecord['gender_id'] = genderRecord.data[0].id;
                            StudentUser.reset();
                            StudentUser.save(userRecord)
                            .then(function(studentRecord) {
                                var userEntity = studentRecord.data.data;
                                var userEntityError = studentRecord.data.error;
                                if (userEntityError.length > 0) {
                                    deferred.resolve(studentRecord.data);
                                } else {
                                    var userId = userEntity.id;
                                    vm.importIdentities(userId, identitiesRecord)
                                    .then(function(res){
                                        deferred.resolve(studentRecord.data);
                                    }, function(error){
                                        deferred.resolve(studentRecord.data);
                                    });
                                }
                            }, function(error) {
                                deferred.reject(error);
                                console.log(error);
                            });
                        }

                    }, function(error) {
                        deferred.reject(error);
                        console.log(error);
                    });

                }
            }, function(error) {
                deferred.reject(error);
                console.log(error);
            });
        }


        return deferred.promise;
    };

    function getUserRecord(openemisNo)
    {
        return Students
            .select()
            .where({
                'openemis_no': openemisNo
            })
            .ajax({defer: true});
    };

    function getGenderRecord(code)
    {
        return Genders
            .select()
            .where({
                'code': code
            })
            .ajax({defer: true});
    };

    function getGenders()
    {
        var success = function(response, deferred) {
            var genderRecords = response.data.data;
            deferred.resolve(genderRecords);
        };
        return Genders
            .select()
            .ajax({success:success, defer: true});
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


    function formatDate(datetime) {
        datetime = new Date(datetime);

        var yyyy = datetime.getFullYear().toString();
        var mm = (datetime.getMonth()+1).toString(); // getMonth() is zero-based
        var dd  = datetime.getDate().toString();

        return yyyy + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + (dd[1]?dd:"0"+dd[0]); // padding
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
