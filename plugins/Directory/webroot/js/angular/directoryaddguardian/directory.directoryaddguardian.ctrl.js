angular.module('directory.directoryaddguardian.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'kd-angular-tree-dropdown', 'kd.data.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryaddguardianCtrl', DirectoryaddguardianController);

DirectoryaddguardianController.$inject = ['$scope', '$q', '$window', '$http', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'KdDataSvc',  'DirectoryaddSvc'];

function DirectoryaddguardianController($scope, $q, $window, $http, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, KdDataSvc, DirectoryaddSvc) {
    var scope = $scope;
    var userCtrl = $scope;
    userCtrl.studentOpenEmisId = undefined;
    const directorySvc = DirectoryaddSvc;
// POCOR-9427 start
    const USER_TYPES = {
        STUDENT: 1,
        STAFF: 2,
        GUARDIAN: 3,
        OTHER: 4
    };
    // POCOR-9427 end
    userCtrl.step = 'user_details';
    userCtrl.selectedUserData = {};
    userCtrl.internalGridOptions = null;
    userCtrl.externalGridOptions = null;
    userCtrl.postRespone = null;
    userCtrl.translateFields = null;
    userCtrl.genderOptions = [];
    userCtrl.nationality_class = 'input select error';
    userCtrl.identity_type_class = 'input select error';
    userCtrl.identity_class = 'input string';
    userCtrl.messageClass = '';
    userCtrl.message = '';
    userCtrl.nationalitiesOptions = [];
    userCtrl.identityTypeOptions = [];
    userCtrl.contactTypeOptions = [];
    userCtrl.relationTypeOptions = [];
    userCtrl.addressAreaOption = [];
    userCtrl.birthplaceAreaOption = [];
    userCtrl.pageSize = 10;
    userCtrl.rowsThisPage = [];
    userCtrl.selectedGuardian;
    userCtrl.error = {};
    userCtrl.studentName;
    userCtrl.isInternalSearchSelected = false;
    userCtrl.isExternalSearchSelected = false;
    userCtrl.isIdentityUserExist = false;
    userCtrl.isExternalSearchEnable = false;
    userCtrl.externalSearchSourceName = '';
    userCtrl.disableFields = {
        username: false,
        password: false
    }
    userCtrl.isSearchResultEmpty = false;
    userCtrl.datepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    userCtrl.addressAreaId = null;
    userCtrl.birthplaceAreaId = null;

    angular.element(document).ready(function () {
        function initUserCtrl() {
            UtilsSvc.isAppendLoader(true);
            directorySvc.init(angular.baseUrl);
            userCtrl.institutionId = Number($window.localStorage.getItem("institution_id"));
            userCtrl.translateFields = {
                'openemis_no': 'OpenEMIS ID',
                'name': 'Name',
                'gender_name': 'Gender',
                'date_of_birth': 'Date Of Birth',
                'nationality_name': 'Nationality',
                'identity_type_name': 'Identity Type',
                'identity_number': 'Identity Number',
                'account_type': 'Account Type'
            };

            // Remove specific items from local storage
            ['address_area', 'address_area_id', 'birthplace_area', 'birthplace_area_id'].forEach(item => {
                if ($window.localStorage.getItem(item)) {
                    $window.localStorage.removeItem(item);
                }
            });
            try { // POCOR-8014-n
                if (typeof userCtrl.studentOpenEmisId !== "undefined") {
                    //POCOR-7916:start
                    var student_param = {
                        openemis_no: userCtrl.studentOpenEmisId
                    };
                    directorySvc.getInternalSearchData(student_param)
                        .then(function (response) {
                            var studentData = response.data.data;
                            // console.log(response);
                            if (Array.isArray(studentData)) {
                                var student = studentData[0];
                                userCtrl.studentName = student.name;
                            }

                        });
                    //POCOR-7916:end
                }
                //POCOR-7231::Start
                if (userCtrl.institutionId) {
                    userCtrl.selectedUserData.institution_id = institution_id;
                }
            } catch (err) {
                console.warn(err)
            }
            // scope.initGrid();
            loadUserData();
        }

        function getGenders() {
            return directorySvc.setGenders(userCtrl)
        }

        function getNationalities() {
            return directorySvc.setNationalities(userCtrl);
        }

        function getIdentityTypes() {
            return directorySvc.setIdentityTypes(userCtrl);
        }

        function getContactTypes() {
            return directorySvc.setContactTypes(userCtrl);
        }

        // POCOR-9427
        function handleConfigItem(configCode, configValue) {
            // Init main config container
            userCtrl.config = userCtrl.config || {};
            userCtrl.selectedUserData.user_type_id = USER_TYPES['GUARDIAN'];
            // Match config keys to user types and fields
            const configMap = {
                // student_email:       { type: 'student', field: 'email' },
                // student_mobile:      { type: 'student', field: 'mobile_number' },
                // StudentIdentities:   { type: 'student', field: 'identity' },
                // StudentNationalities:{ type: 'student', field: 'nationality' },
                //
                // staff_email:         { type: 'staff', field: 'email' },
                // staff_mobile:        { type: 'staff', field: 'mobile_number' },
                // StaffIdentities:     { type: 'staff', field: 'identity' },
                // StaffNationalities:  { type: 'staff', field: 'nationality' },

                // guardian_email:       { type: 'guardian', field: 'email' },
                // guardian_mobile:      { type: 'guardian', field: 'mobile' },
                GuardianIdentities:   { type: 'guardian', field: 'identity' },
                GuardianNationalities:{ type: 'guardian', field: 'nationality' },

                // other_email:       { type: 'other', field: 'email' },
                // other_mobile:      { type: 'other', field: 'mobile' },
                // OtherIdentities:   { type: 'other', field: 'identity' },
                // OtherNationalities:{ type: 'other', field: 'nationality' }
            };

            const configItem = configMap[configCode];

            if (!configItem) {
                console.warn(`Unhandled config code: ${configCode}`);
                return;
            }

            const { type, field } = configItem;
            userCtrl.config[type] = userCtrl.config[type] || {};

            switch (field) {
                case 'email':
                case 'mobile_number':
                    userCtrl.config[type][`${field}_skipped`] = configValue === 2;
                    userCtrl.config[type][`${field}_required`] = configValue === 1 ? 'required' : '';
                    break;

                case 'identity':
                    userCtrl.config[type].identitySkipped = configValue === 2;
                    userCtrl.config[type].identitiesRequired = configValue === 1 ? 'required' : '';
                    break;

                case 'nationality':
                    // Ensure dependency on identitySkipped is handled
                    const identitySkipped = userCtrl.config[type].identitySkipped;
                    if (configValue === 2 && identitySkipped) {
                        userCtrl.config[type].nationalitySkipped = true;
                        userCtrl.config[type].nationalitiesRequired = '';
                    } else {
                        userCtrl.config[type].nationalitySkipped = configValue === 2;
                        userCtrl.config[type].nationalitiesRequired = configValue === 1 ? 'required' : '';
                    }
                    break;
            }
        }

        // POCOR-9427
        function getAddNewUserConfig() {
            const configCodes = [
                // Student
                // "student_email", "student_mobile", "StudentIdentities", "StudentNationalities",
                // // Staff
                // "staff_email", "staff_mobile", "StaffIdentities", "StaffNationalities",
                // Guardian
                // "guardian_email", "guardian_mobile",
                "GuardianIdentities", "GuardianNationalities",
                // Other
                // "other_email", "other_mobile",
                // "OtherIdentities", "OtherNationalities"
            ];

            return Promise.all(configCodes.map(code => directorySvc.getConfigItemValue(code)))
                .then(configValues => {
                    configValues.forEach((value, index) => {
                        handleConfigItem(configCodes[index], parseInt(value));
                    });
                })
                .catch(error => {
                    console.error('Error fetching user config values:', error);
                });
        }

        function getRelationType() {
            return directorySvc.getRelationType()
                .then(resp => {
                    userCtrl.relationTypeOptions = resp.data;
                });
        }

        function loadUserData() {
            getGenders()
                .then(getRelationType)
                .then(getNationalities)
                .then(getIdentityTypes)
                .then(getContactTypes)
                .then(getAddNewUserConfig) // POCOR-9427
                .then(() => {
                    UtilsSvc.isAppendLoader(false);
                })
                .catch(error => {
                    console.error(error);
                    UtilsSvc.isAppendLoader(false);
                });
        }

// Initialize the user controller
        initUserCtrl();
    });

    // First change

    userCtrl.changeRelationType = function () {
        directorySvc.changeRelationType($scope);
    };

    // user_details function

    userCtrl.changeNationality = function() {
        directorySvc.changeNationality($scope);
    };

    userCtrl.changeIdentityType = function() {
        directorySvc.changeIdentityType($scope);
    };


    userCtrl.checkIdentityNumber = function() {
        directorySvc.checkIdentityNumber($scope);
    };

    userCtrl.setName = function() {
        directorySvc.setName($scope);
    };

    userCtrl.changeGender = function() {
        directorySvc.changeGender($scope);
    };

    userCtrl.checkDateOfBirth = function() {
        directorySvc.checkDateOfBirth($scope);
    };

    userCtrl.setError = function(field, message) {
        directorySvc.setError(userCtrl.error, field, message);
    };

    userCtrl.unsetError = function(field) {
        directorySvc.unsetError(userCtrl.error, field);
    };

    userCtrl.unsetAllErrors = function() {
        userCtrl.error = {};
    };

    userCtrl.validateUserDetails = function() {
        directorySvc.validateUserDetails($scope);
    };

    // end common user details functions

    userCtrl.validateConfirmDetails = function() {
        directorySvc.validateConfirmDetails($scope);
    };

    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $window.savePhoto = function (event) {
        let photo = event.files[0];
        userCtrl.selectedUserData.photo = photo;
        userCtrl.selectedUserData.photo_name = photo.name;
        let fileReader = new FileReader();
        fileReader.readAsDataURL(photo);
        fileReader.onload = () => {
            const base64String = fileReader.result.split(',')[1];

            // POCOR-8917 Manually trigger AngularJS digest cycle
            $scope.$apply(() => {
                userCtrl.selectedUserData.photo_base_64 = base64String;
            });
        };
    }


    userCtrl.goToNextStep = async function () {
        if (userCtrl.step === 'confirmation') {
            const result = await userCtrl.checkUserExistByIdentityFromConfiguration();
            if (result) return;
        }
        if (userCtrl.isInternalSearchSelected) {
            userCtrl.processNewUser();
        } else {
            switch (userCtrl.step) {
                case 'user_details':
                    scope.internalGridOptions = null;
                    userCtrl.validateUserDetails();
                    break;
                case 'internal_search': {
                    if (userCtrl.isExternalSearchEnable) {
                        userCtrl.step = 'external_search';
                        userCtrl.externalGridOptions = null;
                        UtilsSvc.isAppendLoader(true);
                        userCtrl.goToExternalSearch();
                    } else {
                        userCtrl.processNewUser();
                    }
                    return;
                }
                case 'external_search':
                    userCtrl.processNewUser();
                    break;
            }
        }
    }

    userCtrl.processNewUser = function () {
        scope.step = 'confirmation';
        UtilsSvc.isAppendLoader(true);

        scope.getUniqueOpenEmisId()
            .then(() => {
                return scope.generatePassword();
            })
            .catch(error => {
                UtilsSvc.isAppendLoader(false);
                scope.messageClass = 'alert-danger';
                scope.message = error.message || error.toString();
                console.error(error);
            })
            .then(() => {
                UtilsSvc.isAppendLoader(false);
            });
    };

    userCtrl.goToPrevStep = function () {
        userCtrl.error = {};
        userCtrl.disableFields = {
            username: false,
            password: false
        }
        const relation_type_id = userCtrl.selectedUserData.relation_type_id;
        const relation_type_name = userCtrl.selectedUserData.relation_type_name;
        userCtrl.selectedUserData = {};
        userCtrl.selectedUserData.relation_type_id = relation_type_id;
        userCtrl.selectedUserData.relation_type_name = relation_type_name;
        userCtrl.selectedUser = null;
        userCtrl.selectedGuardian = null;

        if (userCtrl.isInternalSearchSelected) {
            userCtrl.isInternalSearchSelected = false;
            userCtrl.step = 'user_details';
            userCtrl.internalGridOptions = null;
            // scope.goToInternalSearch();
        } else {
            switch (userCtrl.step) {
                case 'internal_search': {
                    userCtrl.step = 'user_details';
                    break;
                }
                case 'external_search':
                    userCtrl.step = 'internal_search';
                    userCtrl.internalGridOptions = null;
                    userCtrl.goToInternalSearch();
                    break;
                case 'confirmation': {
                    if (userCtrl.isExternalSearchEnable) {
                        userCtrl.step = 'external_search';
                        userCtrl.externalGridOptions = null;
                        userCtrl.goToExternalSearch();
                    } else {
                        userCtrl.step = 'internal_search';
                        userCtrl.internalGridOptions = null;
                        userCtrl.goToInternalSearch();
                    }
                    return;
                }
            }
        }
    }

    userCtrl.cancelProcess = function () {
        $window.history.back();
    }


    userCtrl.goToInternalSearch = function () {
        directorySvc.goToInternalSearch(userCtrl);
    };

    userCtrl.goToExternalSearch = function () {
        directorySvc.goToExternalSearch(userCtrl);
    }

    userCtrl.processGridUserRecord = function (userRecords, params, totalRowCount) {
        // console.log(userRecords);
        if (userRecords.length === 0) {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }
        var lastRow = totalRowCount;
        userCtrl.rowsThisPage = userRecords;

        params.successCallback(userCtrl.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    userCtrl.getUniqueOpenEmisId = function () {
        return directorySvc.setUniqueOpenEmisId(userCtrl);
    };

    userCtrl.generatePassword = function () {
        return directorySvc.setPassword(userCtrl);
    };

    userCtrl.selectUserFromInternalSearch = function (id) {
        userCtrl.selectedGuardian = id;
        userCtrl.isInternalSearchSelected = true;
        userCtrl.getGuardianData();

        if (userCtrl.isIdentityUserExist) {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
        }
        userCtrl.disableFields = {
            username: true,
            password: true
        }
    }

    userCtrl.selectUserFromExternalSearch = function (id) {
        userCtrl.selectedGuardian = id;
        userCtrl.isInternalSearchSelected = false;
        userCtrl.isExternalSearchSelected = true; //POCOR-9590: mark guardian as sourced from External Search so saveDetails sets sync_status=1
        userCtrl.getGuardianData();
        userCtrl.disableFields = {
            username: false,
            password: false
        }
    }

    userCtrl.getGuardianData = function () {
        var log = [];
        angular.forEach(userCtrl.rowsThisPage, function (value) {
            if (value.id == userCtrl.selectedGuardian) {
                // if (userCtrl.isInternalSearchSelected)
                    userCtrl.setUserData(value);
                // else
                //     userCtrl.setExternalUserData(value);
            }
        }, log);
    }

    userCtrl.setUserData = function (selectedData) {
        userCtrl.selectedUserData.addressArea = {
            id: selectedData.address_area_id,
            name: selectedData.area_name,
            code: selectedData.area_code
        };
        userCtrl.selectedUserData.birthplaceArea = {
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name,
            code: selectedData.birth_area_code
        };
        userCtrl.selectedUserData.user_id = selectedData.id;
        userCtrl.selectedUserData.openemis_no = selectedData.openemis_no;
        userCtrl.selectedUserData.first_name = selectedData.first_name;
        userCtrl.selectedUserData.middle_name = selectedData.middle_name;
        userCtrl.selectedUserData.third_name = selectedData.third_name;
        userCtrl.selectedUserData.last_name = selectedData.last_name;
        userCtrl.selectedUserData.preferred_name = selectedData.preferred_name;
        userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
        userCtrl.selectedUserData.email = selectedData.email;
        userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
        userCtrl.selectedUserData.gender_id = selectedData.gender_id;
        userCtrl.selectedUserData.gender = {name: selectedData.gender};
        userCtrl.selectedUserData.nationality_id = selectedData.nationality_id;
        userCtrl.selectedUserData.nationality_name = selectedData.nationality;
        userCtrl.selectedUserData.contact_type_id = selectedData.contact_type_id; // POCOR-8012-n
        userCtrl.selectedUserData.contact_value = selectedData.contact_value; // POCOR-8012-n
        if (selectedData.identity_number) {
            userCtrl.canSkipIdentity = true;
        }
        if (selectedData.nationality_id) {
            userCtrl.canSkipNationality = true;
        }
        userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
        userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
        userCtrl.selectedUserData.identity_number = selectedData.identity_number;
        userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        userCtrl.selectedUserData.password = selectedData.password;
        userCtrl.selectedUserData.address = selectedData.address;
        userCtrl.selectedUserData.postalCode = selectedData.postal_code;
        userCtrl.selectedUserData.address_area_id = selectedData.address_area_id;
        userCtrl.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
        userCtrl.selectedUserData.addressArea = {name: selectedData.area_name};
        userCtrl.selectedUserData.birthplaceArea = {name: selectedData.birth_area_name};
        if ($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if ($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        $window.localStorage.setItem('birthplace_area_id', selectedData.birthplace_area_id);
        $window.localStorage.setItem('address_area_id', selectedData.address_area_id);
        if ($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if ($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        $window.localStorage.setItem('birthplace_area', JSON.stringify({
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name
        }));
        $window.localStorage.setItem('address_area', JSON.stringify({
            id: selectedData.address_area_id,
            name: selectedData.area_name
        }));
        userCtrl.addressAreaId = selectedData.address_area_id;
        userCtrl.birthplaceAreaId = selectedData.birthplace_area_id;
        if (selectedData.address_area_id > 0) {
            document.getElementById('addressArea_textbox').style.visibility = 'visible';
            document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
        } else {
            document.getElementById('addressArea_textbox').style.display = 'none';
            document.getElementById('addressArea_dropdown').style.visibility = 'visible';
        }

        if (selectedData.birthplace_area_id > 0) {
            document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
        } else {
            document.getElementById('birthplaceArea_textbox').style.display = 'none';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
        }
    }

    userCtrl.setExternalUserData = function (selectedData) {
        /* TODO */
        if (userCtrl.externalSearchSourceName == 'Jordan CSPD') {
            directorySvc.getUniqueOpenEmisId().then((response) => {
                const selectedObjectWithOpenemisNo = Object.assign({}, selectedData, {'openemis_no': response})
                selectedData = selectedObjectWithOpenemisNo;
                userCtrl.selectedUserData.addressArea = {
                    id: selectedData.address_area_id,
                    name: selectedData.area_name,
                    code: selectedData.area_code
                };
                userCtrl.selectedUserData.birthplaceArea = {
                    id: selectedData.birthplace_area_id,
                    name: selectedData.birth_area_name,
                    code: selectedData.birth_area_code
                };
                userCtrl.selectedUserData.openemis_no = selectedData.openemis_no;
                userCtrl.selectedUserData.first_name = selectedData.first_name;
                userCtrl.selectedUserData.middle_name = selectedData.middle_name;
                userCtrl.selectedUserData.third_name = selectedData.third_name;
                userCtrl.selectedUserData.last_name = selectedData.last_name;
                userCtrl.selectedUserData.preferred_name = selectedData.preferred_name;
                userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
                userCtrl.selectedUserData.email = selectedData.email;
                userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
                userCtrl.selectedUserData.gender_id = selectedData.gender_id;
                userCtrl.selectedUserData.gender = {name: selectedData.gender};
                userCtrl.selectedUserData.nationality_id = selectedData.nationality_id;
                userCtrl.selectedUserData.nationality_name = selectedData.nationality;
                userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
                userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
                userCtrl.selectedUserData.identity_number = selectedData.identity_number;
                userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                userCtrl.selectedUserData.password = selectedData.password;
                userCtrl.selectedUserData.address = selectedData.address;
                userCtrl.selectedUserData.postalCode = selectedData.postal_code;
                if (selectedData.address_area_id > 0) {
                    document.getElementById('addressArea_textbox').style.visibility = 'visible';
                    document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
                } else {
                    document.getElementById('addressArea_textbox').style.display = 'none';
                    document.getElementById('addressArea_dropdown').style.visibility = 'visible';
                }

                if (selectedData.birthplace_area_id > 0) {
                    document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
                } else {
                    document.getElementById('birthplaceArea_textbox').style.display = 'none';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
                }
            })
        } else {
            userCtrl.selectedUserData.addressArea = {
                id: selectedData.address_area_id,
                name: selectedData.area_name,
                code: selectedData.area_code
            };
            userCtrl.selectedUserData.birthplaceArea = {
                id: selectedData.birthplace_area_id,
                name: selectedData.birth_area_name,
                code: selectedData.birth_area_code
            };
            userCtrl.selectedUserData.user_id = selectedData.id;
            userCtrl.selectedUserData.openemis_no = selectedData.openemis_no;
            userCtrl.selectedUserData.first_name = selectedData.first_name;
            userCtrl.selectedUserData.middle_name = selectedData.middle_name;
            userCtrl.selectedUserData.third_name = selectedData.third_name;
            userCtrl.selectedUserData.last_name = selectedData.last_name;
            userCtrl.selectedUserData.preferred_name = selectedData.preferred_name;
            userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
            userCtrl.selectedUserData.email = selectedData.email;
            userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
            userCtrl.selectedUserData.gender_id = selectedData.gender_id;
            userCtrl.selectedUserData.gender = {name: selectedData.gender};
            userCtrl.selectedUserData.nationality_id = selectedData.nationality_id;
            userCtrl.selectedUserData.nationality_name = selectedData.nationality;
            userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
            userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
            userCtrl.selectedUserData.identity_number = selectedData.identity_number;
            userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
            userCtrl.selectedUserData.password = selectedData.password;
            userCtrl.selectedUserData.address = selectedData.address;
            userCtrl.selectedUserData.postalCode = selectedData.postal_code;
            if (selectedData.address_area_id > 0) {
                document.getElementById('addressArea_textbox').style.visibility = 'visible';
                document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
            } else {
                document.getElementById('addressArea_textbox').style.display = 'none';
                document.getElementById('addressArea_dropdown').style.visibility = 'visible';
            }

            if (selectedData.birthplace_area_id > 0) {
                document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
            } else {
                document.getElementById('birthplaceArea_textbox').style.display = 'none';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
            }
        }
    }

    userCtrl.saveDetails = function () {
        const addressAreaRef = directorySvc.getAddressArea()
        addressAreaRef && (userCtrl.selectedUserData.addressArea = addressAreaRef);
        const birthplaceAreaRef = directorySvc.getBirthplaceArea();
        birthplaceAreaRef && (userCtrl.selectedUserData.birthplaceArea = birthplaceAreaRef);
        var params = {
            guardian_relation_id: userCtrl.selectedUserData.relation_type_id,
            student_openemis_no: userCtrl.studentOpenEmisId,
            openemis_no: userCtrl.selectedUserData.openemis_no,
            first_name: userCtrl.selectedUserData.first_name,
            middle_name: userCtrl.selectedUserData.middle_name,
            third_name: userCtrl.selectedUserData.third_name,
            last_name: userCtrl.selectedUserData.last_name,
            preferred_name: userCtrl.selectedUserData.preferred_name,
            gender_id: userCtrl.selectedUserData.gender_id,
            date_of_birth: userCtrl.selectedUserData.date_of_birth,
            identity_number: userCtrl.selectedUserData.identity_number,
            nationality_id: userCtrl.selectedUserData.nationality_id,
            nationality_name: userCtrl.selectedUserData.nationality_name,
            username: userCtrl.selectedUserData.username,
            password: userCtrl.selectedUserData.password,
            postal_code: userCtrl.selectedUserData.postalCode,
            address: userCtrl.selectedUserData.address,
            birthplace_area_id: directorySvc.getBirthplaceArea(),
            address_area_id: directorySvc.getAddressArea(),
            identity_type_id: userCtrl.selectedUserData.identity_type_id,
            identity_type_name: userCtrl.selectedUserData.identity_type_name,
            photo_name: userCtrl.selectedUserData.photo_name,
            photo_content: userCtrl.selectedUserData.photo_base_64,
            sync_status: userCtrl.isExternalSearchSelected ? 1 : 0, //POCOR-9590: external search → Synced, manual add → Local
            contact_type: userCtrl.selectedUserData.contact_type_id,
            contact_value: userCtrl.selectedUserData.contact_value,
            email: userCtrl.selectedUserData.email,
            mobile_number: userCtrl.selectedUserData.mobile_number,

        };
        UtilsSvc.isAppendLoader(true);
        directorySvc.saveGuardianData(params)
            .then(function (response) {
                userCtrl.message = (userCtrl.selectedUserData && userCtrl.selectedUserData.relation_type_name ? userCtrl.selectedUserData.relation_type_name : 'Guardian') + ' successfully added.';
                userCtrl.messageClass = 'alert-success';
                userCtrl.step = "summary";
                var todayDate = new Date();
                userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.error(error);
                //POCOR-9590: walk per-field errors so the offending field is highlighted
                //and the toast carries the field+rule message instead of generic "Validation failed".
                const fieldErrors = (error && error.data && error.data.errors) ? error.data.errors : null;
                let firstFieldMessage = '';
                if (fieldErrors && typeof fieldErrors === 'object') {
                    for (const field of Object.keys(fieldErrors)) {
                        const rules = fieldErrors[field];
                        const msg = (rules && typeof rules === 'object') ? Object.values(rules)[0] : rules;
                        if (msg) {
                            userCtrl.setError(field, msg);
                            if (!firstFieldMessage) firstFieldMessage = `${field}: ${msg}`;
                        }
                    }
                }
                userCtrl.message = firstFieldMessage || (error.data && error.data.message) || error.statusText || error.toString();
                userCtrl.messageClass = 'alert-danger';
                UtilsSvc.isAppendLoader(false);
            });
    }


    async function checkUserAlreadyExistByIdentity() {
        const userData = userCtrl.selectedUserData;
        const result = await directorySvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id
        });

        if (result.data.user_exist === 1) {
            userCtrl.messageClass = 'alert_warn';
            userCtrl.message = result.data.message;
            userCtrl.isIdentityUserExist = true;
        } else {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
        }
        /*  return result.data.user_exist === 1; */
    }

    userCtrl.addGuardian = function addGuardian() {
        //POCOR-7231::Start
        if ($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        // console.log("addGuardian");
        // console.log(StudentController);
        let params = {
            openemis_no: userCtrl.studentOpenEmisId
        };

        var queryString = KdDataSvc.urlsafeB64Encode(JSON.stringify(params));
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian/' + queryString;

        //POCOR-7231::End
    }

    /**
     * @desc 1)Identity Number is mandatory OR
     * @desc 2)OpenEMIS ID is mandatory OR
     * @desc 3)First Name, Last Name, Date of Birth and Gender are mandatory
     * @returns [ error block name | true or false]
     */
    function checkUserDetailValidationBlocksHasError() {
        const {
            first_name,
            last_name,
            gender_id,
            date_of_birth,
            identity_type_id,
            identity_number,
            nationality_id,
            openemis_no,
            identity_type_name
        } = userCtrl.selectedUserData;
        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth)
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        const isIdentityHasError = identity_number?.length > 1 && (nationality_id === undefined || nationality_id === "" || nationality_id === null || identity_type_id === "" || identity_type_id === undefined || identity_type_id === null)
        let isSkipableForIdentity = identity_number?.length>1 && nationality_id > 0 && identity_type_id >0;

        if (identity_type_name == 'UNHCR') {
            isSkipableForIdentity = false;
        }

        if (isIdentityHasError) {
            return ['Identity', true]
        }
        if (isSkipableForIdentity) {
            return ['Identity', false]
        }

        if (isOpenEmisNoHasError) {
            return ["OpenEMIS_ID", false];
        }

        if (isGeneralInfodHasError) {
            return ["General_Info", true];
        }

        return ["", false];
    }

    // userCtrl.checkConfigForExternalSearch = function checkConfigForExternalSearch() {
    //     var nationality_id = userCtrl.selectedUserData.nationality_id
    //     var identity_type_id = userCtrl.selectedUserData.identity_type_id
    //     directorySvc.checkConfigForExternalSearch(nationality_id, identity_type_id).then(function (resp) {
    //         userCtrl.isExternalSearchEnable = resp.showExternalSearch;
    //         userCtrl.externalSearchSourceName = resp.value;
    //         UtilsSvc.isAppendLoader(false);
    //     }, function (error) {
    //         userCtrl.isExternalSearchEnable = false;
    //         console.error(error);
    //         UtilsSvc.isAppendLoader(false);
    //     });
    // }

    userCtrl.isNextButtonShouldDisable = function isNextButtonShouldDisable() {
        const {
            step,
            selectedUserData,
            isIdentityUserExist,
            externalSearchSourceName,
            isExternalSearchEnable} = scope;
        // POCOR-8231 start: change to var
        const {
            first_name,
            last_name,
            date_of_birth,
            gender_id,
            identity_number,
            openemis_no,
            user_id
        } = selectedUserData;


        if (isIdentityUserExist && step === "internal_search") {
            return true;
        }
        if (openemis_no && !user_id && step === "internal_search") {
            return true;
        }

        if (identity_number && !user_id && isExternalSearchEnable !== true && step === "internal_search") {
            return true;
        }
        if (step === 'internal_search' &&
            isExternalSearchEnable !== true &&
            (!(first_name && last_name && date_of_birth && gender_id))) {
            return true;
        }
        // POCOR-8231 end

        if (step === 'external_search' && externalSearchSourceName === 'UNHCR' && !identity_number) {
            return true;
        }

        if (step === 'external_search' && externalSearchSourceName === 'Seychelles Civil Status' && !identity_number) { // POCOR-9481
            return true;
        }

        if (step === 'external_search' && (!(first_name && last_name && date_of_birth && gender_id))) {
            return true;
        }
        return false;
    };

    userCtrl.getCSPDSearchData = function getCSPDSearchData() {
        var param = userCtrl.selectedUserData; //POCOR-7916
        var dataSource = {
            pageSize: userCtrl.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                directorySvc.getCspdData(param)
                    .then(function (response) {
                        var gridData = response.data.data; //POCOR-7916
                        if (!gridData) gridData = [];
                        gridData.forEach((data, idx) => {
                            data.id = idx;
                            data.name = `${data['first_name']} ${data['middle_name']} ${data['last_name']}`;
                            data.gender = data['gender_name'];
                            data.nationality = data['nationality_name'];
                            data.identity_type = data['identity_type_name'];
                            data.gender_id = data['gender_id'];
                            data.nationality_id = data['nationality_id'];
                            data.identity_type_id = data['identity_type_id'];
                        });
                        var totalRowCount = gridData.length === 0 ? 1 : gridData.length;
                        userCtrl.isSearchResultEmpty = gridData.length === 0;
                        return userCtrl.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }, function (error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        userCtrl.externalGridOptions.api.setDatasource(dataSource);
        userCtrl.externalGridOptions.api.sizeColumnsToFit();
    }

    userCtrl.checkUserExistByIdentityFromConfiguration = async function() {
        const { identity_type_id, identity_number } = userCtrl.selectedUserData;

        userCtrl.unsetError('identity_type_id');
        userCtrl.unsetError('identity_number');

        if (!identity_type_id) {
            return false;
        }

        if (identity_type_id && !identity_number) {
            userCtrl.error.identity_number = "This field cannot be left empty";
            return;
        }

        try {
            const result = await directorySvc.checkUserAlreadyExistByIdentity({
                identity_type_id,
                identity_number,
                nationality_id: userCtrl.selectedUserData.nationality_id,
            });

            if (result.data.user_exist === 1) {
                userCtrl.messageClass = 'alert_warn';
                userCtrl.message = result.data.message;
                userCtrl.isIdentityUserExist = true;
                // userCtrl.error.identity_number = result.data.message;
                $window.scrollTo({ bottom: 0 });

            } else {
                userCtrl.messageClass = '';
                userCtrl.message = '';
                userCtrl.isIdentityUserExist = false;
                userCtrl.unsetError('identity_type_id');
                userCtrl.unsetError('identity_number');

            }
            return result.data.user_exist === 1;
        } catch (error) {
            console.error('Error checking user existence:', error);
            return false;
        }
    };

}
