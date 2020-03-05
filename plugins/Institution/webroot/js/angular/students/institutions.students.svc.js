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
        getAcademicPeriods: getAcademicPeriods,
        getEducationGrades: getEducationGrades,
        getClasses: getClasses,
        getColumnDefs: getColumnDefs,
        getStudentData: getStudentData,
        postEnrolledStudent: postEnrolledStudent,
        addStudentTransferRequest: addStudentTransferRequest,
        getExternalSourceUrl: getExternalSourceUrl,
        addUser: addUser,
        makeDate: makeDate,
        formatDate: formatDate,
        formatDateForSaving: formatDateForSaving,
        getUserRecord: getUserRecord,
        getGenderRecord: getGenderRecord,
        getInternalIdentityTypes: getInternalIdentityTypes,
        addIdentityType: addIdentityType,
        setExternalSourceUrl: setExternalSourceUrl,
        resetExternalVariable: resetExternalVariable,
        getGenders: getGenders,
        getUniqueOpenEmisId: getUniqueOpenEmisId,
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
        getExternalSourceMapping: getExternalSourceMapping,
        generatePassword: generatePassword,
        translate: translate,
        getStudentTransferReasons: getStudentTransferReasons
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
        SpecialNeedTypes: 'SpecialNeeds.SpecialNeedsTypes',
        StudentTransferIn: 'Institution.StudentTransferIn',
        StudentTransferReasons: 'Student.StudentTransferReasons'
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

    function translate(data) {
        KdOrmSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function resetExternalVariable()
    {
        externalSource = null;
        externalToken = null;
    }

    function getExternalSourceMapping()
    {
        return this.externalSourceMapping;
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

        vm.getExternalSourceAttributes()
        .then(function(attributes) {
            var attr = attributes.data;
            delete attr.private_key;
            delete attr.public_key;
            vm.externalSourceMapping = attr;
            var url = angular.baseUrl + '/Configurations/getExternalUsers?page={page}&limit={limit}&first_name={first_name}&last_name={last_name}&identity_number={identity_number}&date_of_birth={date_of_birth}';
            var pageParams = {
                limit: options['endRow'] - options['startRow'],
                page: options['endRow'] / (options['endRow'] - options['startRow']),
            };

            var params = {};
            params['super_admin'] = 0;

            // Get url from user input
            var replaceURL = url;
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
                            params[key] = vm.formatDateForSaving(options['conditions'][key]);
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
            externalSource = true;

            var opt = {
                method: 'GET'
            }
            return KdOrmSvc.customAjax(url, opt);
        }, function(error) {
            deferred.reject(error);
        })
        .then(function(response) {
            deferred.resolve(response);
        }, function(error) {
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
                            options['conditions'][key] = vm.formatDate(options['conditions'][key]);
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
        StudentUser.contain(['Genders', 'MainIdentityTypes', 'MainNationalities']);

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
            userRecord['start_date'] = vm.formatDateForSaving(userRecord['start_date']);
            userRecord['institution_id'] = vm.getInstitutionId();
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
                newUserRecord['start_date'] = vm.formatDateForSaving(userRecord['start_date']);
                newUserRecord['institution_id'] = vm.getInstitutionId();

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
                if (typeof userRecord[attr['address_mapping']] != 'undefined') {
                    newUserRecord['address'] = userRecord[attr['address_mapping']];
                }
                if (typeof userRecord[attr['postal_mapping']] != 'undefined') {
                    newUserRecord['postal_code'] = userRecord[attr['postal_mapping']];
                }

                vm.getUserRecord(newUserRecord['external_reference'])
                .then(function(response) {
                    if (response.data.length > 0) {
                        userData = response.data[0];
                        modifiedUser = userData;
                        delete modifiedUser['openemis_no'];
                        delete modifiedUser['created'];
                        delete modifiedUser['modified'];
                        modifiedUser['is_student'] = 1;
                        modifiedUser['institution_id'] = vm.getInstitutionId();
                        modifiedUser['academic_period_id'] = userRecord['academic_period_id'];
                        modifiedUser['education_grade_id'] = userRecord['education_grade_id'];
                        modifiedUser['start_date'] = vm.formatDateForSaving(userRecord['start_date']);
                        StudentUser.edit(modifiedUser)
                        .then(function(response) {
                            deferred.resolve([response.data, userData]);
                        }, function(error) {
                            deferred.reject(error);
                            console.log(error);
                        });
                    } else {

                        newUserRecord['date_of_birth'] = vm.formatDateForSaving(newUserRecord['date_of_birth']);
                        newUserRecord['is_student'] = 1;

                        vm.importMappingObj(genderName, nationality, identityType)
                        .then(function(promiseArr) {
                            delete newUserRecord['nationality_id'];
                            delete newUserRecord['identity_type_id'];
                            newUserRecord['gender_id'] = promiseArr[0];
                            newUserRecord['nationality_id'] = promiseArr[1];
                            newUserRecord['identity_type_id'] = promiseArr[2];
                            newUserRecord['username'] = newUserRecord['openemis_no'];
                            delete newUserRecord['password'];
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
        data['start_date'] = this.formatDateForSaving(data['start_date']);
        data['end_date'] = this.formatDateForSaving(data['end_date']);
        data['status_id'] = 0;
        data['assignee_id'] = -1;
        data['institution_id'] = institutionId;
        return StudentRecords.save(data);
    };

    function addStudentTransferRequest(data) {
        data['start_date'] = this.formatDateForSaving(data['start_date']);
        data['end_date'] = this.formatDateForSaving(data['end_date']);
        return StudentTransferIn.save(data);
    };

    function setInstitutionId(id) {
        this.institutionId = id;
    }

    function getInstitutionId() {
        return this.institutionId;
    };

    function makeDate(datetime) {
        // Only get the date part, we do not require the time portion
        if (datetime.indexOf('T') > -1) {
            datetime = datetime.split('T')[0];
        }
        // Logic to handle external datasource giving the datetime in this format 2005-07-08T11:22:33+0800
        if (datetime !== undefined && datetime != '' && datetime.indexOf('-') > -1) {
            var date = datetime.split('-');
            if (date[0].length == 4) {
                // To fix timezone offset issue between server and client machine
                var offset = new Date(Date.parse(datetime)).getTimezoneOffset() * 60000;
                date = new Date(Date.parse(datetime) + offset);
            } else {
                date = new Date(date[2], date[1]-1, date[0]);
            }
            return date;
        } else {
            return null;
        }
    }

    function formatDate(datetime) {
        var date = this.makeDate(datetime);
        if (date != null) {
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = date.getDate().toString();

            return (dd[1]?dd:"0"+dd[0]) + '-' + (mm[1]?mm:"0"+mm[0])  + '-' +   yyyy;
        } else {
            return '';
        }
    };

    function formatDateForSaving(datetime) {
        var date = this.makeDate(datetime);
        if (date != null) {
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = date.getDate().toString();

            return yyyy + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + (dd[1]?dd:"0"+dd[0]);
        } else {
            return '';
        }
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

    function generatePassword() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getAutoGeneratedPassword';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.password);
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
            .order(['Nationalities.order'])
            .ajax({defer: true});
    }

    function getSpecialNeedTypes() {
        return SpecialNeedTypes
            .select()
            .ajax({defer: true});
    }

    function getStudentTransferReasons() {
        return StudentTransferReasons
            .select()
            .ajax({defer: true});
    }
};
