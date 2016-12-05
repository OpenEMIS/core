angular
    .module('institutions.students.svc', ['kd.orm.svc'])
    .service('InstitutionsStudentsSvc', InstitutionsStudentsSvc);

InstitutionsStudentsSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc'];

function InstitutionsStudentsSvc($http, $q, $filter, KdOrmSvc) {

    var externalSource = null;
    var externalToken = null;
    var institutionId = null;
    var externalDataSourceMapping = {};

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
        getSpecialNeedTypes: getSpecialNeedTypes,
        getExternalSourceAttributes: getExternalSourceAttributes,
        getNationalityRecord: getNationalityRecord,
        getIdentityTypeRecord: getIdentityTypeRecord,
        importMappingObj: importMappingObj,
        addUserIdentity: addUserIdentity,
        addUserNationality: addUserNationality,
        getExternalSourceMapping: getExternalSourceMapping
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
        UserNationalities: 'User.UserNationalities',
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

    function getExternalSourceMapping()
    {
        return this.externalSourceMapping;
    }

    function getAccessToken()
    {
        var deferred = $q.defer();
        var vm = this;
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
                delete externalDataSourceObject.private_key;
                delete externalDataSourceObject.public_key;
                vm.externalSourceMapping = externalDataSourceObject;
                if (externalDataSourceObject.hasOwnProperty('token_uri')) {
                    var tokenUri = externalDataSourceObject.token_uri;

                    if (tokenUri != '') {
                        delete externalDataSourceObject.token_uri;
                        delete externalDataSourceObject.record_uri;
                        delete externalDataSourceObject.redirect_uri;

                        var url = angular.baseUrl + '/Configurations/generateServerAuthorisationToken?external_data_source_type=' + externalDataSourceType;
                        $http({
                            method: 'GET',
                            url: url,
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                        }).then(function (jwt) {
                            var postData = 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer';
                            postData = postData + '&assertion=' + jwt.data;
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

    function getExternalSourceAttributes() {
        return ExternalDataSourceAttributes
            .find('attributes')
            .ajax({defer: true});
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
            console.log(sourceUrl);
            var source = sourceUrl.data;
            vm.getAccessToken()
            .then(function(token){
                var sourceUrl = null;
                if (source.length > 0) {
                    sourceUrl = source[0].value;
                    externalSource = sourceUrl;
                    externalToken = token;
                } else {
                    sourceUrl = source.record_uri;
                }
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
            var newUserRecord = {};
            var nationality = '';
            var identityType = '';
            var genderName = '';
            var identityNumber = '';
            vm.getExternalSourceAttributes()
            .then(function(attributes) {
                var attr = attributes.data;

                // Mandatory information from the form
                newUserRecord['academic_period_id'] = userRecord['academic_period_id'];
                newUserRecord['education_grade_id'] = userRecord['education_grade_id'];
                newUserRecord['start_date'] = userRecord['start_date'];


                newUserRecord['first_name'] = userRecord[attr['first_name_mapping']];
                newUserRecord['last_name'] = userRecord[attr['last_name_mapping']];
                newUserRecord['date_of_birth'] = userRecord[attr['date_of_birth_mapping']];
                newUserRecord['external_reference'] = userRecord[attr['external_reference_mapping']];
                genderName = userRecord[attr['gender_mapping']];
                // By auto generated
                newUserRecord['openemis_no'] = userRecord['openemis_no'];

                // Optional fields
                if (typeof userRecord[attr['middle_name_mapping']] != 'undefined') {
                    newUserRecord['middle_name'] = userRecord[attr['middle_name_mapping']];
                }
                if (typeof userRecord[attr['third_name_mapping']] != 'undefined') {
                    newUserRecord['third_name'] = userRecord[attr['third_name_mapping']];
                }
                if (typeof userRecord[attr['identity_number_mapping']] != 'undefined') {
                    identityNumber = userRecord[attr['identity_number_mapping']];
                }
                if (typeof userRecord[attr['nationality_mapping']] != 'undefined') {
                    nationality = userRecord[attr['nationality_mapping']];
                }
                if (typeof userRecord[attr['identity_type_mapping']] != 'undefined') {
                    identityType = userRecord[attr['identity_type_mapping']];
                }

                vm.getUserRecord(newUserRecord['external_reference'])
                .then(function(response) {
                    if (response.data.length > 0) {
                        userData = response.data[0];
                        modifiedUser = userData;
                        delete modifiedUser['openemis_no'];
                        modifiedUser['is_student'] = 1;
                        modifiedUser['academic_period_id'] = userRecord['academic_period_id'];
                        modifiedUser['education_grade_id'] = userRecord['education_grade_id'];
                        modifiedUser['start_date'] = userRecord['start_date'];
                        StudentUser.save(modifiedUser)
                        .then(function(response) {
                            deferred.resolve([response.data, userData]);
                        }, function(error) {
                            deferred.reject(error);
                            console.log(error);
                        });
                    } else {

                        newUserRecord['date_of_birth'] = vm.formatDate(newUserRecord['date_of_birth']);
                        newUserRecord['is_student'] = 1;

                        vm.importMappingObj(genderName, nationality, identityType)
                        .then(function(promiseArr) {
                            newUserRecord['gender_id'] = promiseArr[0];
                            newUserRecord['nationality_id'] = promiseArr[1];
                            var identityTypeId = promiseArr[2];
                            StudentUser.reset();
                            StudentUser.save(newUserRecord)
                            .then(function(studentRecord) {
                                var userEntity = studentRecord.data.data;
                                var userEntityError = studentRecord.data.error;
                                if (userEntityError.length > 0) {
                                    deferred.resolve(studentRecord.data);
                                } else {
                                    var userId = userEntity.id;
                                    var promises = [];
                                    // Import identity
                                    if (identityTypeId != null && identityNumber != null && identityNumber != '') {
                                        vm.addUserIdentity(userId, identityTypeId, identityNumber);
                                    }
                                    // Import nationality
                                    if (userEntity.nationality_id != null) {
                                        var nationalityId = userEntity.nationality_id;
                                        vm.addUserNationality(userId, nationalityId);
                                    }
                                    deferred.resolve([studentRecord.data, {}]);
                                }
                            }, function(error) {
                                deferred.reject(error);
                                console.log(error);
                            });
                        }, function(error) {
                            deferred.reject(error);
                            console.log(error);
                        });

                    }
                }, function(error) {
                    deferred.reject(error);
                    console.log(error);
                });
            }, function(error) {
                deferred.reject(error);
            });
        }
        return deferred.promise;
    };

    function getUserRecord(externalRef)
    {
        return StudentUser
            .select()
            .where({
                'external_reference': externalRef
            })
            .contain(['Genders', 'Identities.IdentityTypes'])
            .find('enrolledInstitutionStudents')
            .ajax({defer: true});
    };

    function addUserNationality(userId, nationalityId)
    {
        var data = {};
        data['security_user_id'] = userId;
        data['nationality_id'] = nationalityId;
        return UserNationalities
            .save(data);
    }

    function addUserIdentity(userId, identityTypeId, identityNumber)
    {
        var data = {};
        data['security_user_id'] = userId;
        data['identity_type_id'] = identityTypeId;
        data['number'] = identityNumber;
        return Identities
            .save(data);
    }

    function importMappingObj(genderName, nationalityName, identityTypeName)
    {
        var promises = [];
        promises.push(this.getGenderRecord(genderName));
        promises.push(this.getNationalityRecord(nationalityName));
        promises.push(this.getIdentityTypeRecord(identityTypeName));
        return $q.all(promises);

    }

    function getIdentityTypeRecord(name)
    {
        var deferred = $q.defer();

        if (typeof name == 'undefined' || name == '') {
            deferred.resolve(null);
        } else {
            IdentityTypes
                .select()
                .where({
                    'name': name
                })
                .ajax({defer: true})
                .then(function(response) {
                    if (response.data.length > 0) {
                        deferred.resolve(response.data[0].id);
                    } else {
                        var data = {};
                        data['name'] = name;
                        data['visible'] = 1;
                        data['editable'] = 1;
                        IdentityTypes.reset();
                        IdentityTypes.save(data)
                        .then(function(res) {
                            deferred.resolve(res.data.data.id);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    }
                }, function(error) {
                    deferred.reject(error);
                });
        }

        return deferred.promise;
    }

    function getNationalityRecord(name)
    {
        var deferred = $q.defer();
        if (typeof name == 'undefined' || name == '') {
            deferred.resolve(null);
        } else {
            Nationalities
                .select()
                .where({
                    'name': name
                })
                .ajax({defer: true})
                .then(function(response) {
                    if (response.data.length > 0) {
                        deferred.resolve(response.data[0].id);
                    } else {
                        var data = {};
                        data['name'] = name;
                        data['visible'] = 1;
                        data['editable'] = 1;
                        Nationalities.reset();
                        Nationalities.save(data)
                        .then(function(res) {
                            deferred.resolve(res.data.data.id);
                        }, function(error) {
                            deferred.reject(error);
                        });
                    }
                }, function(error) {
                    deferred.reject(error);
                });
        }
        return deferred.promise;
    };

    function getGenderRecord(name)
    {
        var deferred = $q.defer();
        Genders
            .select()
            .where({
                'name': name
            })
            .ajax({defer: true})
            .then(function(response) {
                if (response.data.length > 0) {
                    deferred.resolve(response.data[0].id);
                } else {
                    var data = {};
                    data['name'] = name;
                    data['code'] = name;
                    Genders.reset();
                    Genders.save(data)
                    .then(function(res) {
                        deferred.resolve(res.data.data.id);
                    }, function(error) {
                        deferred.reject(error);
                    });
                }
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
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
        if (datetime !== undefined && datetime != '') {
            var dateArr = datetime.split('-');
            return dateArr[2] + '-' + dateArr[1] + '-' + dateArr[0];
        } else {
            return '';
        }

    }

    function formatDate(datetime) {
        return this.formatDateReverse(datetime);
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
