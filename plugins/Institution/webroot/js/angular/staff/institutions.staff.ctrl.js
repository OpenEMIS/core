angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.staff.svc', 'angular.chosen', 'kd-angular-tree-dropdown', 'directory.directoryadd.svc'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStaffSvc', '$rootScope',  'DirectoryaddSvc'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStaffSvc, $rootScope,  DirectoryaddSvc) {
    // ag-grid vars

    const userCtrl = $scope;
    const userSvc = InstitutionsStaffSvc;
    const directorySvc = DirectoryaddSvc;

    userCtrl.pageSize = 10;
    userCtrl.step = 'user_details';
    userCtrl.selectedStaffData = {};
    userCtrl.selectedUserData = userCtrl.selectedStaffData;
    userCtrl.addNewStaffConfig = {};
    userCtrl.internalGridOptions = null;
    userCtrl.externalGridOptions = null;
    userCtrl.postRespone = null;
    userCtrl.translateFields = null;
    userCtrl.contactSkipped = true; // POCOR-9101
    userCtrl.contactsRequired = ''; // POCOR-9101
    userCtrl.mobileSkipped = false; // POCOR-9101
    userCtrl.mobileRequired = ''; // POCOR-9101
    userCtrl.emailSkipped = false; //POCOR-9101
    userCtrl.emailRequired = ''; // POCOR-9101
    userCtrl.identitySkipped = false; // POCOR-7882
    userCtrl.identitiesRequired = 'required'; // POCOR-7882
    userCtrl.nationalitySkipped = false; // POCOR-7882
    userCtrl.nationalitiesRequired = 'required'; // POCOR-7882
    userCtrl.nationalityClass = 'input select';
    userCtrl.identityTypeClass = 'input select';
    userCtrl.identityClass = 'input string';
    userCtrl.messageClass = '';
    userCtrl.message = '';
    userCtrl.contact_value = '';
    userCtrl.contact_type_id = '';
    userCtrl.genderOptions = [];
    userCtrl.nationalitiesOptions = [];
    userCtrl.identityTypeOptions = [];
    userCtrl.positionTypeOptions = [];
    userCtrl.institutionPositionOptions = {
        availableOptions: [],
        selectedOption: ''
    };
    userCtrl.staffTypeOptions = [];
    userCtrl.staffGradePositionOptions = [];//POCOR-5069
    userCtrl.shiftsOptions = [];
    userCtrl.fteOptions = [];
    userCtrl.shiftsId = [];
    userCtrl.rowsThisPage = [];
    userCtrl.institutionId = null;
    userCtrl.error = {};
    userCtrl.staffShiftsId = [];
    userCtrl.datepickerOptions = {
        showWeeks: false
    };
    userCtrl.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    userCtrl.isInternalSearchSelected = false;
    userCtrl.isExternalSearchSelected = false;
    userCtrl.staffStatus = 'Pending';
    userCtrl.customFields = [];
    userCtrl.customFieldsArray = [];

    userCtrl.user_identity_number = "";
    userCtrl.isEnableBirthplaceArea = false;
    userCtrl.isEnableAddressArea = false;
    userCtrl.isIdentityUserExist = false;
    userCtrl.isMaximizeAge = false;//POCOR-8071
    userCtrl.ageMessage = '';//POCOR-8071
    userCtrl.canSkipNationality = false;
    userCtrl.canSkipIdentity = false;
    userCtrl.isExternalSearchEnable = false;
    userCtrl.externalSearchSourceName = '';
    userCtrl.disableFields = {
        username: false,
        pasword: false
    }
    userCtrl.user_identity_type_id = 0;
    userCtrl.isSearchResultEmpty = true;
    //controller function
    userCtrl.goToFirstStep = goToFirstStep;
    userCtrl.goToNextStep = goToNextStep;
    userCtrl.goToPrevStep = goToPrevStep;
    userCtrl.confirmUser = confirmUser;
    userCtrl.initGrid = initGrid;
    userCtrl.getPositions = getPositions;
    userCtrl.changePositionType = changePositionType;
    userCtrl.changePosition = changePosition;
    userCtrl.changeStaffType = changeStaffType;
    userCtrl.changeStaffGradePosition = changeStaffGradePosition;//POCOR-5069
    userCtrl.cancelProcess = cancelProcess;
    userCtrl.changeFte = changeFte;
    // userCtrl.getInternalSearchData = getInternalSearchData;
    // userCtrl.getExternalSearchData = getExternalSearchData;
    userCtrl.processGridUserRecord = processGridUserRecord;
    userCtrl.saveStaffDetails = saveStaffDetails;
    userCtrl.validateDetails = validateDetails;
    userCtrl.validateAdditionalDetails = validateAdditionalDetails;
    userCtrl.goToInternalSearch = goToInternalSearch;
    userCtrl.goToExternalSearch = goToExternalSearch;
    userCtrl.setUserData = setUserData;
    userCtrl.setUserDataFromExternalSearchData = setUserDataFromExternalSearchData;
    userCtrl.onDecimalNumberChange = onDecimalNumberChange;
    userCtrl.checkUserAge = checkUserAge;
    userCtrl.changeOption = changeOption;
    userCtrl.selectOption = selectOption;
    userCtrl.transferStaffNextStep = transferStaffNextStep;
    userCtrl.checkUserExistByIdentityFromConfiguration = checkUserExistByIdentityFromConfiguration;
    userCtrl.getCSPDSearchData = getCSPDSearchData;

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

    angular.element(document).ready(function () {
        function initUserCtrl() {
        UtilsSvc.isAppendLoader(true);
        userSvc.init(angular.baseUrl);
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
            ['address_area', 'address_area_id', 'birthplace_area', 'birthplace_area_id', 'studentOpenEmisId', 'repeater_validation'].forEach(item => {
                if ($window.localStorage.getItem(item)) {
                    $window.localStorage.removeItem(item);
                }
            });

            userCtrl.initGrid();
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

        function getPositionTypes() {
            return userSvc.getPositionTypes()
                .then(resp => {
                    userCtrl.positionTypeOptions = resp.data;
                });
        }

        function getFtes() {
            return userSvc.getFtes()
                .then(resp => {
                    userCtrl.fteOptions = resp.data;
                });
        }

        function getStaffTypes() {
            return userSvc.getStaffTypes()
                .then(resp => {
                    userCtrl.staffTypeOptions = resp.data;
                });
        }

        function getShifts() {
            return userSvc.getShifts()
                .then(resp => {
                    userCtrl.shiftsOptions = resp.data;
                });
        }


        function handleConfigItem(configCode, configValue) {
            switch (configCode) {
                case "staff_email":
                    userCtrl.emailSkipped = configValue === 2;
                    userCtrl.emailRequired = configValue === 1 ? 'required' : '';
                    break;
                case "staff_mobile":
                    userCtrl.mobileSkipped = configValue === 2;
                    userCtrl.mobileRequired = configValue === 1 ? 'required' : '';
                    break;
                case "StaffIdentities":
                    userCtrl.identitySkipped = configValue === 2;
                    userCtrl.identitiesRequired = configValue === 1 ? 'required' : '';
                    break;
                case "StaffNationalities":
                    if (configValue === 2 && userCtrl.identitySkipped) {
                        userCtrl.nationalitySkipped = true;
                        userCtrl.nationalitiesRequired = '';
                    } else {
                        userCtrl.nationalitySkipped = configValue === 2;
                        userCtrl.nationalitiesRequired = configValue === 1 ? 'required' : '';
                    }
                    break;
                default:
                    console.warn(`Unhandled config code: ${configCode}`);
            }
        }

        function getAddNewStaffConfig() {
            const configCodes = [
                "staff_email",
                "staff_mobile",
                "StaffIdentities",
                "StaffNationalities"];

            Promise.all(configCodes.map(code => userSvc.getConfigItemValue(code)))
                .then(configValues => {
                    configValues.forEach((configValue, index) => {
                        handleConfigItem(configCodes[index], parseInt(configValue));
                    });
                })
                .catch(error => {
                    console.error('Error fetching configuration items:', error);
                });
        }

        function loadUserData() {
            getGenders()
                .then(getNationalities)
                .then(getIdentityTypes)
                .then(getContactTypes)
                .then(getAddNewStaffConfig)
                .then(getPositionTypes)
                .then(getFtes)
                .then(getStaffTypes)
                .then(getShifts)
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

    userCtrl.getStaffPosititonGrades = function() {
        var params = {};
        if (userCtrl.institutionPositionOptions.selectedOption === null) {
            params = {};
        } else {
            params = {"institution_position_id": userCtrl.institutionPositionOptions.selectedOption.value};
        }
        return userSvc.getStaffPosititonGrades(params)
            .then(resp => {
                userCtrl.staffGradePositionOptions = resp.data;
            });
    }
    userCtrl.changeNationality = function() {
        directorySvc.changeNationality($scope);
    };

    userCtrl.changeIdentityType = function() {
        directorySvc.changeIdentityType($scope);
    };


    userCtrl.changeIdentityNumber = function() {
        directorySvc.changeIdentityNumber($scope);
    };

    userCtrl.setName = function() {
        directorySvc.setName($scope);
    };

    userCtrl.changeGender = function() {
        directorySvc.changeGender($scope);
    };

    userCtrl.changeDateOfBirth = function() {
        directorySvc.changeDateOfBirth($scope);
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

    userCtrl.validateUserDetails = function () {
        directorySvc.validateUserDetails(userCtrl);
    };

    userCtrl.validateConfirmDetails = function () {
        directorySvc.validateConfirmDetails(userCtrl);
    };

    function saveStaffDetails() {
        const addressAreaRef = directorySvc.getAddressArea();
        addressAreaRef && (userCtrl.selectedUserData.addressArea = addressAreaRef)
        const birthplaceAreaRef = directorySvc.getBirthplaceArea();
        birthplaceAreaRef && (userCtrl.selectedUserData.birthplaceArea = birthplaceAreaRef)
        var params = {
            openemis_no: userCtrl.selectedUserData.openemis_no,
            first_name: userCtrl.selectedUserData.first_name,
            middle_name: userCtrl.selectedUserData.middle_name,
            third_name: userCtrl.selectedUserData.third_name,
            last_name: userCtrl.selectedUserData.last_name,
            preferred_name: userCtrl.selectedUserData.preferred_name,
            gender_id: userCtrl.selectedUserData.gender_id,
            date_of_birth: userCtrl.selectedUserData.date_of_birth,
            nationality_id: userCtrl.selectedUserData.nationality_id,
            nationality_name: userCtrl.selectedUserData.nationality_name,
            username: userCtrl.selectedUserData.username,
            is_homeroom: userCtrl.selectedUserData.is_homeroom, //POCOR-5070
            password: userCtrl.isInternalSearchSelected ? '' : userCtrl.selectedUserData.password,
            postal_code: userCtrl.selectedUserData.postalCode,
            address: userCtrl.selectedUserData.address,
            birthplace_area_id: directorySvc.getBirthplaceAreaId() == null ? userCtrl.selectedUserData.birthplace_area_id : directorySvc.getBirthplaceAreaId(),
            address_area_id: directorySvc.getAddressAreaId() == null ? userCtrl.selectedUserData.address_area_id : directorySvc.getAddressAreaId(),
            identity_type_id: userCtrl.selectedUserData.identity_type_id,
            identity_type_name: userCtrl.selectedUserData.identity_type_name,
            identity_number: userCtrl.selectedUserData.identity_number,
            contact_type: userCtrl.selectedUserData.contact_type_id,
            contact_value: userCtrl.selectedUserData.contact_value,
            email: userCtrl.selectedUserData.email,
            mobile_number: userCtrl.selectedUserData.mobile_number,
            start_date: userCtrl.selectedUserData.startDate,
            end_date: userCtrl.selectedUserData.endDate ? $filter('date')(userCtrl.selectedUserData.endDate, 'yyyy-MM-dd') : '',
            institution_position_id: userCtrl.institutionPositionOptions.selectedOption ? userCtrl.institutionPositionOptions.selectedOption.value : null,
            position_type_id: userCtrl.selectedUserData.position_type_id,
            staff_position_grade_id: userCtrl.selectedUserData.staff_position_grade_id,//POCOR-5069
            staff_type_id: userCtrl.selectedUserData.staff_type_id,
            fte: userCtrl.selectedUserData.fte_id,
            shift_ids: userCtrl.staffShiftsId,
            photo_name: userCtrl.selectedUserData.photo_name,
            photo_base_64: userCtrl.selectedUserData.photo_base_64,
            sync_status: userCtrl.isExternalSearchSelected ? 1 : 0, //POCOR-9590: external search → Synced, manual add → Local
            institution_id: userCtrl.institutionId,
            is_same_school: userCtrl.staffData && userCtrl.staffData.is_same_school ? userCtrl.staffData.is_same_school : 0,
            is_diff_school: userCtrl.staffData && userCtrl.staffData.is_diff_school ? userCtrl.staffData.is_diff_school : 0,
            staff_id: userCtrl.staffData && userCtrl.staffData.id ? userCtrl.staffData.id : null,
            previous_institution_id: userCtrl.staffData && userCtrl.staffData.current_enrol_institution_id ? userCtrl.staffData.current_enrol_institution_id : null,
            comment: userCtrl.selectedUserData.comment,
            custom: [],
        };
        userCtrl.customFieldsArray.forEach((customField) => {
            customField.data.forEach((field) => {
                if (field.field_type !== 'CHECKBOX') {
                    let fieldData = {
                        staff_custom_field_id: field.staff_custom_field_id,
                        text_value: "",
                        number_value: null,
                        decimal_value: "",
                        textarea_value: "",
                        time_value: "",
                        date_value: "",
                        file: "",
                        institution_id: userCtrl.institutionId,
                    };
                    if (field.field_type === 'TEXT' || field.field_type === 'NOTE') {
                        fieldData.text_value = field.answer;
                    }
                    if (field.field_type === 'TEXTAREA') {
                        fieldData.textarea_value = field.answer;
                    }
                    if (field.field_type === 'NUMBER') {
                        fieldData.number_value = field.answer;
                    }
                    if (field.field_type === 'DECIMAL') {
                        fieldData.decimal_value = String(field.answer);
                    }
                    if (field.field_type === 'DROPDOWN') {
                        fieldData.number_value = Number(field.answer);
                    }
                    if (field.field_type === 'TIME') {
                        let time = field.answer.toLocaleTimeString();
                        let timeArray = time.split(':');
                        fieldData.time_value = `${timeArray[0]}:${timeArray[1]}`;
                    }
                    if (field.field_type === 'DATE') {
                        fieldData.date_value = $filter('date')(field.answer, 'yyyy-MM-dd');
                    }
                    params.custom.push(fieldData);
                } else {
                    field.answer.forEach((id) => {
                        let fieldData = {
                            staff_custom_field_id: field.staff_custom_field_id,
                            text_value: "",
                            number_value: Number(id),
                            decimal_value: "",
                            textarea_value: "",
                            time_value: "",
                            date_value: "",
                            file: "",
                            institution_id: userCtrl.institutionId,
                        };
                        params.custom.push(fieldData);
                    });
                }
            })
        });
        UtilsSvc.isAppendLoader(true);
        // console.log(params);
        userSvc.saveStaffDetails(params).then(function (resp) {
            // console.log(resp);
            UtilsSvc.isAppendLoader(false);

            if (resp.data.staff.staff_id === undefined) {
                if (resp.data.staff.error !== undefined) {
                    userCtrl.message = resp.data.staff.error;
                } else {
                    userCtrl.message = 'Staff is not added. Check for errors.';
                }
                userCtrl.messageClass = 'alert-danger';
                UtilsSvc.isAppendLoader(false);
                return;
            }
            if (
                userCtrl.staffData
                && userCtrl.staffData.current_enrol_institution_name != ""
                && userCtrl.staffData.is_diff_school > 0) {
                userCtrl.message = 'Staff transfer request is added successfully.';
                userCtrl.messageClass = 'alert-success';
                $window.history.back();
            } else {
                userCtrl.message = 'Staff is added successfully.';
                userCtrl.messageClass = 'alert-success';
                userCtrl.step = "summary";
                var todayDate = new Date();
                userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
            }
        }, function (error) {
            console.error(error);
            userCtrl.message =  error.data.message || error.statusText || error.toString();
            userCtrl.messageClass = 'alert-danger';
            UtilsSvc.isAppendLoader(false);
        });
    }

    function processGridUserRecord(userRecords, params, totalRowCount) {
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

    function getPositions() {
        if (!userCtrl.selectedUserData.position_type_id || !userCtrl.selectedUserData.fte_id)
            return;
        UtilsSvc.isAppendLoader(true);
        var params = {
            institution_id: userCtrl.institutionId,
            fte: userCtrl.selectedUserData.position_type_id === 'Full-Time' ? 1 : Number(userCtrl.selectedUserData.fte_id),
            startDate: userCtrl.selectedUserData.startDate ? $filter('date')(userCtrl.selectedUserData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
            endDate: userCtrl.selectedUserData.endDate ? $filter('date')(userCtrl.selectedUserData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
            openemis_no: userCtrl.selectedUserData.openemis_no,
        };
        userSvc.getPositions(params).then(function (resp) {
            resp.data = resp.data.filter((data) => data.disabled === false)
            userCtrl.institutionPositionOptions.availableOptions = resp.data;
            userCtrl.institutionPositionOptions.selectedOption = null;
            UtilsSvc.isAppendLoader(false);
        }, function (error) {
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    userCtrl.getStaffCustomFields = function() {
        let userId = userCtrl.selectedUserData.userId ? userCtrl.selectedUserData.userId : null;
        // console.log(userId);
        directorySvc.getStaffCustomFields(userId).then(function(resp){
            // console.log(resp)
            userCtrl.customFields = resp.data;
            userCtrl.customFieldsArray = [];
            userCtrl.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    userCtrl.createCustomFieldsArray = function() {
        directorySvc.createCustomFieldsArray(userCtrl);
    }

    function onDecimalNumberChange(field) {
        let timer;
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => {
            field.answer = parseFloat(field.answer.toFixed(field.params.precision));
        }, 3000);
    }

    function changeOption(field, optionId) {
        field.option.forEach((option) => {
            if (option.option_id === optionId) {
                field.selectedOption = option.option_name;
            }
        })
    }

    function changeContactType() {
        var contactTypeId = userCtrl.selectedUserData.contact_type_id;
        var options = userCtrl.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactTypeId) {
                userCtrl.selectedUserData.contact_type_name = options[i].name;
                userCtrl.selectedUserData.contact_value = "";
                break;
            }
        }
    }

    function getContactTypes() {
        userSvc.getContactTypes()
            .then(function (response) {
                // console.log(response)
                userCtrl.contactTypeOptions = response.data;
                UtilsSvc.isAppendLoader(false);
            }, function (error) {
                console.error(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    function selectOption(field) {
        field.answer = [];
        field.option.forEach((option) => {
            if (option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    function setStaffName() {
        var staffData = userCtrl.selectedUserData;
        staffData.name = '';

        if (staffData.hasOwnProperty('first_name')) {
            staffData.name = staffData.first_name.trim();
        }
        userCtrl.appendName(staffData, 'middle_name', true);
        userCtrl.appendName(staffData, 'third_name', true);
        userCtrl.appendName(staffData, 'last_name', true);
        userCtrl.selectedUserData = staffData;
    }

    function appendName(staffObj, variableName, trim) {
        if (staffObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                staffObj[variableName] = staffObj[variableName].trim();
            }
            if (staffObj[variableName] != null && staffObj[variableName] != '') {
                staffObj.name = staffObj.name + ' ' + staffObj[variableName];
            }
        }
        return staffObj;
    }

    function changePositionType() {
        userCtrl.institutionPositionOptions.selectedOption = null;
        userCtrl.selectedUserData.institution_position_id = null;
        userCtrl.selectedUserData.fte_id = null;
        var positionType = userCtrl.selectedUserData.position_type_id;
        var positionTypeOptions = userCtrl.positionTypeOptions;
        for (var i = 0; i < positionTypeOptions.length; i++) {
            if (positionTypeOptions[i].id == positionType) {
                userCtrl.selectedUserData.position_type_name = positionTypeOptions[i].name;
                break;
            }
        }
        if (positionType === 'Full-Time') {
            userCtrl.selectedUserData.fte_id = 1;
            userCtrl.selectedUserData.fte_name = '100%';
            userCtrl.getPositions();
        }
    }

    function changePosition() {


        const selectedPositionId = userCtrl.institutionPositionOptions.selectedOption?.value;
        userCtrl.selectedUserData.institution_position_id = selectedPositionId;

        if (!selectedPositionId) {
            return;
        }

        const positionOptions = userCtrl.institutionPositionOptions.availableOptions;


        const selectedPosition = positionOptions.find(option => option.value === selectedPositionId);

        if (selectedPosition) {
            userCtrl.selectedUserData.position_name = selectedPosition.name;
            userCtrl.getStaffPosititonGrades().then(() => {
                console.log('Staff position grades loaded successfully.');
            }).catch(error => {
                console.error('Error loading staff position grades:', error);
            });
        } else {
            userCtrl.selectedUserData.position_name = "";
            console.log('Position not found in options');
        }
    }

    function changeStaffType() {
        var staffType = userCtrl.selectedUserData.staff_type_id;
        var staffTypeOptions = userCtrl.staffTypeOptions;
        for (var i = 0; i < staffTypeOptions.length; i++) {
            if (staffTypeOptions[i].id == staffType) {
                userCtrl.selectedUserData.staff_type_name = staffTypeOptions[i].name;
                break;
            }
        }
    }

    //POCOR-5069 starts
    function changeStaffGradePosition() {
        var staffPositionGrades = userCtrl.selectedUserData.staff_position_grade_id;
        var staffGradePositionOptions = userCtrl.staffGradePositionOptions;
        for (var i = 0; i < staffGradePositionOptions.length; i++) {
            if (staffGradePositionOptions[i].id == staffPositionGrades) {
                userCtrl.selectedUserData.staff_position_grades_name = staffGradePositionOptions[i].name;
                break;
            }
        }
    }//POCOR-5069 ends

    function changeFte() {
        userCtrl.institutionPositionOptions.selectedOption = null;
        userCtrl.selectedUserData.institution_position_id = null;
        var fte = userCtrl.selectedUserData.fte_id;
        var fteOptions = userCtrl.fteOptions;
        for (var i = 0; i < fteOptions.length; i++) {
            if (fteOptions[i].id == fte) {
                userCtrl.selectedUserData.fte_name = fteOptions[i].name;
                break;
            }
        }
        userCtrl.getPositions();
    }

    function goToInternalSearch() {
        userCtrl.selectedUserData.userType = 'Staff';
        userCtrl.selectedUserData.user_type_id = 2;
        userCtrl.selectedUserData.institution_id = userCtrl.institutionId; // POCOR-8532
        // console.log(userCtrl.selectedUserData);
        directorySvc.goToInternalSearch(userCtrl);
    };

    function goToExternalSearch () {
        directorySvc.goToExternalSearch(userCtrl);
    }

    function goToPrevStep() {
        if (userCtrl.isInternalSearchSelected) {
            userCtrl.isInternalSearchSelected = false;
            userCtrl.step = 'user_details';
            userCtrl.internalGridOptions = null;
            // userCtrl.goToInternalSearch();
        } else if (userCtrl.isExternalSearchSelected) {
            userCtrl.step = 'external_search';
            userCtrl.externalGridOptions = null;
            userCtrl.goToExternalSearch();
        } else {
            switch (userCtrl.step) {
                case 'internal_search': {
                    userSvc.formatDate(userCtrl.selectedUserData.date_of_birth).then(function (formattedDate) {
                        userCtrl.selectedUserData.date_of_birth = formattedDate;
                    });
                    userCtrl.step = 'user_details';
                    if (userCtrl.isSearchResultEmpty) {
                        userCtrl.selectedUserData.openemis_no = "";
                    }
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
                case 'add_staff':
                    userCtrl.step = 'confirmation';
                    break;
            }
        }
    }

    async function validateDetails() {
        userCtrl.error = {};
        if (userCtrl.step === 'user_details') {
            let [blockName, hasError] = checkUserDetailValidationBlocksHasError();//POCOR-8071

            userCtrl.unsetAllErrors();

            if (blockName === 'Identity' && hasError) {
                if (!userCtrl.selectedUserData.nationality_id) {
                    userCtrl.error.nationality_id = 'This field cannot be left empty';
                }
                if (!userCtrl.selectedUserData.identity_type_id) {
                    userCtrl.error.identity_type_id = 'This field cannot be left empty';
                }
                if (!userCtrl.selectedUserData.identity_number) {
                    userCtrl.error.identity_number = 'This field cannot be left empty';
                }

            } else if (blockName === "General_Info" && hasError) {
                if (!userCtrl.selectedUserData.first_name) {
                    userCtrl.error.first_name = 'This field cannot be left empty';
                }
                if (!userCtrl.selectedUserData.last_name) {
                    userCtrl.error.last_name = 'This field cannot be left empty';
                }
                if (!userCtrl.selectedUserData.gender_id) {
                    userCtrl.error.gender_id = 'This field cannot be left empty';
                }
                if (!userCtrl.selectedUserData.date_of_birth) {
                    userCtrl.error.date_of_birth = 'This field cannot be left empty';
                } else {
                    userCtrl.selectedUserData.date_of_birth = $filter('date')(userCtrl.selectedUserData.date_of_birth, 'yyyy-MM-dd');
                }
                if (userCtrl.isMaximizeAge) {
                    userCtrl.error.date_of_birth = userCtrl.ageMessage;//POCOR-8071
                }
            } else if (blockName === "General_Info_Age" && hasError) {
                if (userCtrl.isMaximizeAge) {
                    userCtrl.error.date_of_birth = userCtrl.ageMessage;//POCOR-8071
                } else {
                    hasError = false;
                }
            }
            if (hasError) return;
            userCtrl.step = 'internal_search';
            userCtrl.internalGridOptions = null;
            userCtrl.goToInternalSearch();
            // await checkUserAlreadyExistByIdentity();
        }

        if (userCtrl.step === 'add_staff') {
            let shouldPositionRequired = false;
            let isCustomFieldNotValidated = false;
            if (!userCtrl.selectedUserData.startDate) {
                userCtrl.error.start_date = 'This field cannot be left empty';
            } else {
                userCtrl.selectedUserData.startDate = $filter('date')(userCtrl.selectedUserData.startDate, 'yyyy-MM-dd');
            }
            if (!userCtrl.selectedUserData.position_type_id) {
                userCtrl.error.position_type_id = 'This field cannot be left empty';
            }
            if (userCtrl.selectedUserData.fte_id === 'Part-Time' && !userCtrl.selectedUserData.position_type_id) {
                userCtrl.error.fte_id = 'This field cannot be left empty';
            }//POCOR-5069 starts
            if (!userCtrl.selectedUserData.institution_position_id) {
                userCtrl.error.institution_position_id = 'This field cannot be left empty';
            }
            if (!userCtrl.selectedUserData.is_homeroom) {
                userCtrl.error.is_homeroom = 'This field cannot be left empty';
            }
            if (!userCtrl.selectedUserData.staff_position_grade_id) {
                userCtrl.error.staff_position_grade_id = 'This field cannot be left empty';
            }//POCOR-5069 ends
            if (!userCtrl.selectedUserData.staff_type_id) {
                userCtrl.error.staff_type_id = 'This field cannot be left empty';
            }
            // if (userCtrl.staffShiftsId.length === 0) {
            //     userCtrl.error.staffShiftsId = 'This field cannot be left empty';
            // }
            userCtrl.institutionPositionOptions.availableOptions.forEach((option) => {
                if (!option.disabled) {
                    shouldPositionRequired = true;
                }
            });
            if (shouldPositionRequired && !userCtrl.institutionPositionOptions.selectedOption) {
                userCtrl.error.institution_position_id = 'This field cannot be left empty';
            }
            userCtrl.customFieldsArray.forEach((customField) => {
                customField.data.forEach((field) => {
                    if (field.is_mandatory === 1) {
                        if (field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'DROPDOWN' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL' || field.field_type === 'DATE' || field.field_type === 'TIME') {
                            if (!field.answer) {
                                field.errorMessage = 'This field is required.';
                                isCustomFieldNotValidated = true;
                            }
                        } else if (field.field_type === 'CHECKBOX') {
                            if (field.answer.length === 0) {
                                field.errorMessage = 'This field is required.';
                                isCustomFieldNotValidated = true;
                            }
                        }
                    }
                })
            });
            if (Object.keys(userCtrl.error).length > 0) { // Check if error object is not empty
                console.error(userCtrl.error);
                return;
            }
            if (!userCtrl.selectedUserData.startDate
                || !userCtrl.selectedUserData.position_type_id || !userCtrl.selectedUserData.staff_position_grade_id || !userCtrl.selectedUserData.staff_type_id || !userCtrl.staffShiftsId.length === 0 || userCtrl.error.fte_id
                || userCtrl.error.institution_position_id
                || isCustomFieldNotValidated) { //POCOR-5069 add staff_position_grade_id condition
                return;
            }
            if (
                userCtrl.staffData
                && userCtrl.staffData.current_enrol_institution_name != ""
                && userCtrl.staffData.is_diff_school > 0) {
                userCtrl.step = 'summary';
                userCtrl.messageClass = 'alert-warning';
                userCtrl.message = `Staff is currently assigned to ${userCtrl.staffData.currentlyAssignedTo}`
            } else {
                const record = userCtrl.saveStaffDetails();
            }
        }
    }

    async function goToNextStep() {
        if (userCtrl.step === 'confirmation') {
            const result = await userCtrl.checkUserExistByIdentityFromConfiguration();
        }
        // POCOR-9470 start
        switch (userCtrl.step) {
            case 'user_details':
                userCtrl.checkUserAge();
                break;
            case 'internal_search': {
                if (userCtrl.isExternalSearchEnable && !userCtrl.isInternalSearchSelected) {
                    userCtrl.step = 'external_search';
                    userCtrl.externalGridOptions = null;
                    userCtrl.goToExternalSearch();
                } else {
                    if (userCtrl.staffData && userCtrl.staffData.is_diff_school > 0) {
                        userCtrl.messageClass = 'alert-warning';
                        userCtrl.message = `This staff is already allocated to ${userCtrl.staffData.current_enrol_institution_code} - ${userCtrl.staffData.current_enrol_institution_name}`;
                        userCtrl.step = 'add_staff';
                    } else {
                        userCtrl.processNewUser();
                    }
                }
                // POCOR-9470 end
                return;
            }
            case 'external_search':
                userCtrl.processNewUser();
                break;
            case 'confirmation':
                userCtrl.validateAdditionalDetails();
                break;

        }
    }

    userCtrl.processNewUser = function () {
        userCtrl.step = 'confirmation';
        UtilsSvc.isAppendLoader(true);

        userCtrl.getUniqueOpenEmisId()
            .then(() => {
                // console.log(userCtrl.selectedUserData.openemis_no)
                return userCtrl.generatePassword();
            })
            .then(() => {
                    userCtrl.selectedUserData.userType = {};
                    userCtrl.selectedUserData.userType.name = 'Staff';
                    return userCtrl.getStaffCustomFields();
            })
            .catch(error => {
                UtilsSvc.isAppendLoader(false);
                userCtrl.messageClass = 'alert-danger';
                userCtrl.message = error.message || error.toString();
                console.error(error);
            })
            .then(() => {
                UtilsSvc.isAppendLoader(false);
            });
    };

    async function validateAdditionalDetails() {
        // const [blockName, hasError] = checkAdditionalDetailValidationBlocksHasError();
        userCtrl.unsetAllErrors();
        let hasError = false;
        const selectedUserData = userCtrl.selectedUserData;
        // POCOR-9427 start
        const setError = (field, message) => {
            userCtrl.error[field] = message;
            hasError = true;
        };
        const user_exists = await checkUserAlreadyExistByIdentity();
        if(!userCtrl.isInternalSearchSelected && user_exists){
            // setError('identity_type_id', 'User already exist with this identity type');
            setError('identity_number', 'User already exist with this identity');
            // setError('nationality_id', 'User already exist with this nationality');
        }
        // POCOR-9427 end
        if (!userCtrl.nationalitySkipped &&
            userCtrl.nationalitiesRequired === 'required' &&
            !selectedUserData.nationality_id) {
            userCtrl.error.nationality_id = 'This field cannot be left empty';
            hasError = true;
        }
        if (!userCtrl.identitySkipped &&
            userCtrl.identitiesRequired === 'required' &&
            !selectedUserData.identity_type_id) {
            userCtrl.error.identity_type_id = 'This field cannot be left empty';
            hasError = true;
        }
        if (!userCtrl.identitySkipped &&
            userCtrl.identitiesRequired === 'required' &&
            !selectedUserData.identity_number) {
            userCtrl.error.identity_number = 'This field cannot be left empty';
            hasError = true;
        }
        if (!userCtrl.emailSkipped &&
            userCtrl.emailRequired === 'required' &&
            !selectedUserData.email) {
            userCtrl.error.email = 'This field cannot be left empty';
            hasError = true;
        }
        if (!userCtrl.mobileSkipped &&
            userCtrl.mobileRequired === 'required' &&
            !selectedUserData.mobile_number) {
            userCtrl.error.mobile_number = 'This field cannot be left empty';
            hasError = true;
        }
        //POCOR-9442 start
        userCtrl.customFieldsArray.forEach((customField) => {
            customField.data.forEach((field) => {
                if (field.is_mandatory === 1) {
                    if (field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'DROPDOWN' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL' || field.field_type === 'DATE' || field.field_type === 'TIME') {
                        if (!field.answer) {
                            field.errorMessage = 'This field is required.';
                            isCustomFieldNotValidated = true;
                            hasError = true;
                        }
                    } else if (field.field_type === 'CHECKBOX') {
                        if (field.answer.length === 0) {
                            field.errorMessage = 'This field is required.';
                            isCustomFieldNotValidated = true;
                            hasError = true;
                        }
                    }
                }
            })
        });
        //POCOR-9442 end

        if (hasError) {
            return;
        }
        userCtrl.step = 'add_staff';
        userCtrl.generatePassword();
    }

    function confirmUser() {
        userCtrl.message = (userCtrl.selectedUserData && userCtrl.selectedUserData.userType ? userCtrl.selectedUserData.userType.name : 'Staff') + ' successfully added.';
        userCtrl.messageClass = 'alert-success';
        userCtrl.step = "summary";
    }

    function goToFirstStep() {
        userCtrl.step = 'user_details';
        userCtrl.selectedUserData = {};
    }

    function cancelProcess() {
        $window.history.back();
    }

    userCtrl.selectUserFromInternalSearch = function (id) {
        userCtrl.selectedUser = id;
        userCtrl.isInternalSearchSelected = true;
        userCtrl.isExternalSearchSelected = false;
        userCtrl.getUserData();

        if (userCtrl.isIdentityUserExist) {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
        }

        userCtrl.disableFields = {
            username: true,
            password: true
        };
    }

    userCtrl.selectUserFromExternalSearch = function (id) {
        userCtrl.selectedUser = id;
        userCtrl.isInternalSearchSelected = false;
        userCtrl.isExternalSearchSelected = true;
        userCtrl.getUserData();
        userCtrl.disableFields = {
            username: false,
            password: false,
        }
    }

    userCtrl.getUserData = function () {
        var log = [];

        angular.forEach(userCtrl.rowsThisPage, function (value) {
            if (value.id == userCtrl.selectedUser) {
                userCtrl.staffData = value;
                if (userCtrl.isInternalSearchSelected) {
                    userCtrl.staffStatus = 'Assigned';

                    // POCOR-5672 : fixed showing wrong institution name
                    userCtrl.staffData.currentlyAssignedTo = value.current_enrol_institution_code + ' - ' + value.current_enrol_institution_name;

                    userCtrl.staffData.requestedBy = value.institution_code + ' - ' + value.institution_name;

                }
                userCtrl.setUserData(value);
            }
        }, log);
    }

    function setUserData(selectedData) {
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
        userCtrl.selectedUserData.name = selectedData.name; // POCOR-8532
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
        userCtrl.selectedUserData.photo_name = selectedData.photo_name;
        userCtrl.selectedUserData.photo_base_64 = selectedData.photo_content;
        if (selectedData.identity_number) {
            userCtrl.canSkipIdentity = true;
        }
        if (selectedData.nationality_id) {
            userCtrl.canSkipNationality = true;
        }
        userCtrl.selectedUserData.contact_type_id = selectedData.contact_type_id; // POCOR-8012-n
        userCtrl.selectedUserData.contact_value = selectedData.contact_value; // POCOR-8012-n

        userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        userCtrl.selectedUserData.password = selectedData.password;
        userCtrl.selectedUserData.address = selectedData.address;
        userCtrl.selectedUserData.postalCode = selectedData.postal_code;
        userCtrl.selectedUserData.address_area_id = selectedData.address_area_id;
        userCtrl.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
        userCtrl.selectedUserData.addressArea = {name: selectedData.area_name};
        userCtrl.selectedUserData.birthplaceArea = {name: selectedData.birth_area_name};
        userCtrl.selectedUserData.userId = selectedData.id;
        if($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        $window.localStorage.setItem('birthplace_area_id', selectedData.birthplace_area_id);
        $window.localStorage.setItem('address_area_id', selectedData.address_area_id);
        if($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        $window.localStorage.setItem('birthplace_area', JSON.stringify({id: selectedData.birthplace_area_id, name: selectedData.birth_area_name}));
        $window.localStorage.setItem('address_area', JSON.stringify({
            id: selectedData.address_area_id,
            name: selectedData.area_name
        }));
        userCtrl.addressAreaId = selectedData.address_area_id;
        userCtrl.birthplaceAreaId = selectedData.birthplace_area_id;

        if (selectedData.address_area_id) {
            document.getElementById('addressArea_textbox').style.visibility = 'visible';
            document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
        } else
        {
            document.getElementById('addressArea_textbox').style.display = 'none';
            document.getElementById('addressArea_dropdown').style.visibility = 'visible';
        }

        if (selectedData.birthplace_area_id) {
            document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
        } else
        {
            document.getElementById('birthplaceArea_textbox').style.display = 'none';
            document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
        }
        userCtrl.selectedUserData.currentlyAssignedTo = selectedData.current_enrol_institution_code + ' - ' + selectedData.current_enrol_institution_name;
        userCtrl.selectedUserData.requestedBy = selectedData.institution_code + ' - ' + selectedData.institution_name;
        userCtrl.selectedUserData.is_same_school = selectedData.is_same_school;
        userCtrl.selectedUserData.is_diff_school = selectedData.is_diff_school;

    }

    function setUserDataFromExternalSearchData(selectedData) {
        // DOCS: Demo nationality_number for test usage : 9791048083
        if (userCtrl.externalSearchSourceName == 'Jordan CSPD') {
            userSvc.getUniqueOpenEmisId().then((response) => {
                const selectedObjectWithOpenemisNo =  Object.assign({}, selectedData, {'openemis_no':response})
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
                if (selectedData.identity_number) {
                    userCtrl.canSkipIdentity = true;
                }
                if (selectedData.nationality) {
                    userCtrl.canSkipNationality = true;
                }
                userCtrl.selectedUserData.nationality_name = selectedData.nationality;
                userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
                userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
                userCtrl.selectedUserData.identity_number = selectedData.identity_number;
                userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                userCtrl.selectedUserData.password = selectedData.password;
                userCtrl.selectedUserData.address = selectedData.address;
                userCtrl.selectedUserData.postalCode = selectedData.postal_code;

                if (selectedData.address_area_id > 0)
                {
                    document.getElementById('addressArea_textbox').style.visibility = 'visible';
                    document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
                } else
                {
                    document.getElementById('addressArea_textbox').style.display = 'none';
                    document.getElementById('addressArea_dropdown').style.visibility = 'visible';
                }

                if (selectedData.birthplace_area_id > 0)
                {
                    document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
                } else
                {
                    document.getElementById('birthplaceArea_textbox').style.display = 'none';
                    document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
                }
            })

        }else{
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

            if (selectedData.address_area_id > 0)
            {
                document.getElementById('addressArea_textbox').style.visibility = 'visible';
                document.getElementById('addressArea_dropdown').style.visibility = 'hidden';
            } else
            {
                document.getElementById('addressArea_textbox').style.display = 'none';
                document.getElementById('addressArea_dropdown').style.visibility = 'visible';
            }

            if (selectedData.birthplace_area_id > 0)
            {
                document.getElementById('birthplaceArea_textbox').style.visibility = 'visible';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'hidden';
            } else
            {
                document.getElementById('birthplaceArea_textbox').style.display = 'none';
                document.getElementById('birthplaceArea_dropdown').style.visibility = 'visible';
            }
        }


    }

    function initGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function (localeText) {
                userCtrl.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: userCtrl.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.account_type,
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
                        userCtrl.selectStaffFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                };

                userCtrl.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: userCtrl.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
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
                        userCtrl.selectStaffFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                };
            }, function (error) {
                userCtrl.internalGridOptions = {
                    columnDefs: [
                        {
                            headerName: userCtrl.translateFields.openemis_no,
                            field: "openemis_no",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_number,
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
                        userCtrl.selectStaffFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                };

                userCtrl.externalGridOptions = {
                    columnDefs: [
                        {
                            headerName: userCtrl.translateFields.name,
                            field: "name",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.gender_name,
                            field: "gender",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.date_of_birth,
                            field: "date_of_birth",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.nationality_name,
                            field: "nationality",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_type_name,
                            field: "identity_type",
                            suppressMenu: true,
                            suppressSorting: true
                        },
                        {
                            headerName: userCtrl.translateFields.identity_number,
                            field: "identity_number",
                            suppressMenu: true,
                            suppressSorting: true
                        }
                    ],
                    localeText: localeText,
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
                        userCtrl.selectStaffFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                };
            });
    };

    function reloadInternalDatasource(withData) {
        if (withData !== false) {
            userCtrl.showExternalSearchButton = true;
        }
        userSvc.resetExternalVariable();
        delete userCtrl.selectedStaff;
        userCtrl.staffPositionGradeId = ''; //POCOR-5069
        userCtrl.staffTypeId = '';
        userCtrl.positionType = '';
        userCtrl.endDate = '';
        userCtrl.onChangeAcademicPeriod();
        userCtrl.onChangePositionType();
        userCtrl.createNewInternalDatasource(userCtrl.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        userSvc.resetExternalVariable();
        delete userCtrl.selectedStaff;
        userCtrl.staffPositionGradeId = '';//POCOR-5069
        userCtrl.staffTypeId = '';
        userCtrl.positionType = '';
        userCtrl.endDate = '';
        userCtrl.onChangeAcademicPeriod();
        userCtrl.onChangePositionType();
        userCtrl.createNewExternalDatasource(userCtrl.externalGridOptions, withData);
    };

    function clearInternalSearchFilters() {
        userCtrl.internalFilterOpenemisNo = '';
        userCtrl.internalFilterFirstName = '';
        userCtrl.internalFilterLastName = '';
        userCtrl.internalFilterIdentityNumber = '';
        userCtrl.internalFilterDateOfBirth = '';
        userCtrl.initialLoad = true;
        userCtrl.createNewInternalDatasource(userCtrl.internalGridOptions);
    }

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                // AlertSvc.reset($scope); // POCOR-4009 commented out due to alert class not appear (only white text message appeared) when there is an empty field.
                if (withData) {
                    userSvc.getStaffRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                openemis_no: userCtrl.internalFilterOpenemisNo,
                                first_name: userCtrl.internalFilterFirstName,
                                last_name: userCtrl.internalFilterLastName,
                                identity_number: userCtrl.internalFilterIdentityNumber,
                                date_of_birth: userCtrl.internalFilterDateOfBirth,
                            }
                        }
                    )
                        .then(function (response) {
                            if (response.conditionsCount == 0) {
                                userCtrl.initialLoad = true;
                            } else {
                                userCtrl.initialLoad = false;
                            }
                            var staffRecords = response.data;
                            var totalRowCount = response.total;
                            return userCtrl.processStaffRecord(staffRecords, params, totalRowCount);
                        }, function (error) {
                            console.error(error);
                            AlertSvc.warning($scope, error);
                        });
                } else {
                    userCtrl.rowsThisPage = [];
                    params.successCallback(userCtrl.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function createNewExternalDatasource(gridObj, withData) {
        userCtrl.externalDataLoaded = false;
        userCtrl.initialLoad = true;
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                if (withData) {
                    userSvc.getExternalStaffRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                first_name: userCtrl.internalFilterFirstName,
                                last_name: userCtrl.internalFilterLastName,
                                identity_number: userCtrl.internalFilterIdentityNumber,
                                date_of_birth: userCtrl.internalFilterDateOfBirth
                            }
                        }
                    )
                        .then(function (response) {
                            var staffRecords = response.data;
                            var totalRowCount = response.total;
                            userCtrl.initialLoad = false;
                            return userCtrl.processExternalStaffRecord(staffRecords, params, totalRowCount);
                        }, function (error) {
                            console.error(error);
                            var status = error.status;
                            if (status == '401') {
                                var message = 'You have not been authorised to fetch from external data source.';
                                AlertSvc.warning($scope, message);
                            } else {
                                var message = 'External search failed, please contact your administrator to verify the external search attributes';
                                AlertSvc.warning($scope, message);
                            }
                            var staffRecords = [];
                            userSvc.init(angular.baseUrl);
                            return userCtrl.processExternalStaffRecord(staffRecords, params, 0);
                        })
                        .finally(function (res) {
                            userSvc.init(angular.baseUrl);
                        });
                } else {
                    userCtrl.rowsThisPage = [];
                    params.successCallback(userCtrl.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function processExternalStaffRecord(staffRecords, params, totalRowCount) {
        for (var key in staffRecords) {
            var mapping = userSvc.getExternalSourceMapping();
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            userSvc.formatDate(staffRecords[key][mapping.date_of_birth_mapping]).then(function (formattedDate) {
                staffRecords[key]['date_of_birth'] = formattedDate;
            });
            staffRecords[key]['gender_name'] = staffRecords[key][mapping.gender_mapping];
            staffRecords[key]['gender'] = {'name': staffRecords[key][mapping.gender_mapping]};
            staffRecords[key]['identity_type_name'] = staffRecords[key][mapping.identity_type_mapping];
            staffRecords[key]['identity_number'] = staffRecords[key][mapping.identity_number_mapping];
            staffRecords[key]['nationality_name'] = staffRecords[key][mapping.nationality_mapping];
            staffRecords[key]['address'] = staffRecords[key][mapping.address_mapping];
            staffRecords[key]['postal_code'] = staffRecords[key][mapping.postal_mapping];
            staffRecords[key]['name'] = '';
            if (staffRecords[key].hasOwnProperty(mapping.first_name_mapping)) {
                staffRecords[key]['name'] = staffRecords[key][mapping.first_name_mapping];
            }
            userCtrl.appendName(staffRecords[key], mapping.middle_name_mapping);
            userCtrl.appendName(staffRecords[key], mapping.third_name_mapping);
            userCtrl.appendName(staffRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        userCtrl.rowsThisPage = staffRecords;

        params.successCallback(userCtrl.rowsThisPage, lastRow);
        userCtrl.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function processStaffRecord(staffRecords, params, totalRowCount) {
        // console.log(staffRecords);
        for (var key in staffRecords) {
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            if ((staffRecords[key].hasOwnProperty('institution_students') && staffRecords[key]['institution_students'].length > 0)) {
                staffRecords[key]['institution_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('institution'))) ? staffRecords[key].institution_students['0'].institution.name : '-';
                staffRecords[key]['academic_period_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('academic_period'))) ? staffRecords[key].institution_students['0'].academic_period.name : '-';
                staffRecords[key]['education_grade_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('education_grade'))) ? staffRecords[key].institution_students['0'].education_grade.name : '-';
            }

            if (staffRecords[key]['main_nationality'] != null) {
                staffRecords[key]['nationality_name'] = staffRecords[key]['main_nationality']['name'];
            }
            if (staffRecords[key]['main_identity_type'] != null) {
                staffRecords[key]['identity_type_name'] = staffRecords[key]['main_identity_type']['name'];
            }

            userSvc.formatDate(staffRecords[key]['date_of_birth']).then(function (formattedDate) {
                staffRecords[key]['date_of_birth'] = formattedDate;
            });
            staffRecords[key]['gender_name'] = staffRecords[key]['gender']['name'];
            if (staffRecords[key]['is_student'] == 1 && staffRecords[key]['is_staff'] == 1) {
                staffRecords[key]['account_type'] = 'Student, Staff';
            } else if (staffRecords[key]['is_student'] == 1 && staffRecords[key]['is_staff'] == 0) {
                staffRecords[key]['account_type'] = 'Student';
            } else if (staffRecords[key]['is_student'] == 0 && staffRecords[key]['is_staff'] == 1) {
                staffRecords[key]['account_type'] = 'Staff';
            }

            if (!staffRecords[key].hasOwnProperty('name')) {
                staffRecords[key]['name'] = '';
                if (staffRecords[key].hasOwnProperty('first_name')) {
                    staffRecords[key]['name'] = staffRecords[key]['first_name'];
                }
                userCtrl.appendName(staffRecords[key], 'middle_name');
                userCtrl.appendName(staffRecords[key], 'third_name');
                userCtrl.appendName(staffRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        userCtrl.rowsThisPage = staffRecords;

        params.successCallback(userCtrl.rowsThisPage, lastRow);
        userCtrl.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, userRecord, shiftId = {}, staffPositionGradeId) {//POCOR-5069 add staffPositionGradeId
        UtilsSvc.isAppendLoader(true);
        AlertSvc.reset($scope);

        var data = {
            staff_id: staffId,
            staff_name: staffId,
            institution_position_id: institutionPositionId,
            staff_assignment: true,
            academic_period_id: academicPeriodId,
            position_type: positionType,
            staff_shifts_id: shiftId,
            staff_position_grade_id: staffPositionGradeId,//POCOR-5069
            staff_type_id: staffTypeId,
            FTE: fte,
            start_date: startDate,
            end_date: endDate
        };
        var shiftData = {
            staff_id: staffId,
            shift_id: shiftId,
        };

        // console.log("data",data);
        // console.log("shiftData",shiftData);

        var deferred = $q.defer();

        userSvc.postAssignedStaff(data)
            .then(function (postResponse) {
                userCtrl.postResponse = postResponse.data;
                UtilsSvc.isAppendLoader(false);
                userCtrl.addStaffError = false;
                userCtrl.transferStaffError = false;
                var log = [];
                var counter = 0;
                angular.forEach(postResponse.data.error, function (value) {
                    counter++;
                }, log);

                if (counter == 0) {

                    userSvc.postAssignedStaffShift(shiftData);
                    AlertSvc.success($scope, 'The staff is added successfully.');
                    $window.location.href = 'add?staff_added=true';
                    deferred.resolve(userCtrl.postResponse);
                } else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleReleaseRequestExists')) {
                    AlertSvc.warning($scope, 'There is an existing release record for this staff.');
                    $window.location.href = postResponse.data.error.staff_assignment.ruleReleaseRequestExists;
                    deferred.resolve(userCtrl.postResponse);
                } else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleTransferRequestExists')) {
                    AlertSvc.warning($scope, 'There is an existing transfer record for this staff.');
                    $window.location.href = postResponse.data.error.staff_assignment.ruleTransferRequestExists;
                    deferred.resolve(userCtrl.postResponse);
                } else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleCheckStaffAssignment')) {
                    userSvc.getStaffData(staffId, startDate, endDate)
                        .then(function (response) {
                            userCtrl.selectedStaff = response.id;
                            userCtrl.selectedUserData['institution_staff'] = response.institution_staff;
                            var idName = userCtrl.selectedUserData.openemis_no + ' - ' + userCtrl.selectedUserData.name;
                            var institutionName = userCtrl.selectedUserData['institution_staff'][0]['institution']['code_name'];
                            var currentInstitutionType = userCtrl.selectedUserData['institution_staff'][0]['institution']['institution_type_id'];
                            var currentInstitutionProvider = userCtrl.selectedUserData['institution_staff'][0]['institution']['institution_provider_id'];
                            var newInstitutionType = userCtrl.institutionType;
                            var newInstitutionProvider = userCtrl.institutionProvider;
                            var restrictStaffTransferByTypeConfig = userCtrl.restrictStaffTransferByTypeValue[0]['value'];
                            var restrictStaffTransferByProviderConfig = userCtrl.restrictStaffTransferByProviderValue[0]['value'];

                            if (restrictStaffTransferByTypeConfig == 1 && currentInstitutionType != newInstitutionType) {
                                userCtrl.addStaffError = true;
                                AlertSvc.warning($scope, idName + ' is currently assigned to ' + institutionName + '. Staff transfer between different type is restricted.');
                            } else if (restrictStaffTransferByProviderConfig == 1 && currentInstitutionProvider != newInstitutionProvider) {
                                userCtrl.addStaffError = true;
                                AlertSvc.warning($scope, idName + ' is currently assigned to ' + institutionName + '. Staff transfer between different provider is restricted.');
                            } else {
                                userCtrl.transferStaffError = true;
                                AlertSvc.info($scope, idName + ' is currently assigned to ' + institutionName + '. By clicking save, a transfer request will be sent to the institution for approval');
                            }
                            deferred.resolve(userCtrl.postResponse);
                        }, function (error) {
                            userCtrl.transferStaffError = true;
                            AlertSvc.warning($scope, 'Staff is currently assigned to another Institution.');
                            deferred.resolve(userCtrl.postResponse);
                        });

                } else {
                    userCtrl.addStaffError = true;
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                    deferred.resolve(userCtrl.postResponse);
                }

            }, function (error) {
                console.error(error);
                AlertSvc.warning($scope, error);
                deferred.reject(error);
            });
        return deferred.promise;
    }

    function onAddNewStaffClick() {
        userCtrl.createNewStaff = true;
        userCtrl.completeDisabled = false;
        userCtrl.selectedUserData = {};
        userCtrl.selectedUserData.first_name = '';
        userCtrl.selectedUserData.last_name = '';
        userCtrl.selectedUserData.date_of_birth = '';
        userCtrl.initNationality();
        userCtrl.initIdentityType();
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "createUser"
        });
    }

    function onAddStaffClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "addStaff"
        });
    }

    function onAddStaffCompleteClick() {
        userCtrl.postForm().then(function (response) {
            if (userCtrl.addStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "addStaff"
                });
            } else if (userCtrl.transferStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "transferStaff"
                });
            }
        }, function (error) {
            console.error(error);
            // error handling here
        });
    }

    function onExternalSearchClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "externalSearch"
        });
    }

    function selectStaff(id) {
        userCtrl.selectedStaff = id;
        userCtrl.getStaffData();
    }


    function appendName(studentObj, variableName, trim) {
        if (studentObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                studentObj[variableName] = studentObj[variableName].trim();
            }
            if (studentObj[variableName] != null && studentObj[variableName] != '') {
                studentObj.name = studentObj.name + ' ' + studentObj[variableName];
            }
        }
        return studentObj;
    }

    function changeGender() {
        var staffData = userCtrl.selectedUserData;
        if (staffData.hasOwnProperty('gender_id')) {
            var genderOptions = userCtrl.genderOptions;
            for (var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == staffData.gender_id) {
                    staffData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            userCtrl.selectedUserData = staffData;
        }
    }

    function getStaffData() {
        var log = [];
        angular.forEach(userCtrl.rowsThisPage, function (value) {
            if (value.id == userCtrl.selectedStaff) {
                userCtrl.selectedUserData = value;
            }
        }, log);
    }

    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (userCtrl.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            // userCtrl.startDate = userSvc.formatDate(userCtrl.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Staff_start_date'));
        var endDatePicker = angular.element(document.getElementById('Staff_end_date'));
        userSvc.formatDate(userCtrl.academicPeriodOptions.selectedOption.start_date).then(function (formattedDate) {
            startDatePicker.datepicker("setStartDate", formattedDate);
            endDatePicker.datepicker("setStartDate", formattedDate);
        });
        userSvc.formatDate(userCtrl.academicPeriodOptions.selectedOption.end_date).then(function (formattedDate) {
            startDatePicker.datepicker("setEndDate", formattedDate);
        });
        // startDatePicker.datepicker("setDate", userSvc.formatDate(userCtrl.academicPeriodOptions.selectedOption.start_date));
        userCtrl.onChangeFTE();
    }

    function postTransferForm() {
        var startDate = userCtrl.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for (i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var positionType = userCtrl.positionType;
        var institutionPositionId = (userCtrl.institutionPositionOptions.hasOwnProperty('selectedOption') && userCtrl.institutionPositionOptions.selectedOption != null) ? userCtrl.institutionPositionOptions.selectedOption.value : '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = userCtrl.fte;
        var staffPositionGradeId = (userCtrl.staffPositionGradeId != null && userCtrl.staffPositionGradeId.hasOwnProperty('id')) ? userCtrl.staffPositionGradeId.id : '';//POCOR-5069
        var staffTypeId = (userCtrl.staffTypeId != null && userCtrl.staffTypeId.hasOwnProperty('id')) ? userCtrl.staffTypeId.id : '';
        var data = {
            staff_id: userCtrl.selectedStaff,
            new_start_date: startDate,
            new_end_date: userCtrl.endDate,
            new_staff_position_grade_id: staffPositionGradeId,//POCOR-5069
            new_staff_type_id: staffTypeId,
            new_FTE: fte,
            new_institution_position_id: institutionPositionId,
            status_id: 0,
            assignee_id: -1,
            new_institution_id: userCtrl.institutionId,
            previous_institution_id: userCtrl.selectedUserData.institution_staff[0]['institution']['id'], //POCOR-6909
            // previous_institution_id: userCtrl.selectedUserData['id'],//POCOR-6704
            comment: userCtrl.comment
        };

        userSvc.addStaffTransferRequest(data)
            .then(function (response) {
                var data = response.data;
                if (data.error.length == 0) {
                    AlertSvc.success($scope, 'Staff transfer request is added successfully.');
                    $window.location.href = 'add?staff_transfer_added=true';
                } else if (data.error.hasOwnProperty('staff_assignment') && data.error.staff_assignment.hasOwnProperty('ruleTransferRequestExists')) {
                    AlertSvc.warning($scope, 'There is an existing transfer record for this staff.');
                    $window.location.href = data.error.staff_assignment.ruleTransferRequestExists;
                } else {
                    console.error(response);
                    AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
                }
            }, function (error) {
                console.error(error);
                AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
            })
    }

    function postForm() {
        var deferred = $q.defer();
        // console.log("StaffController"+StaffController);
        var academicPeriodId = (userCtrl.academicPeriodOptions.hasOwnProperty('selectedOption')) ? userCtrl.academicPeriodOptions.selectedOption.id : '';
        var positionType = userCtrl.positionType;
        var institutionPositionId = (userCtrl.institutionPositionOptions.hasOwnProperty('selectedOption') && userCtrl.institutionPositionOptions.selectedOption != null) ? userCtrl.institutionPositionOptions.selectedOption.value : '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = userCtrl.fte;
        var staffPositionGradeId = (userCtrl.staffPositionGradeId != null && userCtrl.staffPositionGradeId.hasOwnProperty('id')) ? userCtrl.staffPositionGradeId.id : '';//POCOR-5069
        var staffTypeId = (userCtrl.staffTypeId != null && userCtrl.staffTypeId.hasOwnProperty('id')) ? userCtrl.staffTypeId.id : '';
        var startDate = userCtrl.startDate;
        var startDateArr = startDate.split("-");
        var shiftId = userCtrl.staffShiftsId;
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for (i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = userCtrl.endDate;

        if (!userCtrl.createNewStaff) {
            if (userCtrl.externalSearch) {
                var staffData = userCtrl.selectedUserData;
                var amendedStaffData = Object.assign({}, staffData);
                userSvc.formatDate(amendedStaffData.date_of_birth).then(function (formattedDate) {
                    amendedStaffData.date_of_birth = formattedDate;
                });
                //POCOR-6576 - added shiftId parameter as shiftId) was missing ealier
                return userCtrl.addStaffUser(amendedStaffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId, staffPositionGradeId);//POCOR-5069 add staffPositionGradeId
            } else {
                var staffId = userCtrl.selectedStaff;
                return userCtrl.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, {}, shiftId, staffPositionGradeId);//POCOR-5069 add staffPositionGradeId
            }
        } else {
            if (userCtrl.selectedUserData != null) {
                var staffData = {};
                var log = [];
                angular.forEach(userCtrl.selectedUserData, function (value, key) {
                    staffData[key] = value;
                }, log);
                if (staffData.hasOwnProperty('date_of_birth')) {
                    var dateOfBirth = staffData.date_of_birth;
                    var dateOfBirthArr = dateOfBirth.split("-");
                    dateOfBirth = dateOfBirthArr[2] + '-' + dateOfBirthArr[1] + '-' + dateOfBirthArr[0];
                    staffData.date_of_birth = dateOfBirth;
                }
                delete staffData['id'];
                delete staffData['institution_staff'];
                delete staffData['is_staff'];
                delete staffData['is_guardian'];
                delete staffData['address'];
                delete staffData['postal_code'];
                delete staffData['address_area_id'];
                delete staffData['birthplace_area_id'];
                delete staffData['date_of_death'];
                staffData['super_admin'] = 0;
                staffData['status'] = 1;
                delete staffData['last_login'];
                delete staffData['photo_name'];
                delete staffData['photo_content'];
                delete staffData['modified'];
                delete staffData['modified_user_id'];
                delete staffData['created'];
                delete staffData['created_user_id'];
                if (staffData['username'] == '') {
                    staffData['username'] = null;
                    staffData['password'] = null;
                } else {
                    staffData['password'] = (staffData['password'] == '') ? null : staffData['password'];
                }
                return userCtrl.addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId, staffPositionGradeId);//POCOR-5069 add staffPositionGradeId
            }
        }
    }

    function addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId, staffPositionGradeId) {//POCOR-5069 add staffPositionGradeId
        var deferred = $q.defer();
        var newStaffData = staffData;

        newStaffData['academic_period_id'] = academicPeriodId;
        newStaffData['start_date'] = startDate;
        if (!userCtrl.externalSearch) {
            newStaffData['nationality_id'] = userCtrl.Staff.nationality_id;
            newStaffData['identity_type_id'] = userCtrl.Staff.identity_type_id;
        }
        newStaffData['position_type'] = positionType;
        newStaffData['institution_position_id'] = institutionPositionId;//POCOR-5069
        newStaffData['staff_type_id'] = staffTypeId;
        newStaffData['staff_position_grade_id'] = staffPositionGradeId;//POCOR-5069
        newStaffData['FTE'] = fte;
        newStaffData['staff_shifts_id'] = shiftId;
        userSvc.addUser(newStaffData)
            .then(function (user) {
                if (user[0].error.length === 0) {
                    var staffId = user[0].data.id;
                    deferred.resolve(userCtrl.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, user[1], shiftId, staffPositionGradeId));//POCOR-5069 add staffPositionGradeId
                } else {
                    userCtrl.postResponse = user[0];
                    AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                    deferred.resolve(userCtrl.postResponse);
                }
            }, function (error) {
                console.error(error);
                deferred.reject(error);
                AlertSvc.warning($scope, error);
            });

        return deferred.promise;
    }


    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function (evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        if (angular.isDefined(userCtrl.postResponse)) {
            delete userCtrl.postResponse;
            $scope.$apply();
        }
        // To go to add student page if there is a student selected from the internal search
        // or external search
        if (data.step == 3 && data.direction == 'next') {
            if (userCtrl.validateNewUser()) {
                evt.preventDefault();
            }
            ;
        }
    });

    function validateNewUser() {
        var remain = false;
        var empty = {'_empty': 'This field cannot be left empty'};
        userCtrl.postResponse = {};
        userCtrl.postResponse.error = {};
        if (userCtrl.selectedUserData.first_name == '') {
            userCtrl.postResponse.error.first_name = empty;
            remain = true;
        }

        if (userCtrl.selectedUserData.last_name == '') {
            userCtrl.postResponse.error.last_name = empty;
            remain = true;
        }
        if (userCtrl.selectedUserData.gender_id == '' || userCtrl.selectedUserData.gender_id == null) {
            userCtrl.postResponse.error.gender_id = empty;
            remain = true;
        }

        if (userCtrl.selectedUserData.date_of_birth == '') {
            userCtrl.postResponse.error.date_of_birth = empty;
            remain = true;
        }

        if (userCtrl.StaffNationalities == 1 && (userCtrl.Staff.nationality_id == '' || userCtrl.Staff.nationality_id == undefined)) {
            remain = true;
        }

        if (userCtrl.selectedUserData.username == '' || userCtrl.selectedUserData.username == undefined) {
            userCtrl.postResponse.error.username = empty;
            remain = true;
        }

        if (userCtrl.selectedUserData.password == '' || userCtrl.selectedUserData.password == undefined) {
            userCtrl.postResponse.error.password = empty;
            remain = true;
        }

        var arrNumber = [{}];

        if (userCtrl.StaffIdentities == 1 && (userCtrl.selectedUserData.identity_number == '' || userCtrl.selectedUserData.identity_number == undefined)) {
            arrNumber[0]['number'] = empty;
            userCtrl.postResponse.error.identities = arrNumber;
            remain = true;
        }

        var arrNationality = [{}];
        if (userCtrl.StaffNationalities == 1 && (userCtrl.Staff.nationality_id == '' || userCtrl.Staff.nationality_id == undefined)) {
            arrNationality[0]['nationality_id'] = empty;
            userCtrl.postResponse.error.nationalities = arrNationality;
            remain = true;
        }

        if (remain) {
            AlertSvc.error($scope, 'Please review the errors in the form.');
            $scope.$apply();
            angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                step: 'createUser'
            });
        }
        return remain;
    }

    userCtrl.getUniqueOpenEmisId = function () {
        return directorySvc.setUniqueOpenEmisId(userCtrl);
    };

    userCtrl.generatePassword = function () {
        return directorySvc.setPassword(userCtrl);
    };


    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function (evt, data) {
        //return;
        // The last complete step is now transfer staff, add transfer staff logic function call here
        userCtrl.postTransferForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function (evt, data) {
        userCtrl.addStaffButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            userCtrl.Staff.identity_type_name = userCtrl.defaultIdentityTypeName;
            userCtrl.Staff.identity_type_id = userCtrl.defaultIdentityTypeId;
            delete userCtrl.postResponse;
            userCtrl.reloadInternalDatasource(true);
            userCtrl.createNewStaff = false;
            userCtrl.externalSearch = false;
            userCtrl.step = 'internal_search';
        }
        // Step 2 - External search
        else if (data.step == 2) {
            userCtrl.Staff.identity_type_name = userCtrl.externalIdentityType;
            userCtrl.Staff.identity_type_id = userCtrl.defaultIdentityTypeId;
            delete userCtrl.postResponse;
            userCtrl.reloadExternalDatasource(true);
            userCtrl.createNewStaff = false;
            userCtrl.externalSearch = true;
            userCtrl.step = 'external_search';
        }
        // Step 3 - Create user
        else if (data.step == 3) {
            userCtrl.externalSearch = false;
            userCtrl.createNewStaff = true;
            userCtrl.step = 'create_user';
            userCtrl.getUniqueOpenEmisId();
            userCtrl.generatePassword();
            userSvc.resetExternalVariable();
        }
        // Step 4 - Add Staff
        else if (data.step == 4) {
            if (userCtrl.externalSearch) {
                userCtrl.getUniqueOpenEmisId();
            }
            // Work around for alert reset
            userCtrl.createNewInternalDatasource(userCtrl.internalGridOptions, true);
            userCtrl.step = 'add_staff';
        }
        // Step 5 - Transfer Staff
        else if (data.step == 5) {
            userCtrl.step = 'transfer_staff';
        }
    });

    function transferStaffNextStep() {
        userCtrl.step = 'transfer_staff';
    }

    //POCOR-8071
    async function checkUserAge() {
        const userData = userCtrl.selectedUserData;
        const result1 = await userSvc.checkUserAge({

            'date_of_birth': userData.date_of_birth
        });
        if (result1.data.status_code == "400") {
            userCtrl.isMaximizeAge = true;
            userCtrl.ageMessage = result1.data.message;
            userCtrl.validateDetails();
        } else {
            userCtrl.isMaximizeAge = false;
            userCtrl.ageMessage = result1.data.message;
            userCtrl.validateDetails();
        }
    }

    //POCOR-8071

    async function checkUserAlreadyExistByIdentity() {

        const userData = userCtrl.selectedUserData;
        const result = await directorySvc.checkUserExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
        });
        if (result.data.user_exist === 1) {
            userCtrl.messageClass = 'alert-warning';
            userCtrl.message = result.data.message;
            userCtrl.isIdentityUserExist = true;
        } else {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
        }
        return result.data.user_exist === 1;
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
            openemis_no,
            nationality_id,
            identity_type_name
        } = userCtrl.selectedUserData;
        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth)
        const isGeneralInfoAgedHasError = (date_of_birth)
        const isIdentityHasError = identity_number?.length > 1 && (nationality_id === undefined || nationality_id === "" || nationality_id === null || identity_type_id === undefined || identity_type_id === null || identity_type_id === "")
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        let isSkipableForIdentity = identity_number?.length > 1 &&
            nationality_id > 0 &&
            identity_type_id > 0;

        if (identity_type_name == 'UNHCR') {
            isSkipableForIdentity = false;
        }
        if (identity_type_name == 'Seychelles Civil Status') { // POCOR-9481
            isSkipableForIdentity = false;
        }
        if (isIdentityHasError && !isOpenEmisNoHasError) {
            return ['Identity', true];
        }
        if (isSkipableForIdentity) {
            return ['Identity', false];
        }
        if (isOpenEmisNoHasError && !isIdentityHasError) {
            return ["OpenEMIS_ID", false];
        }
        if (isGeneralInfodHasError) {
            return ["General_Info", true];
        }
        if (isGeneralInfoAgedHasError) {
            return ["General_Info_Age", true]; //POCOR-8071
        }

        return ["", false];
    }


    userCtrl.isNextButtonShouldDisable = function isNextButtonShouldDisable() {
        return directorySvc.isNextButtonShouldDisable(userCtrl);
    }

    function getCSPDSearchData() {
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
                        userCtrl.isSearchResultEmpty = gridData.length === 0;
                        var totalRowCount = gridData.length === 0 ? 1 : gridData.length;
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


    async function checkUserExistByIdentityFromConfiguration() {
        // console.log('checkUserExistByIdentityFromConfiguration');
        //POCOR-7481-HINDOL

        const userData = userCtrl.selectedUserData;
        const {identity_type_id, identity_number} = userData;
        if (!identity_type_id) {
            userCtrl.error.identity_type_id =
                "This field cannot be left empty";
            return false;
        }
        if (!identity_number) {
            userCtrl.error.identity_number =
                "This field cannot be left empty";
            return false;
        }

        const result = await directorySvc.checkUserExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
        });
        // StudentController.error.nationality_id = "";
        userCtrl.unsetError('identity_type_id');
        userCtrl.unsetError('identity_number');

        if (result.data.user_exist === 1) {
            userCtrl.messageClass = 'alert-warning';
            userCtrl.message = result.data.message;
            userCtrl.isIdentityUserExist = true;
            // userCtrl.error.identity_number = result.data.message;
            $window.scrollTo({bottom: 0});
        } else {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
            userCtrl.unsetError('identity_number')
        }
        return result.data.user_exist === 1;
    }

}
