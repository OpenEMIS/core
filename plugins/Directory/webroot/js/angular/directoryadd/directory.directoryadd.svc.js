angular
    .module('directory.directoryadd.svc', ['kd.orm.svc', 'aggrid.locale.svc', 'alert.svc'])
    .service('DirectoryaddSvc', DirectoryaddSvc);

DirectoryaddSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc','AggridLocaleSvc', 'AlertSvc', 'UtilsSvc', '$window'];

function DirectoryaddSvc($http, $q, $filter, KdOrmSvc, AggridLocaleSvc, AlertSvc, UtilsSvc, $window) {
    // POCOR-9427 start
    const USER_TYPES = {
        STUDENT: 1,
        STAFF: 2,
        GUARDIAN: 3,
        OTHER: 4
    };

    const USER_TYPE_KEYS = {
        [USER_TYPES.STUDENT]: 'student',
        [USER_TYPES.STAFF]: 'staff',
        [USER_TYPES.GUARDIAN]: 'guardian',
        [USER_TYPES.OTHER]: 'other'
    };
    // POCOR-9427 end

    var models = {
        Genders: 'User.Genders',
        Nationalities: 'FieldOption.Nationalities', // POCOR-9427
        ConfigItems: 'Configuration.ConfigItems' // POCOR-9427
    };
    var service = {
        init: init,
        // user details function
        getUserTypes: getUserTypes,
        setUserTypes: setUserTypes,
        getGenders: getGenders,
        setGenders: setGenders,
        getNationalities: getNationalities,
        setNationalities: setNationalities,
        getIdentityTypes: getIdentityTypes,
        setIdentityTypes: setIdentityTypes,
        getContactTypes: getContactTypes,
        setContactTypes: setContactTypes,
        getRelationType: getRelationType,
        setError: setError,
        unsetError: unsetError,
        unsetAllErrors: unsetAllErrors,
        setName: setName,
        changeGender: changeGender,
        changeUserType: changeUserType,
        changeRelationType: changeRelationType,
        changeNationality: changeNationality,
        changeIdentityType: changeIdentityType,
        changeIdentityNumber: changeIdentityNumber,
        changeDateOfBirth: changeDateOfBirth,
        changeContactType: changeContactType,
        changeContactValue: changeContactValue,
        validateUserDetails: validateUserDetails,
        validateConfirmDetails: validateConfirmDetails,
        checkUserAlreadyExistByIdentity: checkUserAlreadyExistByIdentity,
        checkUserAlreadyExistByIdentityWithoutWarning:checkUserAlreadyExistByIdentityWithoutWarning,
        checkUserExistByIdentity: checkUserExistByIdentity,
        // checkUserDetailValidationBlocksHasError,
        // end user details function

        //confirm user functions
        getUniqueOpenEmisId: getUniqueOpenEmisId,
        setUniqueOpenEmisId: setUniqueOpenEmisId,
        generatePassword: generatePassword,
        setPassword: setPassword,
        //end confirm user functions
        goToInternalSearch: goToInternalSearch,
        goToExternalSearch: goToExternalSearch,
        getInternalSearchData: getInternalSearchData,
        getExternalSearchData: getExternalSearchData,
        getStudentCustomFields: getStudentCustomFields,
        getStaffCustomFields: getStaffCustomFields,
        createCustomFieldsArray: createCustomFieldsArray,
        getAddressAreaId: getAddressAreaId,
        getAddressArea: getAddressArea,
        getBirthplaceAreaId: getBirthplaceAreaId,
        getBirthplaceArea: getBirthplaceArea,
        saveDirectoryData: saveDirectoryData,
        saveGuardianData: saveGuardianData,
        getCspdData: getCspdData,
        getRedirectToGuardian: getRedirectToGuardian,
        isNextButtonShouldDisable: isNextButtonShouldDisable,
        getConfigItemValue: getConfigItemValue, // POCOR-9427
    };
    return service;

    function init(baseUrl){
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Directory');
        KdOrmSvc.init(models);
    }

    function getUniqueOpenEmisId() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getUniqueOpenemisId/';
        // console.log(url);
        $http.get(url)
        .then(function(response){
            // console.log("response");
            // console.log(response);
            deferred.resolve(response.data.openemis_no);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 to set Openemis No
    function setUniqueOpenEmisId(scope) {
        // if OpenEmisNo is present
        if (
            (scope.isExternalSearchSelected || scope.isInternalSearchSelected) &&
            scope.selectedUserData.openemis_no &&
            scope.selectedUserData.openemis_no.toString().trim() !== ''
        ) {
            // if UserName is absent
            if (!scope.selectedUserData.username) {
                scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
            }
            return Promise.resolve();
        }

        if ((scope.isExternalSearchSelected || scope.isInternalSearchSelected) &&
            scope.selectedUserData.openemis_no &&
            scope.selectedUserData.openemis_no.toString() !== '')
        {
            // if UserName is absent
            if (!scope.selectedUserData.username) {
                scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
            }
            return Promise.resolve();
        }

        return getUniqueOpenEmisId()
            .then(response => {
                scope.selectedUserData.openemis_no = response;
                scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
            })
            .catch(error => {
                console.error(error);
            });
    }


    function generatePassword() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getAutoGeneratedPassword/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.password);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 for Ctrl.js
    function setPassword(scope) {
        if (scope.selectedUserData.password) {
            // if no pswd only
            return Promise.resolve();
        }
        return generatePassword()
            .then(response => {
                scope.selectedUserData.password = response;
            })
            .catch(error => {
                console.error(error);
            });
    }

    function getUserTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getUserType/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 for Ctrl.js
    function setUserTypes(scope) {
        return this.getUserTypes()
            .then(resp => {
                scope.userTypeOptions = resp.data;
            });
    }

    function getGenders() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getGenders/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 for Ctrl.js
    function setGenders(scope) {
        return this.getGenders()
            .then(resp => {
                scope.genderOptions = resp.data;
                scope.basicFieldsRequired = true;
            });
    }

    function getNationalities() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getNationalities/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 for Ctrl.js
    function setNationalities(scope) {
        return this.getNationalities()
            .then(resp => {
                scope.nationalitiesOptions = resp.data;
            });
    }

    function getIdentityTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getIdentityTypes/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    // POCOR-8231 for Ctrl.js
    function setIdentityTypes(scope) {
        return this.getIdentityTypes()
            .then(resp => {
                scope.identityTypeOptions = resp.data;
            });
    }

    function getContactTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getContactType/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function setContactTypes(scope) {
        return this.getContactTypes()
            .then(resp => {
                scope.contactTypeOptions = resp.data;
            });
    }

    function getRelationType() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getRelationshipType';
        $http.get(url)
            .then(function(response){
                deferred.resolve(response);
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
    };

    // POCOR-8231 for Ctrl.js
    function setName(scope) {
        const unsetFields = ['first_name', 'middle_name', 'third_name', 'last_name'];
        unsetFields.forEach(field => unsetError(scope.error, field));

        const appendName = (dataObj, variableName) => {
            if (dataObj.hasOwnProperty(variableName)) {
                const value = dataObj[variableName].trim();
                if (value) {
                    dataObj.name += ` ${value}`;
                }
            }
        };

        const userData = scope.selectedUserData;
        userData.name = userData.first_name?.trim() || '';

        ['middle_name', 'third_name', 'last_name'].forEach(field => appendName(userData, field));
    }

    function changeGender(scope) {
        unsetError(scope.error, 'gender_id');
        var userData = scope.selectedUserData;
        if (userData.hasOwnProperty('gender_id')) {
            var genderOptions = scope.genderOptions;
            for (var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == userData.gender_id) {
                    userData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            scope.selectedUserData = userData;
        }
    }

    function changeUserType(scope) {
        const {selectedUserData, userTypeOptions, error} = scope;

        if (!selectedUserData.hasOwnProperty('user_type_id')) {
            return;
        }

        unsetError(error, 'user_type_id');

        const userType = userTypeOptions.find(option => option.id === selectedUserData.user_type_id);

        if (userType) {
            selectedUserData.userType = {name: userType.name};
            scope.selectedUserData = selectedUserData;
        }
    }

    function changeRelationType(scope) {
        const {selectedUserData, relationTypeOptions, error} = scope;

        if (!selectedUserData.hasOwnProperty('relation_type_id')) {
            return;
        }

        unsetError(error, 'relation_type_id');

        const relationType = relationTypeOptions.find(option => option.id === selectedUserData.relation_type_id);

        if (relationType) {
            selectedUserData.relationType = {name: relationType.name};
            scope.selectedUserData = selectedUserData;
        }
    }

    function changeNationality(scope) {
        unsetError(scope.error, 'nationality_id');

        const {selectedUserData, nationalitiesOptions, identityTypeOptions} = scope;
        const nationalityId = selectedUserData.nationality_id;

        if (nationalityId === null) {
            selectedUserData.nationality_name = "";
            selectedUserData.nationality_id = null;
            selectedUserData.identity_type_name = "";
            selectedUserData.identity_type_id = null;
            selectedUserData.identity_number = null;
            scope.isExternalSearchEnable = false;
            scope.externalSearchSourceName = "";
        } else {
            const nationality = nationalitiesOptions.find(option => option.id === nationalityId);

            if (nationality) {
                if (nationality.identity_type_id === null) {
                    selectedUserData.identity_type_id = identityTypeOptions[0].id;
                    selectedUserData.identity_type_name = identityTypeOptions[0].name;
                } else {
                    selectedUserData.identity_type_id = nationality.identity_type_id;
                    selectedUserData.identity_type_name = nationality.identity_type_name;
                }
                selectedUserData.nationality_name = nationality.name;
                selectedUserData.identity_number = null;
            }
        }
        const {nationality_id, identity_type_id, identity_number} = scope.selectedUserData;
        if (nationality_id && identity_type_id && identity_number) {
            unsetAllErrors(scope);
        }

        setConfigForExternalSearch(scope);
    }

    function setConfigForExternalSearch(scope) {
        var identity_type_id = scope.selectedUserData.identity_type_id;
        var nationality_id = scope.selectedUserData.nationality_id;
        scope.isExternalSearchEnable = false;

        checkConfigForExternalSearch(nationality_id, identity_type_id)
            .then((resp) => {
                scope.isExternalSearchEnable = resp.showExternalSearch;
                // console.log(resp);
                scope.externalSearchSourceName = resp.value;
                if(scope.externalSearchSourceName === 'OpenEMIS Core') {
                    scope.basicFieldsRequired = false;
                }
                if(scope.externalSearchSourceName === 'Seychelles Civil Status') { // POCOR-9481
                    scope.basicFieldsRequired = false;
                }
                if(scope.externalSearchSourceName === 'UNHCR') {
                    scope.basicFieldsRequired = false;
                }
                UtilsSvc.isAppendLoader(false);
            })
            .catch((error) => {
                scope.isExternalSearchEnable = false;
                console.error(error);
                UtilsSvc.isAppendLoader(false);
            });

    }

    function changeIdentityType(scope) {
        unsetError(scope.error, 'identity_type_id');
        const {selectedUserData, identityTypeOptions} = scope;
        const identityTypeId = selectedUserData.identity_type_id;

        if (identityTypeId === null) {
            selectedUserData.identity_number = '';
            selectedUserData.identity_type_name = '';
            scope.canSkipIdentity = false;
            return;
        }

        const identityType = identityTypeOptions.find(option => option.id === identityTypeId);

        if (identityType) {
            selectedUserData.identity_type_name = identityType.name;
        }
        const hasValidIdentityNumber = validateIdentityNumberByIdentityType(scope);
        const {nationality_id, identity_type_id, identity_number} = scope.selectedUserData;
        if (hasValidIdentityNumber && nationality_id && identity_type_id && identity_number) {
            unsetAllErrors(scope);
        }
        setConfigForExternalSearch(scope);
    }

    function getIdentityTypeValidationPattern(scope, identityTypeId) {
        if (!scope || !Array.isArray(scope.identityTypeOptions) || !identityTypeId) {
            return '';
        }

        const identityType = scope.identityTypeOptions.find(option => Number(option.id) === Number(identityTypeId));
        return identityType && identityType.validation_pattern ? String(identityType.validation_pattern).trim() : '';
    }

    function validateIdentityNumberByIdentityType(scope) {
        unsetError(scope.error, 'identity_number');

        const {identity_type_id, identity_number} = scope.selectedUserData || {};
        if (!identity_type_id || identity_number === undefined || identity_number === null || identity_number === '') {
            return true;
        }

        const pattern = getIdentityTypeValidationPattern(scope, identity_type_id);
        if (!pattern) {
            return true;
        }

        try {
            const regex = new RegExp(pattern);
            if (!regex.test(String(identity_number))) {
                setError(scope.error, 'identity_number', 'Please enter a valid Identity Number');
                return false;
            }
        } catch (e) {
            // Ignore malformed admin regex to avoid blocking all entries.
            console.warn('Invalid identity validation_pattern:', pattern, e);
        }

        return true;
    }

    function changeIdentityNumber(scope) {
        const {nationality_id, identity_type_id, identity_number} = scope.selectedUserData;
        scope.canSkipIdentity = !!(identity_number && String(identity_number).trim());
        if (identity_number) {
            unsetError(scope.error, 'identity_number');
        }
        const hasValidIdentityNumber = validateIdentityNumberByIdentityType(scope);
        if (hasValidIdentityNumber && nationality_id && identity_type_id && identity_number) {
            unsetAllErrors(scope);
        }
    }

    function changeContactValue(scope) {
        this.unsetError('contact_value');
    }

    function changeDateOfBirth(scope) {
        if (scope.selectedUserData.date_of_birth) {
            this.unsetError(scope.error, 'date_of_birth');
        }
    }

    function changeContactType(scope) {
        var contactType = scope.selectedUserData.contact_type_id;
        var options = scope.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactType) {
                scope.selectedUserData.contact_type_name = options[i].name;
                break;
            }
        }
    }

    async function validateUserDetails(scope) {
        scope.error = {};
        // POCOR-9427 start
        const userTypeId = scope.selectedUserData.user_type_id;
        // console.log(scope);
        const configKey = USER_TYPE_KEYS[userTypeId];
        const config = scope.config[configKey] || {};
        // console.log(scope.selectedUserData)
        // console.log(configKey)
        // console.log(scope.config)
        // console.log(config)
        const checkAndSetError = (field, message) => {
            const value = scope.selectedUserData[field];
            if (value === '' || value === undefined || value === null) {
                scope.error[field] = message;
            }
        };

        // Validate user type and relation type if options exist
        if (scope.userTypeOptions) {
            checkAndSetError('user_type_id', 'This field cannot be left empty');
        }
        if (scope.relationTypeOptions) {
            checkAndSetError('relation_type_id', 'This field cannot be left empty');
        }

        if (Object.keys(scope.error).length > 0) return;

        if (scope.step === 'user_details') {
            const [blockName, hasError] = await checkUserDetailValidationBlocksHasError(scope);

            if (blockName === 'Identity' && hasError) {
                // if (!config.nationalitySkipped && config.nationalitiesRequired === 'required') {
                    checkAndSetError('nationality_id', 'This field cannot be left empty');
                // }
                // if (!config.identitySkipped && config.identitiesRequired === 'required') {
                    checkAndSetError('identity_type_id', 'This field cannot be left empty');
                    checkAndSetError('identity_number', 'This field cannot be left empty');
                // }
            } else if (blockName === 'General_Info' && hasError) {
                checkAndSetError('first_name', 'This field cannot be left empty');
                checkAndSetError('last_name', 'This field cannot be left empty');
                checkAndSetError('gender_id', 'This field cannot be left empty');
                checkAndSetError('date_of_birth', 'This field cannot be left empty');

                if (scope.selectedUserData.date_of_birth) {
                    scope.selectedUserData.date_of_birth = $filter('date')(scope.selectedUserData.date_of_birth, 'yyyy-MM-dd');
                }
            }

            if (!validateIdentityNumberByIdentityType(scope)) {
                return;
            }

            if (Object.keys(scope.error).length > 0) return;
            if (scope.selectedUserData.identity_number) {
                scope.canSkipIdentity = true;
            }
            if (scope.selectedUserData.nationality_id) {
                scope.canSkipNationality = true;
            }
            // Move to next step
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
        }
        // POCOR-9427 end
    }


    //POCOR-9590: shared reset for the External Search step.
    //All four wizards (Directory / Student / Staff / Guardian) call
    //directorySvc.goToExternalSearch(scope), so wiping the prior grid state
    //here re-arms the search for everyone in one place. Without this, going
    //Back to change the identity inputs and clicking Next again would either
    //show the cached first hit or, after a single page returns, leave the
    //grid stuck in "no more pages" mode for every subsequent identity.
    function resetExternalSearchGrid(scope) {
        var prior = scope.externalGridOptions;
        if (prior && prior.api) {
            if (typeof prior.api.purgeInfiniteCache === 'function') {
                try { prior.api.purgeInfiniteCache(); } catch (e) { /* api torn down */ }
            }
            if (typeof prior.api.setRowData === 'function') {
                try { prior.api.setRowData([]); } catch (e) { /* infinite model */ }
            }
        }
        scope.rowsThisPage = [];
        scope.isExternalSearchSelected = false;
        if ('selectedGuardian' in scope) {
            scope.selectedGuardian = undefined;
        }
    }

    function goToExternalSearch(scope) {
        resetExternalSearchGrid(scope);
        UtilsSvc.isAppendLoader(true);
        var externalSearchParams = {
            first_name: scope.selectedUserData.first_name,
            last_name: scope.selectedUserData.last_name,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            openemis_no: scope.selectedUserData.openemis_no,
            nationality_id: scope.selectedUserData.nationality_id,
            search_type: scope.externalSearchSourceName,
        }
        // console.log(externalSearchParams);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
                    enableColResize: true,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: function (_e) {
                        var id = _e.node.data.id;
                        scope.selectUserFromExternalSearch(id);
                        scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
    };
                setTimeout(function () {
                    // scope.getExternalSearchData();
                    if (scope.externalSearchSourceName === 'Jordan CSPD') {
                        scope.getCSPDSearchData();
                    } else {
                        setExternalSearchData(scope);
                    }
                }, 1500);
            }, function (error) {
                scope.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
                    enableColResize: true,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: function (_e) {
                        var id = _e.node.data.id;
                        scope.selectUserFromExternalSearch(id);
                        scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.externalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    if (scope.externalSearchSourceName === 'Jordan CSPD') {
                        scope.getCSPDSearchData();
                    } else {
                        setExternalSearchData(scope);
                    }
                }, 1500);
            });
    }

    function goToInternalSearch(scope) {
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: angular.isDefined(scope.dynamicOpenemisNoHeader) ? scope.dynamicOpenemisNoHeader : scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.account_type,
                            field: "account_type",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
                    enableColResize: true,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: function (_e) {
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectUserFromInternalSearch(_e.node.data.id);
                        scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    setInternalSearchData(scope);
                }, 1500);
            }, function (error) {
                scope.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: scope.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: scope.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    enableColResize: false,
                    enableFilter: false,
                    enableServerSideFilter: true,
                    enableServerSideSorting: true,
                    enableSorting: false,
                    headerHeight: 38,
                    rowData: [],
                    rowHeight: 38,
                    rowModelType: 'infinite',
                    // Removed options - Issues in ag-Grid AG-828
                    // suppressCellSelection: true,

                    // Added options
                    suppressContextMenu: true,
                    stopEditingWhenGridLosesFocus: true,
                    ensureDomOrder: true,
                    pagination: true,
                    paginationPageSize: 10,
                    maxBlocksInCache: 1,
                    cacheBlockSize: 10,
                    // angularCompileRows: true,
                    onRowSelected: function (_e) {
                        scope.isInternalSearchSelected = true;
                        scope.isExternalSearchSelected = false;
                        scope.selectUserFromInternalSearch(_e.node.data.id);
                        scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                    onGridReady: function () {
                        if (angular.isDefined(scope.internalGridOptions.api)) {
                            setTimeout(function () {
                                scope.setGridData();
                            })
                        }
                    },
                };
                setTimeout(function () {
                    scope.getInternalSearchData();
                }, 1500);
            });
    }

    async function validateConfirmDetails(scope) {
        // POCOR-9427 start
        scope.error = {};
        let isCustomFieldNotValidated = false;

        const selectedUserData = scope.selectedUserData;
        const userTypeId = selectedUserData.user_type_id;
        const configKey = USER_TYPE_KEYS[userTypeId]; // from USER_TYPES mapping
        const config = scope.config[configKey] || {};

        const checkAndSetError = (field, message) => {
            const value = selectedUserData[field];
            if (value === '' || value === undefined || value === null) {
                scope.error[field] = message;
            }
        };
        const setError = (field, message) => {
                scope.error[field] = message;
        };
        const user_exists = await checkUserAlreadyExistByIdentity(scope);
        if(!scope.isInternalSearchSelected && user_exists){
                setError('identity_type_id', 'User already exist with this nationality');
                setError('identity_number', 'User already exist with this identity');
                setError('nationality_id', 'User already exist with this identity type');
        }

        // 🧾 Username / Password check
        if (!scope.isInternalSearchSelected) {
            checkAndSetError('username', 'This field cannot be left empty');
            checkAndSetError('password', 'This field cannot be left empty');
        }

        // 🧾 Email and Mobile Number (from config)
        if (!config.email_skipped && config.email_required === 'required') {
            checkAndSetError('email', 'This field cannot be left empty');
        }
        if (!config.mobile_number_skipped && config.mobile_number_required === 'required') {
            checkAndSetError('mobile_number', 'This field cannot be left empty');
        }

        // 🧾 Identity + Nationality (re-validation based on config)
        if (!config.identitySkipped && config.identitiesRequired === 'required') {
            checkAndSetError('identity_type_id', 'This field cannot be left empty');
            checkAndSetError('identity_number', 'This field cannot be left empty');
        }
        if (!config.nationalitySkipped && config.nationalitiesRequired === 'required') {
            checkAndSetError('nationality_id', 'This field cannot be left empty');
        }

        if (!validateIdentityNumberByIdentityType(scope)) {
            return;
        }

        // 🧾 Custom Fields
        if (scope.customFieldsArray) {
            scope.customFieldsArray.forEach((customField) => {
                customField.data.forEach((field) => {
                    if (field.is_mandatory === 1) {
                        const needsAnswer = ['TEXT', 'TEXTAREA', 'NOTE', 'DROPDOWN', 'NUMBER', 'DECIMAL', 'DATE', 'TIME'].includes(field.field_type);
                        const needsNonEmptyArray = field.field_type === 'CHECKBOX';
                        if ((needsAnswer && !field.answer) || (needsNonEmptyArray && (!Array.isArray(field.answer) || field.answer.length === 0))) {
                            scope.error[field.name] = 'This field is required.';
                            field.errorMessage = 'This field is required.';
                            isCustomFieldNotValidated = true;
                        }
                    }
                });
            });
        }

        if (isCustomFieldNotValidated || Object.keys(scope.error).length > 0) {
            return;
        }
        // POCOR-9427 end
        scope.saveDetails();
    }

    async function checkUserAlreadyExistByIdentity(scope) {
        const userData = scope.selectedUserData;
        // console.log(scope);
        const result = await checkUserExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
        });

        if (result.data.user_exist === 1) {
            scope.messageClass = 'alert_warn';
            scope.message = result.data.message;
            scope.isIdentityUserExist = true;
        } else {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
        }
        return result.data.user_exist === 1;
    }
    async function checkUserAlreadyExistByIdentityWithoutWarning(scope) {
        const userData = scope.selectedUserData;
        // console.log(scope);
        const result = await checkUserExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
        });

        return result.data.user_exist === 1;
    }

    async function checkUserDetailValidationBlocksHasError(scope) {
        const {
            first_name,
            last_name,
            gender_id,
            date_of_birth,
            identity_type_id,
            identity_number,
            openemis_no,
            nationality_id,
            identity_type_name
        } = scope.selectedUserData;
        const externalSearchSourceName = scope.externalSearchSourceName;
        const user_exists = await checkUserAlreadyExistByIdentity(scope);

        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth);
        const isIdentityHasError = (identity_number?.length > 1 || nationality_id || identity_type_id) &&
            (!identity_number || !nationality_id || !identity_type_id);
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        let isSkipableForIdentity = identity_number?.length > 1 && nationality_id > 0 && identity_type_id > 0;

        if (identity_type_name === 'UNHCR') {
            isSkipableForIdentity = false;
        }

        if (isOpenEmisNoHasError) {
            return ["OpenEMIS_ID", false];
        }

        if (isIdentityHasError) {
            return ['Identity', true];
        }

        if (isSkipableForIdentity) {
            if (user_exists === true) {
                return ['Identity', false];
        }
            if (externalSearchSourceName === 'OpenEMIS Core') {
                return ['Identity', false];
            }
            if (externalSearchSourceName === 'Seychelles Civil Status') {
                return ['Identity', false];
            }
        }

        if (isGeneralInfodHasError) {
            return ["General_Info", true];
        }

        return ["", false];
    }




    function getInternalSearchData(params) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryInternalSearch';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function setInternalSearchData(scope) {
        var first_name = '';
        var last_name = '';
        var openemis_no = null;
        var date_of_birth = '';
        var identity_number = '';

        var nationality_id = '';
        var nationality_name = '';
        var identity_type_name = '';
        var identity_type_id = '';

        first_name = scope.selectedUserData.first_name;
        last_name = scope.selectedUserData.last_name;
        date_of_birth = scope.selectedUserData.date_of_birth;
        identity_number = scope.selectedUserData.identity_number;
        openemis_no = scope.selectedUserData.openemis_no;
        nationality_id = scope.selectedUserData.nationality_id;
        nationality_name = scope.selectedUserData.nationality_name;
        identity_type_name = scope.selectedUserData.identity_type_name;
        identity_type_id = scope.selectedUserData.identity_type_id;
        let user_type_id =  scope.selectedUserData.user_type_id ?? null; // POCOR-8063
        let guardian_type_id = scope.selectedUserData.relation_type_id ?? null; // POCOR-8063
        let student_openemis_no = scope.studentOpenEmisId ?? null; // POCOR-8063
        let institution_id = scope.institutionId ?? null; // POCOR-8063
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                var param = {
                    page: params.endRow / (params.endRow - params.startRow),
                    limit: params.endRow - params.startRow,
                    first_name: first_name,
                    last_name: last_name,
                    openemis_no: openemis_no,
                    date_of_birth: date_of_birth,
                    identity_number: identity_number,
                    institution_id: institution_id,
                    user_type_id: user_type_id,
                    guardian_type_id: guardian_type_id,
                    nationality_id: nationality_id,
                    nationality_name: nationality_name,
                    identity_type_name: identity_type_name,
                    identity_type_id: identity_type_id,
                    student_openemis_no: student_openemis_no
    };

                // console.log(param);
                getInternalSearchData(param)
                    .then(function (response) {
                        var gridData = response.data.data;
                        // console.log(gridData);
                        if (!gridData)
                            gridData = [];
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        scope.isSearchResultEmpty = gridData.length === 0;
                        return scope.processGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        scope.internalGridOptions.api.setDatasource(dataSource);
        scope.internalGridOptions.api.sizeColumnsToFit();
    }

    function getExternalSearchData(params) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryExternalSearch';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function setExternalSearchData(scope) {
        var param = {
            first_name: scope.selectedUserData.first_name,
            last_name: scope.selectedUserData.last_name,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            openemis_no: scope.selectedUserData.openemis_no,
            nationality_id: scope.selectedUserData.nationality_id,
            search_type: scope.externalSearchSourceName,
        }
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                getExternalSearchData(param)
                    .then(function (response) {
                        var gridData = response.data.data;
                        // console.log(gridData);
                        if (!Array.isArray(gridData)) {
                            gridData = gridData ? [gridData] : [];
                        }
                        if (scope.externalSearchSourceName === 'UNHCR') {
                            scope.selectedUserData.identity_number = null;
                        }
                        // console.log(gridData);
                        gridData.forEach((data, idx) => {
                            if (scope.externalSearchSourceName === 'UNHCR') {
                                scope.selectedUserData.identity_number = null;
                                data.name = scope.selectedUserData.name;
                                data.gender = scope.selectedUserData.gender.name;
                                data.gender_id = scope.selectedUserData.gender_id;
                                data.nationality_id = scope.selectedUserData.nationality_id;
                                data.nationality = scope.selectedUserData.nationality_name;
                                data.identity_type = scope.selectedUserData.identity_type_name;
                                data.identity_type_id = scope.selectedUserData.identity_type_id;
                                data.first_name = scope.selectedUserData.first_name;
                                data.last_name = scope.selectedUserData.last_name;
                                data.middle_name = scope.selectedUserData.middle_name;
                                data.third_name = scope.selectedUserData.third_name;
                                data.preferred_name = scope.selectedUserData.preferred_name;
                                data.date_of_birth = scope.selectedUserData.date_of_birth;
                            } else if (scope.externalSearchSourceName === 'Seychelles Civil Status') { // POCOR-9481
                                // Seychelles returns simple fields (not nested objects)
                                data.gender_id = data['gender_id'];
                                data.gender = data['gender'];
                                data.first_name = data['first_name'];
                                data.last_name = data['last_name'];
                                data.name = data['full_name'];
                                data.date_of_birth = data['date_of_birth'];
                                data.nationality_id = data['nationality_id'];
                                data.nationality = data['nationality'];
                                data.identity_type = scope.selectedUserData.identity_type_name;
                                data.identity_type_id = scope.selectedUserData.identity_type_id;

                            } else  if (scope.externalSearchSourceName === 'OpenEMIS Core') {
                                if (Object.keys(data).length !== 0) {
                                    scope.selectedUserData.identity_number = null;
                                    data.name = data['full_name'];
                                    data.gender_id = data['gender_id'];
                                    const genderOption = scope.genderOptions.find(option => option.id == data.gender_id);
                                    if (genderOption) {
                                        data.gender = genderOption.name;
                                    }
                                    data.nationality_id = scope.selectedUserData.nationality_id;
                                    data.nationality = scope.selectedUserData.nationality_name;
                                    data.identity_type = scope.selectedUserData.identity_type_name;
                                    data.identity_type_id = scope.selectedUserData.identity_type_id;
                                    // data.identity_number = scope.selectedUserData.identity_number;
                                    data.first_name = data['first_name'];
                                    data.last_name = data['last_name'];
                                    data.middle_name = data['middle_name'];
                                    data.third_name = data['third_name'];
                                    data.date_of_birth = data['date_of_birth'];
                                }
                            } else {
                                data.gender_id = data['gender.id'];
                                data.gender = data['gender.name'];
                                data.nationality_id = data['main_nationality.id'];
                                data.nationality = data['main_nationality.name'];
                                data.identity_type = data['main_identity_type.name'];
                                data.identity_type_id = data['main_identity_type.id'];
                            }
                            data.id = idx;
                        });
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        scope.isSearchResultEmpty = gridData.length === 0;
                        return scope.processGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
    };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit();
    }

    function getRedirectToGuardian() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getRedirectToGuardian';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getStudentCustomFields(userId){
        var params = {
            student_id: userId,
        };
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/studentCustomFields';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getStaffCustomFields(staffId){
        var params = {
            staff_id: staffId,
        };
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/staffCustomFields';
        $http.post(url, {params: params})
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }




    function createCustomFieldsArray(scope) {
        if (scope.customFields === "null") return;

        function mapBySection(item) {
            return item.section;
        }

        function filterBySection(item, section) {
            return section === item.section;
        }
        // console.log(scope.customFields);
        if(scope.customFields && scope.customFields.length > 0) {
            var selectedCustomField = scope.customFields;
            var filteredSections = Array.from(new Set(scope.customFields.map((item) => mapBySection(item))));
            filteredSections.forEach((section)=>{
                let filteredArray = selectedCustomField.filter((item) => filterBySection(item, section));
                scope.customFieldsArray.push({sectionName: section , data: filteredArray});
            });
            scope.customFieldsArray.forEach((customField) => {
                customField.data.forEach((fieldData) => {
                    fieldData.answer = '';
                    fieldData.errorMessage = '';
                    if(fieldData.field_type === 'TEXT' || fieldData.field_type === 'TEXTAREA' || fieldData.field_type === 'NOTE') {
                        fieldData.answer = fieldData.values ? fieldData.values : '';
                    }
                    if(fieldData.field_type === 'DROPDOWN') {
                        fieldData.selectedOptionId = '';
                        fieldData.answer = fieldData.values && fieldData.values.length > 0 && fieldData.values[0].dropdown_val ? fieldData.values[0].dropdown_val : '';
                        fieldData.option.forEach((option) => {
                            if(option.option_id === fieldData.answer) {
                                fieldData.selectedOption = option.option_name;
                            }
                        })
                    }
                    //POCOR-9664 Changes for date picker.
                    if(fieldData.field_type === 'DATE') {
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        fieldData.datePickerOptions = {
                            minDate: fieldData.params && fieldData.params.start_date ? new Date(fieldData.params.start_date): new Date(),
                            maxDate: new Date('01/01/2100'),
                            showWeeks: false
                        };
                        if (fieldData.values && fieldData.values !== 'null') {
                            fieldData.answer = new Date(fieldData.values);
                            fieldData.answer = fieldData.values;
                        } else {
                            fieldData.answer = '';
                        }
                    }
                    if(fieldData.field_type === 'TIME') {
                        fieldData.hourStep = 1;
                        fieldData.minuteStep = 5;
                        fieldData.isMeridian = true;
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        if(fieldData.params && fieldData.params.start_time) {
                            var startTimeArray = fieldData.params.start_time.split(" ");
                            var startTimes = startTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var startTimeHour = startTimeArray[1] === 'PM' ? Number(startTimes[0]) : Number(startTimes[0]) - 12;
                            } else {
                                var startTimeHour = startTimeArray[1] === 'AM' ? Number(startTimes[0]) : Number(startTimes[0]) + 12;
                            }
                        }
                        if(fieldData.params && fieldData.params.end_time) {
                            var endTimeArray = fieldData.params.end_time.split(" ");
                            var endTimes = endTimeArray[0].split(":");
                            if(startTimes[0] === 12) {
                                var endTimeHour = endTimeArray[1] === 'PM' ? Number(endTimes[0]) : Number(endTimes[0]) - 12;
                            } else {
                                var endTimeHour = endTimeArray[1] === 'AM' ? Number(endTimes[0]) : Number(endTimes[0]) + 12;
                            }
                        }
                        if(fieldData.values !== '') {
                            let timeValuesArray = fieldData.values.split(':');
                            fieldData.answer = new Date(new Date(new Date().setHours(timeValuesArray[0])).setMinutes(timeValuesArray[1]));
                        } else {
                            fieldData.answer = new Date();
                        }
                    }
                    if(fieldData.field_type === 'CHECKBOX') {
                        fieldData.answer = [];
                        fieldData.option.forEach((option) => {
                            option.selected = false;
                        });
                        if(fieldData.values && fieldData.values.length > 0) {
                            fieldData.values.forEach((value) => {
                                fieldData.answer.push(value.checkbox_val);
                                fieldData.option.forEach((option)=> {
                                    if(option.option_id === value.checkbox_val) {
                                        option.selected = true;
                                    }
                                })
                            });
                        }
                    }
                    if(fieldData.field_type === 'DECIMAL' || fieldData.field_type === 'NUMBER') {
                        let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                        fieldData.params = params;
                        fieldData.answer = Number(fieldData.values);
                    }
                });
            });
        }

    }

    function getAddressAreaId () {
        selectedAddressAreaId = $window.localStorage.getItem('address_area_id');
        if (selectedAddressAreaId !== null) {  // localStorage returns null if the item is not found
            try {
        return JSON.parse(selectedAddressAreaId);
            } catch (e) {
                console.error('Error parsing JSON from localStorage', e);
                return null; // or handle the error as needed
            }
        } else {
            // Handle the case where selectedBirthplaceAreaId is not found in localStorage
            return null; // or any default value you prefer
        }
        return null;
    }

    function getAddressArea () {
        selectedAddressArea = $window.localStorage.getItem('address_area');
        if (selectedAddressArea !== null) {  // localStorage returns null if the item is not found
            try {
        return JSON.parse(selectedAddressArea);
            } catch (e) {
                console.error('Error parsing JSON from localStorage', e);
                return null; // or handle the error as needed
            }
        } else {
            // Handle the case where selectedBirthplaceAreaId is not found in localStorage
            return null; // or any default value you prefer
        }
        return null;
    }

    function getBirthplaceAreaId () {
        selectedBirthplaceAreaId = $window.localStorage.getItem('birthplace_area_id');
        if (selectedBirthplaceAreaId !== null) {  // localStorage returns null if the item is not found
            try {
                return JSON.parse(selectedBirthplaceAreaId);
            } catch (e) {
                console.error('Error parsing JSON from localStorage', e);
                return null; // or handle the error as needed
            }
        } else {
            // Handle the case where selectedBirthplaceAreaId is not found in localStorage
            return null; // or any default value you prefer
        }
        return null;
    }

    function getBirthplaceArea () {
        selectedBirthplaceArea = $window.localStorage.getItem('birthplace_area');
        if (selectedBirthplaceArea !== null) {  // localStorage returns null if the item is not found
            try {
                return JSON.parse(selectedBirthplaceArea);
            } catch (e) {
                console.error('Error parsing JSON from localStorage', e);
                return null; // or handle the error as needed
            }
        } else {
            // Handle the case where selectedBirthplaceAreaId is not found in localStorage
            return null; // or any default value you prefer
        }
        return null;
    }

    function saveDirectoryData (params) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institution/Institutions/saveDirectoryData';
        $http.post(url, params)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function saveGuardianData(params){
        // console.log(params);
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/saveGuardianData';
        $http.post(url, params)
            .then(function(response){
                // console.log(response);
                deferred.resolve(response);
            }, function(error) {
                deferred.reject(error);
            });
        return deferred.promise;
    }

    /**
     * Parameters are - identity_type_id, identity_number & nationality_id pass as a object
     * If staff exist then user_exist will be 1 otherwise 0 & show the message as warning
     * @required {identity_type_id} identity_type_id
     * @required {identity_number} identity_number
     * @required {nationality_id} nationality_id
     * @returns {[{"user_exist":1,"status_code":2,"message":"User already exist with this nationality, identity type & identity type. Kindly select user from below list."}]}
     */
    function checkUserExistByIdentity(params) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/checkUserAlreadyExistByIdentity';
        $http.post(url, { params: params })
            .then(function (response)
            {
                deferred.resolve(response);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }

    /**
    * Based on showExternalSearch property need to hide external search step in form wizard
    * @returns {Case 1: for None  [{"value":"None","showExternalSearch ":false}]}
   *  @returns {Case 2: for rest values [{"value":"OpenEMIS Identity","showExternalSearch ":true}]}
    */
    function checkConfigForExternalSearch(nationality_id, identity_type_id)
    {
        var deferred = $q.defer();
        let url = angular.baseUrl + '/Institutions/checkConfigurationForExternalSearch';
        let params = {
            'nationality_id' : nationality_id,
            'identity_type_id' : identity_type_id
        };

        $http.post(url, {params: params})
            .then(function (response)
            {
                deferred.resolve(response.data[0]);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }


    /**
     * @name  Url: /Institutions/getCspdData
     * @description  Request Params: identity_number
     * @param {*} params  {identity_number}
     */

    function getCspdData(params){
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Institutions/getCspdData';
        $http.post(url, { params: params })
            .then(function (response)
            {
                deferred.resolve(response);
            }, function (error)
            {
                deferred.reject(error);
            });
        return deferred.promise;
    }

    function setError(errorObj, field, message) {
        errorObj[field] = message;
    }

    function unsetError(errorObj, field) {
        delete errorObj[field];
    }

    function unsetAllErrors(scope) {
        scope.error = {};
    }

    // POCOR-9427
    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;
            if (angular.isObject(results) && results.length > 0) {
                var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
                deferred.resolve(configItemValue);
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItems
            .where({
                code: code
            })
            .ajax({
                success: success,
                defer: true
            });
    }

    function isNextButtonShouldDisable(scope) {
        const {
            step,
            selectedUserData: {
                first_name,
                last_name,
                date_of_birth,
                gender_id,
                identity_number,
                openemis_no,
                user_id
            },
            isIdentityUserExist,
            externalSearchSourceName,
            isExternalSearchEnable
        } = scope;

        const checkVars = {
            step,
            first_name,
            last_name,
            date_of_birth,
            gender_id,
            identity_number,
            openemis_no,
            user_id,
            isIdentityUserExist,
            externalSearchSourceName,
            isExternalSearchEnable
};

        const isInternalSearch = checkVars.step === "internal_search";
        const isExternalSearch = checkVars.step === "external_search";

        // console.log(checkVars);

        if (checkVars.isIdentityUserExist && isInternalSearch) return true;
        if (checkVars.openemis_no && !checkVars.user_id && isInternalSearch) return true;
        // if (checkVars.identity_number && !checkVars.user_id && !checkVars.isExternalSearchEnable && isInternalSearch) return true; // POCOR-8776
        if (isInternalSearch && !checkVars.isExternalSearchEnable && !(checkVars.first_name && checkVars.last_name && checkVars.date_of_birth && checkVars.gender_id)) return true;
        if (isExternalSearch && checkVars.externalSearchSourceName === 'UNHCR' && !checkVars.identity_number) return true;
        if (isExternalSearch && checkVars.externalSearchSourceName === 'Seychelles Civil Status' && !checkVars.identity_number) return true; // POCOR-9481
        if (isExternalSearch && !(checkVars.first_name && checkVars.last_name && checkVars.date_of_birth && checkVars.gender_id)) return true;

        return false;
    }
}
