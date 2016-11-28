angular
    .module('institutions.students.svc', ['kd.orm.svc'])
    .service('InstitutionsStudentsSvc', InstitutionsStudentsSvc);

InstitutionsStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function InstitutionsStudentsSvc($http, $q, $filter, KdOrmSvc) {

    var externalSource = null;
    var externalToken = null;
    var institutionId = null;

    var service = {
        init: init,
        initExternal: initExternal,
        getStudentRecords: getStudentRecords,
        getExternalStudentRecords: getExternalStudentRecords,
        setInstitutionId: setInstitutionId,
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
        getUniqueOpenEmisId: getUniqueOpenEmisId,
        formatDateReverse: formatDateReverse,
        getAddNewStudentConfig: getAddNewStudentConfig,
        getUserContactTypes: getUserContactTypes,
        getIdentityTypes: getIdentityTypes,
        getNationalities: getNationalities,
        getSpecialNeedTypes: getSpecialNeedTypes
    };

    var models = {
        Genders: 'User.Genders',
        StudentRecords: 'Institution.StudentAdmission',
        StudentUser: 'Institution.StudentUser',
        InstitutionGrades: 'Institution.InstitutionGrades',
        Institutions: 'Institution.Institutions',
        AcademicPeriods: 'AcademicPeriod.AcademicPeriods',
        InstitutionClasses: 'Institution.InstitutionClasses',
        IdentityTypes: 'FieldOption.IdentityTypes',
        ExternalDataSourceAttributes: 'Configuration.ExternalDataSourceAttributes',
        Identities: 'User.Identities',
        ConfigItems: 'Configuration.ConfigItems',
        Nationalities: 'FieldOption.Nationalities',
        ContactTypes: 'User.ContactTypes',
        SpecialNeedTypes: 'FieldOption.SpecialNeedTypes'
    };

    var externalModels = {
        IdentityTypes: 'IdentityTypes'

    };

    return service;

    function init(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Students');
        KdOrmSvc.init(models);
    };

    function initExternal(baseUrl) {
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('ExternalAPI');
        KdOrmSvc.init(externalModels);
    };

    function resetExternalVariable()
    {
        externalSource = null;
        externalToken = null;
    }

    function getAccessToken()
    {
        var deferred = $q.defer();
        this.init(angular.baseUrl);
        ConfigItems
        .select()
        .where({
            code: 'external_data_source_type'
        })
        .ajax({defer: true})
        .then(function(response) {
            var externalDataSourceType = response.data[0]['value'];
            ExternalDataSourceAttributes
            .select()
            .where({
                external_data_source_type: externalDataSourceType
            })
            .ajax({defer: true})
            .then(function(response) {
                var data = response.data;
                var externalDataSourceObject = new Object;
                for(var i = 0; i < data.length; i++) {
                    externalDataSourceObject[data[i].attribute_field] = data[i].value;
                }
                if (externalDataSourceObject.hasOwnProperty('token_uri')) {
                    var tokenUri = externalDataSourceObject.token_uri;

                    if (tokenUri != '') {
                        delete externalDataSourceObject.token_uri;
                        delete externalDataSourceObject.record_uri;
                        delete externalDataSourceObject.redirect_uri;
                        var postData = 'grant_type=refresh_token';
                        var log = [];
                        angular.forEach(externalDataSourceObject, function(value, key) {
                          postData = postData + '&' + key + '=' + value;
                        }, log);
                        $http({
                            method: 'POST',
                            url: tokenUri,
                            data: postData,
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                        }).then(function(res) {
                            deferred.resolve(res.data.access_token);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    } else {
                        var error = 'No Token URI';
                        deferred.reject(error);
                    }
                }
            }, function(error){
                deferred.reject(error);
            });
        }, function(error) {
            deferred.reject(error);
        });

        return deferred.promise;
    }

    function getExternalSourceUrl()
    {
        return ExternalDataSourceAttributes
            .find('Uri', {record_type: 'record_uri'})
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
                vm.initExternal(sourceUrl);
                var pageParams = {
                    limit: options['endRow'] - options['startRow'],
                    page: options['endRow'] / (options['endRow'] - options['startRow']),
                };

                var params = {};
                params['super_admin'] = 0;

                // Get url from user input
                var replaceURL = sourceUrl;
                var replacement = {
                    "{page}": pageParams.page,
                    "{limit}": pageParams.limit,
                    "{first_name}": '',
                    "{last_name}": '',
                    "{identity_number}": '',
                    "{date_of_birth}": ''
                }

                var conditionsCount = 0;

                if (options.hasOwnProperty('conditions')) {
                    for (var key in options['conditions']) {
                        if (typeof options['conditions'][key] == 'string') {
                            options['conditions'][key] = options['conditions'][key].trim();
                            if (key != 'date_of_birth') {
                                params[key] = options['conditions'][key];
                            } else {
                                params[key] = vm.formatDateReverse(options['conditions'][key]);
                            }
                            var replaceKey = '{'+key+'}';
                            replacement[replaceKey] = params[key];
                            conditionsCount++;
                        }
                    }
                }

                var url = replaceURL.replace(/{\w+}/g, function(all) {
                    return all in replacement ? replacement[all] : all;
                });

                var authorizationHeader = 'Bearer ' + token;

                var success = function(response, deferred) {
                    var studentData = response.data;
                    studentData['conditionsCount'] = conditionsCount;
                    if (angular.isObject(studentData)) {
                        deferred.resolve(studentData);
                    } else {
                        deferred.reject('Error getting student records');
                    }
                };

                var opt = {
                    method: 'GET',
                    headers: {'Content-Type': 'application/json', 'Authorization': authorizationHeader}
                }
                return KdOrmSvc.customAjax(url, opt);
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
        var vm = this;
        var institutionId = vm.getInstitutionId();
        var params = {
            limit: options['endRow'] - options['startRow'],
            page: options['endRow'] / (options['endRow'] - options['startRow']),
        }



        var conditionsCount = 0;

        if (options.hasOwnProperty('conditions')) {
            for (var key in options['conditions']) {
                if (typeof options['conditions'][key] == 'string') {
                    options['conditions'][key] = options['conditions'][key].trim();

                    if (options['conditions'][key] !== '') {
                        if (key == 'date_of_birth') {
                            options['conditions'][key] = vm.formatDateReverse(options['conditions'][key]);
                        }
                        params[key] = options['conditions'][key];
                        conditionsCount++;
                    }
                }
            }
        }

        var success = function(response, deferred) {
            var studentData = response.data;
            studentData['conditionsCount'] = conditionsCount;
            if (angular.isObject(studentData)) {
                deferred.resolve(studentData);
            } else {
                deferred.reject('Error getting student records');
            }
        };

        params['institution_id'] = institutionId;
        StudentUser.reset();
        StudentUser.find('Students', params);
        StudentUser.find('enrolledInstitutionStudents');
        StudentUser.contain(['Genders']);

        return StudentUser.ajax({defer: true, success: success});
    };

    function getStudentData(id) {
        var vm = this;
        var success = function(response, deferred) {
            var studentData = response.data.data;
            if (angular.isObject(studentData) && studentData.length > 0) {
                deferred.resolve(studentData[0]);
            } else {
                deferred.reject('Student not found');
            }
        };

        StudentUser.select();
        var settings = {success: success, defer: true};
        return StudentUser
            .contain(['Genders', 'Identities.IdentityTypes'])
            .find('enrolledInstitutionStudents')
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

    function addUser(userRecord)
    {
        var deferred = $q.defer();
        var vm = this;
        if (externalSource == null) {
            userRecord['username'] = userRecord['openemis_no'];
            delete userRecord['gender'];
            if (userRecord['nationality_id'] != '' && userRecord['nationality_id'] != undefined) {
                userRecord['nationalities'] = [{
                    'nationality_id': userRecord['nationality_id']
                }];
            }
            if (userRecord['identity_type_id'] != '' && userRecord['identity_type_id'] != undefined && userRecord['identity_number'] != undefined && userRecord['identity_number'] != '') {
                userRecord['identities'] = [{
                    'identity_type_id': userRecord['identity_type_id'],
                    'number': userRecord['identity_number']
                }];
            }
            delete userRecord['identity_type_id'];
            delete userRecord['identity_number'];
            delete userRecord['nationality_id'];
            StudentUser.reset();
            StudentUser.save(userRecord)
            .then(function(studentRecord) {
                deferred.resolve([studentRecord.data, {}]);
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
                    StudentUser.save(modifiedUser)
                    .then(function(response) {
                        deferred.resolve([response.data, userData]);
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
                    vm.getGenderRecord(userRecord['gender']['name'])
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
                                        deferred.resolve([studentRecord.data, {}]);
                                    }, function(error){
                                        deferred.resolve([studentRecord.data, {}]);
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
        return StudentUser
            .select()
            .where({
                'openemis_no': openemisNo
            })
            .contain(['Genders', 'Identities.IdentityTypes'])
            .find('enrolledInstitutionStudents')
            .ajax({defer: true});
    };

    function getGenderRecord(name)
    {
        return Genders
            .select()
            .where({
                'name': name
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
        var institutionId = this.getInstitutionId();
        data['institution_id'] = institutionId;
        data['student_status_id'] = 1;
        data['previous_institution_id'] = 0;
        data['student_transfer_reason_id'] = 0;
        data['type'] = 1;
        data['status'] = 0;
        data['institution_class_id'] = data['class'];
        return StudentRecords.save(data)
    };

    function setInstitutionId(id) {
        this.institutionId = id;
    }

    function getInstitutionId() {
        return this.institutionId;
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

    function formatDateReverse(datetime) {
        var dateArr = datetime.split('-');
        return dateArr[2] + '-' + dateArr[1] + '-' + dateArr[0];
    }

    function formatDate(datetime) {
        datetime = new Date(datetime);

        var yyyy = datetime.getFullYear().toString();
        var mm = (datetime.getMonth()+1).toString(); // getMonth() is zero-based
        var dd  = datetime.getDate().toString();

        return (dd[1]?dd:"0"+dd[0]) + '-' + (mm[1]?mm:"0"+mm[0])  + '-' +   yyyy; // padding
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
            .find('SchoolAcademicPeriod')
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
            InstitutionGrades.find('EducationGradeInCurrentInstitution', {academic_period_id: options.academicPeriodId, institution_id: options.institutionId});
        } else {
            InstitutionGrades.find('EducationGradeInCurrentInstitution', {institution_id: options.institutionId});
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
                institution_id: options.institutionId,
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

    function getUniqueOpenEmisId() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getUniqueOpenemisId/Student';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.openemis_no);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getAddNewStudentConfig() {
        return ConfigItems
            .select()
            .where({type: 'Add New Student'})
            .ajax({defer: true});
    }

    function getUserContactTypes() {
        return ContactTypes
            .select()
            .ajax({defer: true});
    }

    function getIdentityTypes() {
        return IdentityTypes
            .select()
            .ajax({defer: true});
    }

    function getNationalities() {
        return Nationalities
            .select()
            .contain(['IdentityTypes'])
            .ajax({defer: true});
    }
    function getSpecialNeedTypes() {
        return SpecialNeedTypes
            .select()
            .ajax({defer: true});
    }
};
