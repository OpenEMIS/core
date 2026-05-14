angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.students.svc', 'kd-angular-tree-dropdown', 'kd.data.svc', 'directory.directoryadd.svc'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStudentsSvc', '$rootScope', 'KdDataSvc',  'DirectoryaddSvc'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStudentsSvc, $rootScope, KdDataSvc,  DirectoryaddSvc) {
    // ag-grid vars

    const userCtrl = $scope;
    var scope = $scope;
    userCtrl.selectedUserData = {};
    userCtrl.notSaved = true;
    userCtrl.isConfirming = false;
    userCtrl.selectedStudentData = userCtrl.selectedUserData;
    const userData = userCtrl.selectedUserData;
    const userSvc = InstitutionsStudentsSvc;
    const directorySvc = DirectoryaddSvc;
    // for file upload
    scope.startWithOneLeftButton = false;
    scope.selectedButton = 'import';
    scope.startWithTwoLeftButton = false;
    scope.wrapperClass = '';
    //
    userCtrl.pageSize = 10;
    userCtrl.step = 'user_details';
    userCtrl.addNewStudentConfig = {};
    userCtrl.internalGridOptions = null;
    userCtrl.externalGridOptions = null;
    userCtrl.postRespone = null;
    userCtrl.translateFields = null;
    //contacts/nationalities/identities req/no
    userCtrl.contactSkipped = true; // POCOR-9101
    userCtrl.contactsRequired = ''; // POCOR-9101
    userCtrl.emailSkipped = false; // POCOR-9101
    userCtrl.emailRequired = ''; // POCOR-9101
    userCtrl.mobileSkipped = false; // POCOR-9101
    userCtrl.mobileRequired = ''; // POCOR-9101
    userCtrl.identitySkipped = true; // POCOR-7882
    userCtrl.identitiesRequired = ''; // POCOR-7882
    userCtrl.nationalitySkipped = true; // POCOR-7882
    userCtrl.nationalitiesRequired = ''; // POCOR-7882
    userCtrl.nationalityClass = 'input select';
    userCtrl.identityTypeClass = 'input select';
    userCtrl.identityClass = 'input string';
    //upper alert
    userCtrl.messageClass = '';
    userCtrl.message = '';
    //common user options
    userCtrl.genderOptions = [];
    userCtrl.nationalitiesOptions = [];
    userCtrl.identityTypeOptions = [];
    userCtrl.contactTypeOptions = [];
    //student options
    userCtrl.academicPeriodOptions = [];
    userCtrl.educationGradeOptions = [];
    userCtrl.classOptions = [];
    userCtrl.selectedGuardianData = {};
    userCtrl.isGuardianAdding = false;
    userCtrl.guardianStep = 'user_details';
    userCtrl.redirectToGuardian = false;
    userCtrl.multipleInstitutionsStudentEnrollment = true;
    userCtrl.transferReasonsOptions = [];
    userCtrl.isSameSchool = false;
    userCtrl.isDiffSchool = false;
    userCtrl.currentYear = new Date().getFullYear();
    userCtrl.currentAcademicPeriod = $window.localStorage.getItem("currentAcademicPeriod");//POCOR-7733
    userCtrl.currentAcademicPeriodName = $window.localStorage.getItem("currentAcademicPeriodName");//POCOR-7733
    userCtrl.studentStatus = 'Pending Transfer';
    userCtrl.studentAdmissionStatus = " "; //POCOR-7716
    userCtrl.studentAdmissionStatusValue = " "; //POCOR-7716
    //common
    userCtrl.error = {};
    userCtrl.institutionId = null;
    userCtrl.customFields = [];
    userCtrl.customFieldsArray = [];
    userCtrl.selectedSection = '';
    userCtrl.isInternalSearchSelected = false;
    userCtrl.isExternalSearchSelected = false;
    userCtrl.canSkipNationality = false;
    userCtrl.canSkipIdentity = false;
    userCtrl.userData = {};
    userCtrl.isExternalSearchEnable = false;
    userCtrl.externalSearchSourceName = '';
    userCtrl.datepickerOptions = {
        showWeeks: false
    };
    userCtrl.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    userCtrl.disableFields = {
        username: false,
        password: false
    }
    userCtrl.isSearchResultEmpty = true;
    userCtrl.MaxFileSize = 0;
    userCtrl.isIdentityUserExist = false;


    //controller function
    userCtrl.goToFirstStep = goToFirstStep;
    userCtrl.goToNextStep = goToNextStep;
    userCtrl.goToPrevStep = goToPrevStep;
    userCtrl.gotoAddStudentStep = gotoAddStudentStep;
    userCtrl.confirmUser = confirmUser;
    userCtrl.getStudentAdmissionStatus = getStudentAdmissionStatus;//POCOR-7716
    userCtrl.initGrid = initGrid;
    userCtrl.changeAcademicPeriod = changeAcademicPeriod;
    userCtrl.changeEducationGrade = changeEducationGrade;
    userCtrl.changeClass = changeClass;
    userCtrl.changeStartDate = changeStartDate;
    userCtrl.cancelProcess = cancelProcess;
    // userCtrl.getAcademicPeriods = getAcademicPeriods;
    userCtrl.getEducationGrades = getEducationGrades;
    userCtrl.getClasses = getClasses;
    userCtrl.getInternalSearchData = getInternalSearchData;
    userCtrl.getExternalSearchData = getExternalSearchData;
    userCtrl.processGridUserRecord = processGridUserRecord;
    userCtrl.addGuardian = addGuardian;
    userCtrl.goToInternalSearch = goToInternalSearch;
    userCtrl.goToExternalSearch = goToExternalSearch;
    userCtrl.getRedirectToGuardian = getRedirectToGuardian;
    userCtrl.getRelationType = getRelationType;
    userCtrl.validateDetails = validateDetails;
    userCtrl.validateAdditionalDetails = validateAdditionalDetails;
    userCtrl.saveUserDetails = saveUserDetails;
    userCtrl.getStudentCustomFields = getStudentCustomFields;
    userCtrl.changeOption = changeOption;
    userCtrl.changed = changed;
    userCtrl.selectOption = selectOption;
    userCtrl.onDecimalNumberChange = onDecimalNumberChange;
    userCtrl.setUserData = setUserData;
    userCtrl.changeTransferReason = changeTransferReason;
    userCtrl.transferStudent = transferStudent;
    userCtrl.setUserDataFromExternalSearchData = setUserDataFromExternalSearchData;
    userCtrl.transferStudentNextStep = transferStudentNextStep;
    userCtrl.checkConfigForExternalSearch = checkConfigForExternalSearch;
    userCtrl.isNextButtonShouldDisable = isNextButtonShouldDisable;
    userCtrl.getCSPDSearchData = getCSPDSearchData;
    userCtrl.checkUserExistByIdentityFromConfiguration = checkUserExistByIdentityFromConfiguration;
    userCtrl.studentExistInTheSameSchool = studentExistInTheSameSchool;
    userCtrl.nextStepFromStudentExistInTheSameSchool = nextStepFromStudentExistInTheSameSchool;
    userCtrl.studentExistInTheOtherSchool = studentExistInTheOtherSchool;
    userCtrl.nextStepFromStudentExistInTheOtherSchool = nextStepFromStudentExistInTheOtherSchool;
    userCtrl.studentExistInUnfinishedWithdraw = studentExistInUnfinishedWithdraw;
    userCtrl.nextStepFromStudentExistInUnfinishedWithdraw = nextStepFromStudentExistInUnfinishedWithdraw;
    userCtrl.studentExistInUnfinishedTransfer = studentExistInUnfinishedTransfer;
    userCtrl.nextStepFromStudentExistInUnfinishedTransfer = nextStepFromStudentExistInUnfinishedTransfer;
    userCtrl.handleFileSelection = handleFileSelection;

    //POCOR-7224-HINDOL[END]

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

        function getMultipleInstitutionsStudentEnrollment() {
            return userSvc.getConfigItemValue('multiple_institutions_student_enrollment')
                .then(configValue => {
                    userCtrl.multipleInstitutionsStudentEnrollment = (configValue === "1");
                })
                .catch(error => {
                    console.error('Error fetching MultipleInstitutionsStudentEnrollment configuration:', error);
                });
        }

        function getMaxFileSizeConfig() {
            return userSvc.getConfigItemValue('dashboard_img_size_limit')
                .then(configValue => {
                    userCtrl.maxFileSize = (configValue || 0);
                })
                .catch(error => {
                    console.error('Error fetching MaxFileSize configuration:', error);
                });
        }

        function getAcademicPeriods() {
            return userSvc.getAcademicPeriods()
                .then(resp => {
                    userCtrl.academicPeriodOptions = resp.data;
                    // console.log(userCtrl.academicPeriodOptions);
                    // Iterate over the array to find the current academic period
                    for (const period of resp.data) {
                        if (period.current === 1) {
                            userCtrl.currentAcademicPeriod = period.id;
                            userCtrl.currentAcademicPeriodName = period.name;
                            const academicPeriod = period.id; //POCOR-8411 -- Start
                            userSvc.getStartDateFromAcademicPeriod({academic_period_id: academicPeriod}).then((response) => {
                                    const startDateRangeResponse = response;
                                    const {start_date, end_date} = startDateRangeResponse.data[0];
                                userSvc.formatDate(start_date).then(function(formattedDate) {
                                    userCtrl.currentAcademicPeriodStartDate = formattedDate;
                                });
                                userSvc.formatDate(end_date).then(function(formattedDate) {
                                    userCtrl.currentAcademicPeriodEndDate = formattedDate;
                                });
                                }
                            );
                            break; // Exit the loop once the current period is found
                        }
                    }
                });
        }

        function handleConfigItem(configCode, configValue) {
            switch (configCode) {
                case "student_email":
                    userCtrl.emailSkipped = configValue === 2;
                    userCtrl.emailRequired = configValue === 1 ? 'required' : '';
                    break;
                case "student_mobile":
                    userCtrl.mobileSkipped = configValue === 2;
                    userCtrl.mobileRequired = configValue === 1 ? 'required' : '';
                    break;
                case "StudentIdentities":
                    userCtrl.identitySkipped = configValue === 2;
                    userCtrl.identitiesRequired = configValue === 1 ? 'required' : '';
                    break;
                case "StudentNationalities":
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

        function getAddNewStudentConfig() {
            const configCodes = [
                "student_email",
                "student_mobile",
                "StudentIdentities",
                "StudentNationalities"];

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
                .then(getAddNewStudentConfig)
                .then(getMultipleInstitutionsStudentEnrollment)
                .then(getMaxFileSizeConfig)
                .then(getAcademicPeriods)
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


    scope.uploadFile = function (field, e) {

        var fileInput = e.target;

        if (fileInput && fileInput.files && fileInput.files[0]) {
            const maxFileGiven = userCtrl.maxFileSize;
            // console.log(maxFileGiven);
            var maxFileSizeInt = parseInt(maxFileGiven);
            if (!isNaN(maxFileSizeInt)) {
                // console.log(maxFileSizeInt);
                var selectedFile = fileInput.files[0];
                // console.log(selectedFile.size);
                if (selectedFile.size > maxFileSizeInt) {
                    field.errorMessage = 'File Size Is Too Big';
                } else {
                    field.errorMessage = '';
                    let fileReader = new FileReader();
                    fileReader.readAsDataURL(selectedFile);
                    fileReader.onload = () => {
                        field.file = fileReader.result;
                    }
                    field.answer = selectedFile.name;
                    field.file_name = selectedFile.name;
                    field.file_size = selectedFile.size;
                    field.file = fileInput.selectedFile;
                }
            } else {
                console.error('MaxFileSize is not a valid integer.');
                maxFileSizeInt = 0;
            }
            // Access file properties
            // console.log('File name:', selectedFile.name);
            // console.log('File type:', selectedFile.type);
            // console.log('File size:', selectedFile.size);

            // You can now handle the file as needed, for example, store its information in your model
        }
        $scope.$apply();
    };


    function handleFileSelection(field) {
        // console.log(field);
    }

    scope.removeFile = function (field) {
        field.answer = null;
    };

    userCtrl.getUniqueOpenEmisId = function () {
        return directorySvc.setUniqueOpenEmisId(userCtrl);
    };

    userCtrl.generatePassword = function () {
        return directorySvc.setPassword(userCtrl);
    };


    function getInternalSearchData() {
        const {
            first_name,
            last_name,
            date_of_birth,
            identity_number,
            openemis_no,
            nationality_id,
            nationality_name,
            identity_type_name,
            identity_type_id,
        } = userCtrl.selectedUserData;

        // Modify values based on conditions
        let paramFirstName = first_name;
        let paramLastName = last_name;
        let paramDateOfBirth = date_of_birth;
        let paramIdentityNumber = identity_number;
        let paramOpenEmisNo = openemis_no;
        let paramNationalityId = nationality_id;
        let paramNationalityName = nationality_name;
        let paramIdentityTypeName = identity_type_name;
        let paramIdentityTypeId = identity_type_id;

        if (openemis_no) {
            paramFirstName = paramLastName = paramDateOfBirth = paramIdentityNumber = null;
            paramNationalityId = paramNationalityName = paramIdentityTypeName = paramIdentityTypeId = null;

            userCtrl.selectedUserData.first_name = null;
            userCtrl.selectedUserData.last_name = null;
            userCtrl.selectedUserData.date_of_birth = null;
            userCtrl.selectedUserData.identity_number = null;
            userCtrl.selectedUserData.nationality_id = null;
            userCtrl.selectedUserData.nationality_name = null;
            userCtrl.selectedUserData.identity_type_name = null;
            userCtrl.selectedUserData.identity_type_id = null;
        } else if (identity_number && identity_type_id && nationality_id) {
            paramFirstName = paramLastName = paramDateOfBirth = null;

            userCtrl.selectedUserData.first_name = null;
            userCtrl.selectedUserData.last_name = null;
            userCtrl.selectedUserData.date_of_birth = null;
        }

        let param = {
            first_name: paramFirstName,
            last_name: paramLastName,
            date_of_birth: paramDateOfBirth,
            identity_number: paramIdentityNumber,
            openemis_no: paramOpenEmisNo,
            institution_id: userCtrl.institutionId,
            user_type_id: 1,
            nationality_id: paramNationalityId,
            nationality_name: paramNationalityName,
            identity_type_name: paramIdentityTypeName,
            identity_type_id: paramIdentityTypeId,
        };

        const dataSource = {
            pageSize: userCtrl.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param = {
                    ...param,
                    limit: params.endRow - params.startRow,
                    page: params.endRow / (params.endRow - params.startRow),
                };

                userSvc.getInternalSearchData(param)
                    .then(function (response) {
                        // console.log(param)
                        // console.log(response)
                        const gridData = response.data.data || [];
                        userCtrl.isSearchResultEmpty = gridData.length === 0;
                        const totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        userCtrl.processGridUserRecord(gridData, params, totalRowCount);
                        UtilsSvc.isAppendLoader(false);
                    })
                    .catch(function (error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };

        userCtrl.internalGridOptions.api.setDatasource(dataSource);
        userCtrl.internalGridOptions.api.sizeColumnsToFit();
    }


    function getExternalSearchData() {
        const {selectedUserData, externalSearchSourceName, pageSize, externalGridOptions} = userCtrl;

        let param = {
            first_name: selectedUserData.first_name,
            last_name: selectedUserData.last_name,
            date_of_birth: selectedUserData.date_of_birth,
            identity_number: selectedUserData.identity_number,
            openemis_no: selectedUserData.openemis_no,
            nationality_id: selectedUserData.nationality_id,
            search_type: externalSearchSourceName
        };

        const dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);

                param = {
                    ...param,
                    limit: params.endRow - params.startRow,
                    page: params.endRow / (params.endRow - params.startRow),
                };

                userSvc.getExternalSearchData(param)
                    .then(function (response) {
                        let gridData = response.data.data || [];
                        if (externalSearchSourceName === 'UNHCR') {
                            userCtrl.selectedUserData.identity_number = null;
                        }

                        gridData.forEach((data, idx) => {
                            if (externalSearchSourceName === 'UNHCR') {
                                Object.assign(data, {
                                    name: selectedUserData.name,
                                    gender: selectedUserData.gender.name,
                                    gender_id: selectedUserData.gender_id,
                                    nationality_id: selectedUserData.nationality_id,
                                    nationality: selectedUserData.nationality_name,
                                    identity_type: selectedUserData.identity_type_name,
                                    identity_type_id: selectedUserData.identity_type_id,
                                    first_name: selectedUserData.first_name,
                                    last_name: selectedUserData.last_name,
                                    middle_name: selectedUserData.middle_name,
                                    third_name: selectedUserData.third_name,
                                    preferred_name: selectedUserData.preferred_name,
                                    date_of_birth: selectedUserData.date_of_birth,
                                });
                            } else if (externalSearchSourceName === 'Seychelles Civil Status') { // POCOR-9481
                                Object.assign(data, {
                                    gender_id: data['gender_id'],
                                    gender: data['gender'],
                                    first_name: data['first_name'],
                                    last_name: data['last_name'],
                                    name: data['full_name'],
                                    date_of_birth: data['date_of_birth'],
                                    nationality_id: data['nationality_id'],
                                    nationality: data['nationality'],
                                    identity_type: selectedUserData.identity_type_name,
                                    identity_type_id: scope.selectedUserData.identity_type_id
                                });
                            } else {
                                Object.assign(data, {
                                    gender_id: data['gender.id'],
                                    gender: data['gender.name'],
                                    nationality_id: data['main_nationality.id'],
                                    nationality: data['main_nationality.name'],
                                    identity_type: data['main_identity_type.name'],
                                    identity_type_id: data['main_identity_type.id'],
                                });
                            }
                            data.id = idx;
                        });

                        userCtrl.isSearchResultEmpty = gridData.length === 0;
                        const totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        userCtrl.processGridUserRecord(gridData, params, totalRowCount);
                        UtilsSvc.isAppendLoader(false);
                    })
                    .catch(function (error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };

        externalGridOptions.api.setDatasource(dataSource);
        externalGridOptions.api.sizeColumnsToFit();
    }

    function processGridUserRecord(userRecords, params, totalRowCount) {
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


    //POCOR-7716 start
     function getStudentAdmissionStatus() {
        return userSvc.getStudentAdmissionStatus()
            .then(resp => {
                const admissionStatus = resp.data[0];
                userCtrl.studentAdmissionStatus = admissionStatus.name;
                userCtrl.studentAdmissionStatusValue = admissionStatus.id;
            })
            .catch(error => {
                console.error(error);
            });
    }

    //POCOR-7716 end
    function getEducationGrades() {
        if (!userCtrl.selectedUserData.academic_period_id) {
            userCtrl.selectedUserData.academic_period_id = userCtrl.userData.current_enrol_academic_period_id;
        }
        UtilsSvc.isAppendLoader(true);

        userCtrl.selectedUserData.education_grade_id = null;

        const param = {
            academic_periods: userCtrl.selectedUserData.academic_period_id,
            institution_id: userCtrl.institutionId
        };

        userSvc.getEducationGrades(param)
            .then(resp => {
                // console.log(resp.data);
                userCtrl.educationGradeOptions = resp.data !== 'null' ? resp.data : [];
            })
            .catch(error => {
                console.error(error);
            })
            .finally(() => {
                UtilsSvc.isAppendLoader(false);
            });
    }

    function getClasses() {
        if (!userCtrl.selectedUserData.education_grade_id) return;

        const params = {
            academic_period: userCtrl.selectedUserData.academic_period_id,
            institution_id: userCtrl.institutionId,
            grade_id: userCtrl.selectedUserData.education_grade_id
        };

        UtilsSvc.isAppendLoader(true);

        userSvc.getClasses(params)
            .then(resp => {
                userCtrl.classOptions = resp.data !== 'null' ? resp.data : [];
            })
            .catch(error => {
                console.error(error);
            })
            .finally(() => {
                UtilsSvc.isAppendLoader(false);
            });
    }

    $window.savePhoto = function (event) {
        const photo = event.files[0];
        userCtrl.selectedUserData.photo = photo;
        userCtrl.selectedUserData.photo_name = photo.name;

        const fileReader = new FileReader();
        fileReader.readAsDataURL(photo);

        fileReader.onload = () => {
            const base64String = fileReader.result.split(',')[1];

            // POCOR-8917 Manually trigger AngularJS digest cycle
            $scope.$apply(() => {
                userCtrl.selectedUserData.photo_base_64 = base64String;
            });
        };

    };
    function getStudentCustomFields() {
        const studentId = userCtrl.userData?.id || null;

        directorySvc.getStudentCustomFields(studentId)
            .then(resp => {
                // console.log(resp)
                userCtrl.customFields = resp.data;
                userCtrl.customFieldsArray = [];
                userCtrl.createCustomFieldsArray();
            })
            .catch(error => {
                console.error(error);
                UtilsSvc.isAppendLoader(false);
            });
    }

    function changeOption(field, optionId) {
        field.option.forEach((option) => {
            if (option.option_id === optionId) {
                field.selectedOption = option.option_name;
            }
        })
    }

    function changed(answer) {
        console.log(answer);
    }

    function selectOption(field) {
        field.answer = [];
        field.option.forEach((option) => {
            if (option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    //POCOR-7993 start
    function onDecimalNumberChange(field) {
        if (field) {
            // Check if params is not null/undefined
            if (field.params) {
                if (field.params.precision) {
                    let timer;
                    if (timer) {
                        clearTimeout(timer);
                    }
                    timer = setTimeout(() => {
                        field.answer = parseFloat(field.answer.toFixed(field.params.precision));
                    }, 3000);
                }
            }
        }
    }

    userCtrl.appendName = (dataObj, variableName) => {
        if (dataObj.hasOwnProperty(variableName)) {
            const value = dataObj[variableName]?.trim() || '';
            if (value) {
                dataObj.name += ` ${value}`;
            }
        }
    };

    userCtrl.setName = function() {
        const unsetFields = ['first_name', 'middle_name', 'third_name', 'last_name'];
        unsetFields.forEach(field => userCtrl.unsetError(field));

        const userData = userCtrl.selectedUserData;
        userData.name = userData.first_name?.trim() || '';

        ['middle_name', 'third_name', 'last_name'].forEach(field =>  userCtrl.appendName(userData, field));

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
    //POCOR-8411 -- Start
    userCtrl.changeTransferStartDate = function() {
        userCtrl.unsetError('transferStartDate');
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


    async function changeAcademicPeriod() {
        const {selectedUserData, academicPeriodOptions} = userCtrl;
        const academicPeriod = selectedUserData.academic_period_id;

        const selectedPeriod = academicPeriodOptions.find(option => option.id === academicPeriod);

        if (selectedPeriod) {
            selectedUserData.academic_period_name = selectedPeriod.name;
        }

        userCtrl.unsetError('academic_period_id');

        try {
            const startDateRangeResponse = await userSvc.getStartDateFromAcademicPeriod({academic_period_id: academicPeriod});
            const {start_date, end_date} = startDateRangeResponse.data[0];
            userCtrl.getEducationGrades();

            const startDatePicker2 = angular.element(document.getElementById('Student_start_date'));
            userSvc.formatDate(start_date).then(function(formattedDate) {
                startDatePicker2.datepicker("setStartDate", formattedDate);
            });
            userSvc.formatDate(end_date).then(function(formattedDate) {
                startDatePicker2.datepicker("setEndDate", formattedDate);
                selectedUserData.endDate = formattedDate;
            });

        } catch (error) {
            console.error(error);
        }
    }

    function changeClass() {
        const {selectedUserData, classOptions, institutionId, error} = userCtrl;
        const classId = selectedUserData.class_id;

        const selectedClass = classOptions.find(option => option.id === classId);

        if (selectedClass) {
            selectedUserData.education_grade_name = selectedClass.name;
        }

        userCtrl.unsetError('class_id');

        if (classId) {
            const param = {
                academic_periods: selectedUserData.academic_period_id,
                institution_id: institutionId,
                education_grade_id: selectedUserData.education_grade_id,
                class_id: classId
            };

            userSvc.getClassCapacity(param).then(resp => {
                if (resp.data !== 'null') {
                    const capacityStatus = resp.data.capacity_status;
                    if (capacityStatus === 'Exceeded Capacity') {
                        error.class_id = 'Class capacity is full';
                        selectedUserData.class_id = '';
                    }
                }
            }).catch(error => {
                console.error(error);
            });
        }
    }


    async function changeStartDate() {
        userCtrl.unsetError('startDate');
    }
    async function changeEducationGrade() {
        const {selectedUserData, educationGradeOptions, error} = userCtrl;
        const {education_grade_id, academic_period_id, date_of_birth} = selectedUserData;

        const selectedGrade = educationGradeOptions.find(option => option.education_grade_id === education_grade_id);

        if (selectedGrade) {
            selectedUserData.education_grade_name = selectedGrade.name;
        }
        userCtrl.unsetError('education_grade_id');

        userCtrl.getClasses();

        var formattedDateOfBirth = date_of_birth;
        userSvc.formatDate(date_of_birth).then(function(formattedDate) {
            formattedDateOfBirth = formattedDate;
        });

        if (education_grade_id !== undefined && formattedDateOfBirth !== undefined && academic_period_id !== undefined) {
            const params = {
                date_of_birth: date_of_birth,
                education_grade_id: education_grade_id,
                academic_period_id: academic_period_id
            };

            try {
                const dateOfBirthValidationResponse = await userSvc.getDateOfBirthValidation(params);
                const {validation_error, min_age, max_age, student_age} = dateOfBirthValidationResponse.data[0];

                if (validation_error === 1) {
                    error.date_of_birth = `The student is ${student_age} years old in the given Academic Period. The student should be between ${min_age} to ${max_age} years old`;
                } else {
                    userCtrl.unsetError('date_of_birth');
                }
            } catch (error) {
                console.error(error);
            }
        }
    }

    function changeTransferReason() {
        const {selectedUserData, transferReasonsOptions, error} = userCtrl;
        const transferReasonId = selectedUserData.transfer_reason_id;

        selectedUserData.transferReason = {};

        const selectedReason = transferReasonsOptions.find(option => option.id === transferReasonId);

        if (selectedReason) {
            selectedUserData.transferReason.name = selectedReason.name;
        }
        userCtrl.unsetError('transfer_reason_id');

    }

    function getGridOptions(localeText, columnDefs, onRowSelected) {
        return {
            columnDefs: columnDefs,
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
            suppressContextMenu: true,
            stopEditingWhenGridLosesFocus: true,
            ensureDomOrder: true,
            pagination: true,
            paginationPageSize: 10,
            maxBlocksInCache: 1,
            cacheBlockSize: 10,
            onRowSelected: onRowSelected,
            onGridSizeChanged: function () {
                this.api.sizeColumnsToFit();
            },
        };
    }

    function goToInternalSearch() {
        userCtrl.selectedUserData.userType = 'Student';
        userCtrl.selectedUserData.user_type_id = 1;
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
            // StudentController.goToInternalSearch();
        } else if (userCtrl.isExternalSearchSelected) {
            userCtrl.step = 'external_search';
            userCtrl.externalGridOptions = null;
            userCtrl.goToExternalSearch();
        } else {
            switch (userCtrl.step) {
                case 'internal_search': {
                    if (userCtrl.selectedUserData.date_of_birth) {
                        userSvc.formatDate(userCtrl.selectedUserData.date_of_birth).then(function(formattedDate) {
                            userCtrl.selectedUserData.date_of_birth = formattedDate;
                        });
                    }
                    // userCtrl.selectedUserData.date_of_birth = userSvc.formatDate(userCtrl.selectedUserData.date_of_birth);
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
                case 'add_student':
                    userCtrl.processNewUser();
                    break;
            }
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
                return userCtrl.getStudentAdmissionStatus();
            })
            .then(() => {
                userCtrl.selectedUserData.userType = {};
                userCtrl.selectedUserData.userType.name = 'Students';
                return userCtrl.getStudentCustomFields();
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


    function studentExistInTheSameSchool() {
        return (userCtrl.isInternalSearchSelected
            && userCtrl.userData
            && userCtrl.userData.is_same_school)
    }

    function nextStepFromStudentExistInTheSameSchool() {
        userCtrl.step = 'summary';
        userCtrl.messageClass = 'alert-warning';
        userCtrl.message = 'This student is already allocated to the current institution';
        userCtrl.getRedirectToGuardian();
        userCtrl.isInternalSearchSelected = false;
    }

    function studentExistInUnfinishedWithdraw() {
        return (userCtrl.isInternalSearchSelected
            && userCtrl.userData
            && userCtrl.userData.is_pending_withdraw)
    }

    function nextStepFromStudentExistInUnfinishedWithdraw() {
        userCtrl.step = 'summary';
        userCtrl.messageClass = 'alert-warning';
        userCtrl.message = `This student has an unfinished withdraw from
        ${userCtrl.userData.pending_withdraw_institution_code}
        - ${userCtrl.userData.pending_withdraw_institution_name}.
        Please connect responsible person to finish this operation`;
        // StudentController.getRedirectToGuardian();
        userCtrl.isInternalSearchSelected = false;
    }

    function studentExistInUnfinishedTransfer() {
        return (userCtrl.isInternalSearchSelected
            && userCtrl.userData
            && userCtrl.userData.is_pending_transfer);
    }

    function nextStepFromStudentExistInUnfinishedTransfer() {
        userCtrl.step = 'summary';
        userCtrl.messageClass = 'alert-warning';
        userCtrl.message = `This student has unfinished tranfer from
        ${userCtrl.userData.pending_transfer_prev_institution_code}
        - ${userCtrl.userData.pending_transfer_prev_institution_name}
        to ${userCtrl.userData.pending_transfer_institution_code}
        - ${userCtrl.userData.pending_transfer_institution_name}.
        Please connect responsible person to finish this operation`;
        // StudentController.getRedirectToGuardian();
        userCtrl.isInternalSearchSelected = false;
    }

    function studentExistInTheOtherSchool() {
        return (userCtrl.isInternalSearchSelected
            && userCtrl.userData
            && userCtrl.userData.is_diff_school
        )
    }

    function nextStepFromStudentExistInTheOtherSchool() {
        userCtrl.step = 'summary';
        userCtrl.messageClass = 'alert-warning';
        userCtrl.message = `This student is already allocated
        to ${userCtrl.userData.current_enrol_institution_code}
        - ${userCtrl.userData.current_enrol_institution_name}`;
        userCtrl.getStudentTransferReason();
        userCtrl.isInternalSearchSelected = false;
    }


    function gotoAddStudentStep() {
        userCtrl.step = 'add_student';
        userCtrl.selectedUserData.endDate = userCtrl.currentAcademicPeriodEndDate;

    }

    async function goToNextStep() {
        userCtrl.messageClass = '';
        userCtrl.message = ``;
        if (userCtrl.step === 'confirmation') {
            const studentExistByIdentityFromConfiguration =
                await userCtrl.checkUserExistByIdentityFromConfiguration();
        }
        if (userCtrl.studentExistInUnfinishedWithdraw()) {
            // console.log('studentExistInUnfinishedWithdraw');
            userCtrl.nextStepFromStudentExistInUnfinishedWithdraw();
            // console.log('studentExistInUnfinishedWithdraw');
            return;
        }

        if (userCtrl.studentExistInUnfinishedTransfer()) {
            // console.log('studentExistInUnfinishedTransfer');
            userCtrl.nextStepFromStudentExistInUnfinishedTransfer();
            // console.log('nextStepFromStudentExistInUnfinishedTransfer');
            return;
        }

        if (userCtrl.studentExistInTheSameSchool()) {
            userCtrl.nextStepFromStudentExistInTheSameSchool();
            return;
        }

        const single_institutions_student_enrollment =
            !(userCtrl.multipleInstitutionsStudentEnrollment);
        if (single_institutions_student_enrollment) {
            if (userCtrl.studentExistInTheOtherSchool()) {
                userCtrl.nextStepFromStudentExistInTheOtherSchool();
                return;
            }
        }

        // POCOR-7871
        if (userCtrl.isInternalSearchSelected && userCtrl.step !== 'confirmation') {
            userCtrl.processNewUser();
            // StudentController.isInternalSearchSelected = false; // POCOR-7871
            return;
        }

        if (userCtrl.isExternalSearchSelected) {
            switch (userCtrl.step) {
                case "external_search":
                    userCtrl.processNewUser();
                    break;
                case "confirmation":
                    userCtrl.gotoAddStudentStep();
                    break;
            }
            return;
        }

        switch (userCtrl.step) {
            case 'user_details':
                scope.internalGridOptions = null;
                userCtrl.validateDetails();
                break;
            case 'internal_search': {
                if (userCtrl.isExternalSearchEnable) {
                    userCtrl.step = 'external_search';
                    userCtrl.externalGridOptions = null;
                    userCtrl.goToExternalSearch();
                    return;
                }
                userCtrl.processNewUser(); // this step adds OpenemisID // POCOR-8231-C4
                return;
            }
                break;
            case 'external_search':
                userCtrl.processNewUser(); // this step adds OpenemisID // POCOR-8231-C4
                break;
            case 'confirmation':
                userCtrl.validateAdditionalDetails();
                break;
        }

    }


    //POCOR-6172-HINDOL[END]
    //POCOR-7224-HINDOL[END]

    async function validateDetails() {
        userCtrl.unsetAllErrors();
        const [blockName, hasError] = checkUserDetailValidationBlocksHasError();
        const {selectedStudentData: selectedUserData, error} = userCtrl;

        // Reset errors


        // Validate Identity Block
        if (blockName === 'Identity' && hasError) {
            if (!selectedUserData.nationality_id) {
                error.nationality_id = 'This field cannot be left empty';
            }
            if (!selectedUserData.identity_type_id) {
                error.identity_type_id = 'This field cannot be left empty';
            }
            if (!selectedUserData.identity_number) {
                error.identity_number = 'This field has an error';
            }
        }

        // Validate General Info Block
        if (blockName === 'General_Info' && hasError) {
            if (!selectedUserData.first_name) {
                error.first_name = 'This field cannot be left empty';
            }
            if (!selectedUserData.last_name) {
                error.last_name = 'This field cannot be left empty';
            }
            if (!selectedUserData.gender_id) {
                error.gender_id = 'This field cannot be left empty';
            }
            if (!selectedUserData.date_of_birth) {
                error.date_of_birth = 'This field cannot be left empty';
            } else {
                selectedUserData.date_of_birth = $filter('date')(selectedUserData.date_of_birth, 'yyyy-MM-dd');
            }
        }

        if (hasError) {
            return;
        }

        userCtrl.step = 'internal_search';
        userCtrl.internalGridOptions = null;
        userCtrl.goToInternalSearch();

        await checkUserAlreadyExistByIdentity(); // POCOR-8231 returned
    }

    async function validateAdditionalDetails() {
        userCtrl.unsetAllErrors();
        let hasError = false;
        const userData = userCtrl.selectedUserData;
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
            !userData.nationality_id) {
            userCtrl.error.nationality_id = 'This field cannot be left empty';
            console.error('StudentController.error.nationality_id');
            hasError = true;
        }
        if (!userCtrl.identitySkipped &&
            userCtrl.identitiesRequired === 'required' &&
            !userData.identity_type_id) {
            userCtrl.error.identity_type_id = 'This field cannot be left empty';
            console.error('StudentController.error.identity_type_id');
            hasError = true;
        }
        if (!userCtrl.identitySkipped &&
            userCtrl.identitiesRequired === 'required' &&
            !userData.identity_number) {
            userCtrl.error.identity_number = 'This field cannot be left empty';
            console.error('StudentController.error.identity_number');
            hasError = true;
        }
        if (!userCtrl.mobileSkipped &&
            userCtrl.mobileRequired === 'required' &&
            !userData.mobile_number) {
            userCtrl.error.mobile_number = 'This field cannot be left empty';
            console.error('StudentController.error.contact_type_id');
            hasError = true;
        }
        if (!userCtrl.emailSkipped &&
            userCtrl.emailRequired === 'required' &&
            !userData.email) {
            userCtrl.error.email = 'This field cannot be left empty';
            console.error('StudentController.error.contact_value');
            hasError = true;
        }

        if (hasError) {
            return;
        }
        userCtrl.gotoAddStudentStep();
    }

    function confirmUser() {
        let isCustomFieldNotValidated = false;
        //POCOR-7871
        if (!userCtrl.isInternalSearchSelected) {
            if (!userCtrl.selectedUserData.username) {
                userCtrl.error.username = 'This field cannot be left empty';
            }
            if (!userCtrl.selectedUserData.password) {
                userCtrl.error.password = 'This field cannot be left empty';
            }
        }
        if (!userCtrl.selectedUserData.academic_period_id) {
            userCtrl.error.academic_period_id = 'This field cannot be left empty';
        }
        if (!userCtrl.selectedUserData.education_grade_id) {
            userCtrl.error.education_grade_id = 'This field cannot be left empty';
        }
        var res = userSvc.getEducationGradeAddStudent(userCtrl.selectedUserData.education_grade_id,
            userCtrl.selectedUserData.first_name,
            userCtrl.selectedUserData.last_name,
            userCtrl.selectedUserData.openemis_no); //POCOR-7386
        var res1 = $window.localStorage.getItem('repeater_validation');
        timer = setTimeout(() => {
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"yes"') {
                userCtrl.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
                $window.localStorage.removeItem('repeater_validation');
                return;
            }
        }, 3000);
        if (!userCtrl.selectedUserData.startDate) {
            userCtrl.error.startDate = 'This field cannot be left empty';
        }

        // Validation logic for custom fields POCOR-8179
        userCtrl.customFieldsArray.forEach((customField) => {
            customField.data.forEach((field) => {
                field.errorMessage = '';
                if (field.is_mandatory === 1) {
                    if (!field.answer && ['TEXT', 'TEXTAREA', 'NOTE', 'DROPDOWN', 'NUMBER', 'DECIMAL', 'DATE', 'TIME', 'file'].includes(field.field_type)) {
                        userCtrl.error[field.name] = 'This field is required.'
                        field.errorMessage = 'Custom field is required.';
                        isCustomFieldNotValidated = true;
                    } else if (field.field_type === 'CHECKBOX' && field.answer.length === 0) {
                        userCtrl.error[field.name] = 'This field is required.'
                        field.errorMessage = 'Custom field is required.';
                        isCustomFieldNotValidated = true;
                    }
                }
            });
        });

        // Return if any custom field is not validated
        if (isCustomFieldNotValidated) {
            return;
        }

        // other validations and save logic
        if (!userCtrl.isInternalSearchSelected) {
            if (!userCtrl.selectedUserData.username ||
                !userCtrl.selectedUserData.password ||
                !userCtrl.selectedUserData.academic_period_id ||
                !userCtrl.selectedUserData.startDate) {
                return;
            }
        } else {
            if (!userCtrl.selectedUserData.academic_period_id ||
                !userCtrl.selectedUserData.startDate) {
                return;
            }
        }

        timer = setTimeout(() => {
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"no"') {
                userCtrl.saveUserDetails('confirmUser');
                $window.localStorage.removeItem('repeater_validation');
            }
        }, 3000);
    }

    function saveUserDetails(caller) {
        if (userCtrl.isConfirming) {
            console.info('Confirmation already in progress');
            return;
        }
        userCtrl.isConfirming = true;
        if (userCtrl.multipleInstitutionsStudentEnrollment) {
            if (typeof userCtrl.userData != "undefined") {
                if (typeof userCtrl.userData.is_diff_school != "undefined") {
                    userCtrl.userData.is_diff_school = 0;
                }
            }
            if (typeof userCtrl.selectedUserData != "undefined") {
                if (typeof userCtrl.selectedUserData.is_diff_school != "undefined") {
                    userCtrl.selectedUserData.is_diff_school = 0;
                }
            }
        }
        let startDate = userCtrl.userData
        && userCtrl.userData.is_diff_school > 0 ? $filter('date')(userCtrl.selectedUserData.transferStartDate, 'yyyy-MM-dd') : $filter('date')(userCtrl.selectedUserData.startDate, 'yyyy-MM-dd');
        const addressAreaRef = userSvc.getAddressArea();
        addressAreaRef && (userCtrl.selectedUserData.addressArea = addressAreaRef);
        const birthplaceAreaRef = userSvc.getBirthplaceArea();
        birthplaceAreaRef && (userCtrl.selectedUserData.birthplaceArea = birthplaceAreaRef)
        // console.log(userCtrl.userData);
        let previousInstitutionId = userCtrl.userData && userCtrl.userData.current_enrol_institution_id ? userCtrl.userData.current_enrol_institution_id : null;
        var params = {
            called: caller,
            currentAcademicPeriod: userCtrl.currentAcademicPeriod,//POCOR-7733
            currentAcademicPeriodName: userCtrl.currentAcademicPeriodName,//POCOR-7733
            institution_id: userCtrl.institutionId,
            openemis_no: userCtrl.selectedUserData.openemis_no,
            first_name: userCtrl.selectedUserData.first_name,
            middle_name: userCtrl.selectedUserData.middle_name,
            third_name: userCtrl.selectedUserData.third_name,
            last_name: userCtrl.selectedUserData.last_name,
            preferred_name: userCtrl.selectedUserData.preferred_name,
            gender_id: userCtrl.selectedUserData.gender_id,
            date_of_birth: userCtrl.selectedUserData.date_of_birth,
            username: userCtrl.selectedUserData.username,
            password: userCtrl.selectedUserData.password,
            postal_code: userCtrl.selectedUserData.postalCode,
            address: userCtrl.selectedUserData.address,
            birthplace_area_id: userSvc.getBirthplaceAreaId() === null ? userCtrl.selectedUserData.birthplace_area_id : userSvc.getBirthplaceAreaId(),
            address_area_id: userSvc.getAddressAreaId() === null ? userCtrl.selectedUserData.address_area_id : userSvc.getAddressAreaId(),
            identity_type_id: userCtrl.selectedUserData.identity_type_id,
            identity_type_name: userCtrl.selectedUserData.identity_type_name,
            identity_number: userCtrl.selectedUserData.identity_number,
            nationality_id: userCtrl.selectedUserData.nationality_id,
            nationality_name: userCtrl.selectedUserData.nationality_name,
            contact_type: userCtrl.selectedUserData.contact_type_id,
            contact_value: userCtrl.selectedUserData.contact_value,
            email: userCtrl.selectedUserData.email,
            mobile_number: userCtrl.selectedUserData.mobile_number,
            education_grade_id: userCtrl.selectedUserData.education_grade_id,
            academic_period_id: userCtrl.selectedUserData.academic_period_id,
            start_date: startDate,
            end_date: userCtrl.selectedUserData.endDate,
            institution_class_id: userCtrl.selectedUserData.class_id,
            student_status_id: 1,
            student_admission_status: userCtrl.studentAdmissionStatus,//POCOR-7716
            student_admission_status_value: userCtrl.studentAdmissionStatusValue,//POCOR-7716
            photo_base_64: userCtrl.selectedUserData.photo_base_64,
            photo_name: userCtrl.selectedUserData.photo_name,
            sync_status: userCtrl.isExternalSearchSelected ? 1 : 0, //POCOR-9590: external search → Synced, manual add → Local
            is_diff_school: userCtrl.userData && userCtrl.userData.is_diff_school ? userCtrl.userData.is_diff_school : 0,
            student_id: userCtrl.userData && userCtrl.userData.id ? userCtrl.userData.id : null,
            previous_institution_id: previousInstitutionId,
            previous_academic_period_id: userCtrl.userData && userCtrl.userData.current_enrol_academic_period_id ? userCtrl.userData.current_enrol_academic_period_id : null,
            previous_education_grade_id: userCtrl.userData && userCtrl.userData.current_enrol_education_grade_id ? userCtrl.userData.current_enrol_education_grade_id : null,
            student_transfer_reason_id: userCtrl.selectedUserData.transfer_reason_id ? userCtrl.selectedUserData.transfer_reason_id : null,
            comment: userCtrl.selectedUserData.transferComment,
            custom: [],
        };
        userCtrl.customFieldsArray.forEach((customField) => {
                customField.data.forEach((field) => {
                        if (field.field_type !== 'CHECKBOX') {
                            let fieldData = {
                                student_custom_field_id: field.student_custom_field_id,
                                text_value: null,
                                unique: field.is_unique,
                                mandatory: field.is_mandatory,
                                number_value: null,
                                decimal_value: null,
                                textarea_value: null,
                                time_value: null,
                                date_value: null,
                                file: null,
                                institution_id: userCtrl.institutionId,
                            };
                            if (field.field_type === 'TEXT' || field.field_type === 'NOTE') {
                                if (field.answer) {
                                    fieldData.text_value = field.answer;
                                }
                            }
                            if (field.field_type === 'TEXTAREA') {
                                if (field.answer) {
                                    fieldData.textarea_value = field.answer;
                                }
                            }
                            if (field.field_type === 'NUMBER') {
                                if (field.answer) {
                                    fieldData.number_value = field.answer;
                                }
                            }
                            if (field.field_type === 'DECIMAL') {
                                if (field.answer) {
                                    fieldData.decimal_value = String(field.answer);
                                }
                            }
                            if (field.field_type === 'DROPDOWN') {
                                if (field.answer) {
                                    fieldData.number_value = Number(field.answer);
                                }
                            }
                            if (field.field_type === 'TIME') {
                                if (field.answer) {

                                    let time = field.answer.toLocaleTimeString();
                                    let timeArray = time.split(':');
                                    fieldData.time_value = `${timeArray[0]}:${timeArray[1]}`;
                                }
                            }
                            if (field.field_type === 'DATE') {
                                if (field.answer) {
                                    fieldData.date_value = normalizeCustomDateForSave(field.answer); //POCOR-9664 - fix date format for custom field
                                }
                            }
                            if (field.field_type === 'FILE') {
                                if (field.answer) {
                                    fieldData.file = field.file;
                                    fieldData.text_value = field.answer;
                                }
                            }
                            params.custom.push(fieldData);
                        } else {
                            if (field.answer) {
                                field.answer.forEach((id) => {
                                    let fieldData = {
                                        student_custom_field_id: field.student_custom_field_id,
                                        text_value: null,
                                        unique: field.is_unique,
                                        mandatory: field.is_mandatory,
                                        number_value: Number(id),
                                        decimal_value: null,
                                        textarea_value: null,
                                        time_value: null,
                                        date_value: null,
                                        file: null,
                                        institution_id: userCtrl.institutionId,
                                    };
                                    params.custom.push(fieldData);
                                });
                            }
                        }
                    }
                )
                ;
            }
        )
        ;
        //POCOR-7733 start
        if (params.is_diff_school > 0) {
            if (params.currentAcademicPeriod != params.previous_academic_period_id) {
                if (params.student_status_id == 1) {
                    userCtrl.message = `This student is allocated to ${userCtrl.userData.current_enrol_institution_code}
                                               - ${userCtrl.userData.current_enrol_institution_name} in a different
                                                 Academic Period. Transfer can only happen for students in current
                                                 Academic Period.`;
                    userCtrl.messageClass = "alert-warning";
                    UtilsSvc.isAppendLoader(false);
                    return;
                }
            }
        }

        //POCOR-7733 end
        UtilsSvc.isAppendLoader(true);

        // console.log(params)
        userSvc.saveStudentDetails(params).then(function (resp) {
            if (resp) {
                //POCOR-6172-HINDOL[START]
                userCtrl.notSaved = false;
                if (userCtrl.userData &&
                    //POCOR-6172-HINDOL[END]
                    userCtrl.userData.is_diff_school < 1
                ) {
                    if (resp.data.saved_student.institution_student === undefined) {
                        if (resp.data.saved_student.error !== undefined) {
                            userCtrl.message = resp.data.saved_student.error;
                        } else {
                            userCtrl.message = 'Student is not added. Check for errors.';
                        }
                        userCtrl.messageClass = 'alert-danger';
                        UtilsSvc.isAppendLoader(false);
                        userCtrl.isConfirming = false;
                        return;
                    }
                }
                if (userCtrl.userData &&
                    //POCOR-6172-HINDOL[END]
                    userCtrl.userData.is_diff_school > 0
                ) {
                    userCtrl.message = 'Student transfer request is added successfully.';
                    userCtrl.messageClass = 'alert-success';
                    UtilsSvc.isAppendLoader(false);
                    userCtrl.isConfirming = false;
                    $window.history.back();
                } else {
                    userCtrl.message = 'Student is added successfully.';
                    userCtrl.messageClass = 'alert-success';
                    UtilsSvc.isAppendLoader(false);
                    userCtrl.step = "summary"; // POCOR-8559
                    var todayDate = new Date();
                    userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
                    userCtrl.getRedirectToGuardian();
                    userCtrl.isConfirming = false;
                }
            }
        }, function (error) {
            console.error(error);
            userCtrl.message =  error.data.message || error.statusText || error.toString();
            userCtrl.messageClass = 'alert-danger';
            UtilsSvc.isAppendLoader(false);
            userCtrl.isConfirming = false;
        });
    }

    function transferStudent() {
        if (!userCtrl.selectedUserData.education_grade_id) {
            userCtrl.error.education_grade_id = 'This field cannot be left empty';
        }
        // console.log(StudentController.selectedStudentData);
        var res = userSvc.getEducationGrade(userCtrl.selectedUserData.education_grade_id, userCtrl.selectedUserData.openemis_no);
        // $validation = JSON.parse(res.data);

        let shouldSaveData = false;

        // timer = setTimeout(() => {
        var res1 = $window.localStorage.getItem('repeater_validation');
        timer = setTimeout(() => {
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"yes"') {
                shouldSaveData = true;
                userCtrl.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
                $window.localStorage.removeItem('repeater_validation');
                return;
            }
        }, 3000);

        //   if (res1 == '"yes"') {
        //     StudentController.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
        //     shouldSaveData = false;
        //   }
        // }, 3000);

        if (!userCtrl.selectedUserData.transferStartDate) {
            userCtrl.error.transferStartDate = 'This field cannot be left empty';
        } else {
            userCtrl.selectedUserData.transferStartDate = $filter('date')(userCtrl.selectedUserData.transferStartDate, 'yyyy-MM-dd');
        }
        if (!userCtrl.selectedUserData.transfer_reason_id) {
            userCtrl.error.transfer_reason_id = 'This field cannot be left empty';
        }

        if (!userCtrl.selectedUserData.education_grade_id || !userCtrl.selectedUserData.transferStartDate || !userCtrl.selectedUserData.transfer_reason_id) {
            return;
        }

        timer = setTimeout(() => {
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"no"') {
                userCtrl.saveUserDetails('transferStudent');
                $window.localStorage.removeItem('repeater_validation')
            }
        }, 3000);
    }

    //POCOR-9664 - Function to normalize custom field date input for saving.
    function normalizeCustomDateForSave(value) {
        if (!value) {
            return null;
        }

        if (value instanceof Date) {
            return isNaN(value.getTime()) ? null : $filter('date')(value, 'yyyy-MM-dd');
        }

        if (typeof value === 'string') {
            var raw = value.trim();
            if (!raw || raw.toLowerCase() === 'null') {
                return null;
            }
            if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(raw)) {
                return raw;
            }
            if (/^\d{1,2}-\d{1,2}-\d{4}$/.test(raw)) {
                var parts = raw.split('-');
                return parts[2] + '-' + ('0' + parts[1]).slice(-2) + '-' + ('0' + parts[0]).slice(-2);
            }
            var parsed = new Date(raw);
            return isNaN(parsed.getTime()) ? null : $filter('date')(parsed, 'yyyy-MM-dd');
        }

        return null;
    }

    function goToFirstStep() {
        if (!userCtrl.isGuardianAdding) {
            userCtrl.step = 'user_details';
            userCtrl.selectedStudentData = {};
            userCtrl.selectedUserData = {};
        } else {
            userCtrl.guardianStep = 'user_details';
            userCtrl.selectedGuardianData = {};
            userCtrl.selectedUserData = {};
        }
    }

    function cancelProcess() {
        $window.history.back();
    }

    function addGuardian() {
        if ($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        // console.log("addGuardian");
        // console.log(StudentController);
        let params = {
            student_id: userCtrl.selectedUserID,
            user_id: userCtrl.selectedUserData.student_id,
            openemis_no: userCtrl.selectedUserData.openemis_no
        };
        var queryString = KdDataSvc.urlsafeB64Encode(JSON.stringify(params));
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian?queryString=' + queryString;
    }

    function getRedirectToGuardian() {
        UtilsSvc.isAppendLoader(true);
        userSvc.getRedirectToGuardian().then(function (resp) {
            userCtrl.redirectToGuardian = resp.data[0].redirecttoguardian_status;
            UtilsSvc.isAppendLoader(false);
        }, function (error) {
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getRelationType() {
        UtilsSvc.isAppendLoader(true);
        userSvc.getRelationType().then(function (resp) {
            userCtrl.relationTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function (error) {
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    userCtrl.selectUserFromInternalSearch = function (id) {
        userCtrl.selectedUserID = id;
        userCtrl.isInternalSearchSelected = true;
        userCtrl.isExternalSearchSelected = false;
        userCtrl.getStudentData();

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

    userCtrl.selectStudentFromExternalSearch = function (id) {
        userCtrl.selectedUserID = id;
        userCtrl.isInternalSearchSelected = false;
        userCtrl.isExternalSearchSelected = true;
        userCtrl.getStudentData();
        userCtrl.disableFields = {
            username: false,
            password: false
        }
    }

    userCtrl.selectUserFromExternalSearch = function (id) {
        userCtrl.selectedUserID = id;
        userCtrl.isInternalSearchSelected = false;
        userCtrl.isExternalSearchSelected = true;
        userCtrl.getStudentData();
        userCtrl.disableFields = {
            username: false,
            password: false
        }
    }

    userCtrl.getStudentData = function () {
        var log = [];
        angular.forEach(userCtrl.rowsThisPage, function (value) {
            if (value.id == userCtrl.selectedUserID) {
                userCtrl.userData = value;
                if (userCtrl.isInternalSearchSelected) {
                    userCtrl.userData.currentlyAllocatedTo = value.current_enrol_institution_code + ' - ' + value.current_enrol_institution_name;

                }
                userCtrl.setUserData(value);
            }
        }, log);
    }

    function setUserData(selectedData) {
        // console.log(selectedData);
        //POCOR-7889: start
        if (selectedData.current_enrol_academic_period_id !== undefined) {
            const academicPeriod = selectedData.current_enrol_academic_period_id;
            userSvc.getStartDateFromAcademicPeriod({academic_period_id: academicPeriod}).then((response) => {
                    const startDateRangeResponse = response;
                    const {start_date, end_date} = startDateRangeResponse.data[0];
                    userSvc.formatDate(start_date).then(function (formattedDate) {
                        userCtrl.selectedUserData.startDate = formattedDate;
                    });
                    userSvc.formatDate(end_date).then(function (formattedDate) {
                        userCtrl.selectedUserData.endDate = formattedDate;
                    });
                }
            );
        } else {
            userCtrl.selectedUserData.endDate = userCtrl.currentAcademicPeriodEndDate; //default beahaviour
        }
        //POCOR-7889: end
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
        // console.log(selectedData);
        userCtrl.selectedUserData.user_id = selectedData.id;
        userCtrl.selectedUserData.openemis_no = selectedData.openemis_no;
        userCtrl.selectedUserData.name = selectedData.name;//POCOR-7172
        userCtrl.selectedUserData.first_name = selectedData.first_name;
        userCtrl.selectedUserData.middle_name = selectedData.middle_name;
        userCtrl.selectedUserData.third_name = selectedData.third_name;
        userCtrl.selectedUserData.last_name = selectedData.last_name;
        userCtrl.selectedUserData.preferred_name = selectedData.preferred_name;
        userCtrl.selectedUserData.gender_id = selectedData.gender_id;
        userCtrl.selectedUserData.photo_name = selectedData.photo_name; // POCOR-8917
        userCtrl.selectedUserData.photo_base_64 = selectedData.photo_content; // POCOR-8917
        userCtrl.selectedUserData.gender = {
            name: selectedData.gender
        };
        userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
        userCtrl.selectedUserData.email = selectedData.email;
        userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
        userCtrl.selectedUserData.contact_type_id = selectedData.contact_type_id; // POCOR-8012-n
        userCtrl.selectedUserData.contact_value = selectedData.contact_value; // POCOR-8012-n
        userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
        userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
        if (selectedData.identity_number) {
            userCtrl.canSkipIdentity = true;
        }
        if (selectedData.nationality) {
            userCtrl.canSkipNationality = true;
        }
        userCtrl.selectedUserData.identity_number = selectedData.identity_number;
        userCtrl.selectedUserData.nationality_name = selectedData.nationality;
        userCtrl.selectedUserData.nationality_id = selectedData.nationality_id;

        // console.log(selectedData.nationality);
        userCtrl.selectedUserData.address = selectedData.address;
        userCtrl.selectedUserData.postalCode = selectedData.postal_code;
        userCtrl.selectedUserData.addressArea.name = selectedData.area_name;
        userCtrl.selectedUserData.birthplaceArea.name = selectedData.birth_area_name;
        userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        var todayDate = new Date();
        userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
        userCtrl.isSameSchool = selectedData.is_same_school > 0 ? true : false;
        userCtrl.isDiffSchool = selectedData.is_diff_school ? true : false;
        if (userCtrl.multipleInstitutionsStudentEnrollment) {
            userCtrl.isDiffSchool = false;
        }
        if (selectedData.is_pending_withdraw) {
            userCtrl.isDiffSchool = false;
        }
        if (selectedData.is_pending_transfer) {
            userCtrl.isDiffSchool = false;
        }
        userCtrl.selectedUserData.currentlyAllocatedTo = selectedData.current_enrol_institution_code + ' - ' + selectedData.current_enrol_institution_name;

        userCtrl.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id === undefined ? null : selectedData.birthplace_area_id;
        userCtrl.selectedUserData.address_area_id = selectedData.address_area_id === undefined ? null : selectedData.address_area_id;
        userCtrl.selectedUserData.birth_area_code = selectedData.birth_area_code === undefined ? '' : selectedData.birth_area_code;
        userCtrl.selectedUserData.area_code = selectedData.area_code === undefined ? '' : selectedData.area_code;

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

    function setUserDataFromExternalSearchData(selectedData) {
        if (userCtrl.externalSearchSourceName === 'Jordan CSPD') {
            userSvc.getUniqueOpenEmisId().then((response) => {
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
                userCtrl.selectedUserData.gender_id = selectedData.gender_id;
                userCtrl.selectedUserData.gender = {
                    name: selectedData.gender
                };
                userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
                userCtrl.selectedUserData.email = selectedData.email;
                userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
                userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
                userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
                userCtrl.selectedUserData.identity_number = selectedData.identity_number;
                userCtrl.selectedUserData.nationality_name = selectedData.nationality;
                userCtrl.selectedUserData.address = selectedData.address;
                userCtrl.selectedUserData.postalCode = selectedData.postal_code;
                userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                userCtrl.selectedUserData.endDate = userCtrl.currentAcademicPeriodEndDate;
                var todayDate = new Date();
                userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');

                userCtrl.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
                userCtrl.selectedUserData.address_area_id = selectedData.address_area_id;
                userCtrl.selectedUserData.birth_area_code = selectedData.birth_area_code;
                userCtrl.selectedUserData.area_code = selectedData.area_code;
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
                userCtrl.disableFields = {
                    username: false,
                    password: false,
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
            userCtrl.selectedUserData.openemis_no = selectedData.openemis_no;
            userCtrl.selectedUserData.first_name = selectedData.first_name;
            userCtrl.selectedUserData.middle_name = selectedData.middle_name;
            userCtrl.selectedUserData.third_name = selectedData.third_name;
            userCtrl.selectedUserData.last_name = selectedData.last_name;
            userCtrl.selectedUserData.preferred_name = selectedData.preferred_name;
            userCtrl.selectedUserData.gender_id = selectedData.gender_id;
            userCtrl.selectedUserData.gender = {
                name: selectedData.gender
            };
            userCtrl.selectedUserData.date_of_birth = selectedData.date_of_birth;
            userCtrl.selectedUserData.email = selectedData.email;
            userCtrl.selectedUserData.mobile_number = selectedData.mobile_number;
            userCtrl.selectedUserData.identity_type_name = selectedData.identity_type;
            userCtrl.selectedUserData.identity_type_id = selectedData.identity_type_id;
            userCtrl.selectedUserData.identity_number = selectedData.identity_number;
            userCtrl.selectedUserData.nationality_name = selectedData.nationality;
            userCtrl.selectedUserData.address = selectedData.address;
            userCtrl.selectedUserData.postalCode = selectedData.postal_code;
            userCtrl.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
            userCtrl.selectedUserData.endDate = userCtrl.currentAcademicPeriodEndDate;
            var todayDate = new Date();
            userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');

            userCtrl.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
            userCtrl.selectedUserData.address_area_id = selectedData.address_area_id;
            userCtrl.selectedUserData.birth_area_code = selectedData.birth_area_code;
            userCtrl.selectedUserData.area_code = selectedData.area_code;
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
            userCtrl.disableFields = {
                username: false,
                password: false,
            }
        }
    }

    userCtrl.getStudentTransferReason = function () {
        UtilsSvc.isAppendLoader(true);
        userSvc.getStudentTransferReason().then(function (resp) {
            userCtrl.transferReasonsOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
            userCtrl.getEducationGrades();
        }, function (error) {
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
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
                        userCtrl.selectUserFromInternalSearch(_e.node.data.id);
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
                        userCtrl.selectStudentFromExternalSearch(_e.node.data.id);
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
                        userCtrl.selectUserFromInternalSearch(_e.node.data.id);
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
                        userCtrl.selectStudentFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function () {
                        this.api.sizeColumnsToFit();
                    },
                };
            });
    };


    function transferStudentNextStep() {
        userCtrl.step = 'transfer_student';
        // POCOR-7889
        var startDatePicker = angular.element(document.getElementById('Student_transfer_start_date'));
        var start_date = userCtrl.selectedUserData.startDate;
        startDatePicker.datepicker("setStartDate", start_date);

    }

    async function checkUserAlreadyExistByIdentity() {

        const userData = userCtrl.selectedUserData;
        const result = await userSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id': userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id,
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
        const isIdentityHasError = (identity_number?.length > 1 || nationality_id || identity_type_id) &&
            (!identity_number || !nationality_id || !identity_type_id);
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        let isSkipableForIdentity = identity_number?.length > 1 && nationality_id > 0 && identity_type_id > 0;

        if (identity_type_name == 'UNHCR') {
            isSkipableForIdentity = false;
        }
        // POCOR-8231 If there is OpenEMIS others errors are ignored
        if (isOpenEmisNoHasError) {
            return ["OpenEMIS_ID", false];
        }

        /**
         * New For POCOR-7351
         */
        if (isIdentityHasError) {
            return ['Identity', true];
        }
        // POCOR-8231 End

        if (isSkipableForIdentity) {
            return ['Identity', false];
        }

        if (isGeneralInfodHasError) {
            return ["General_Info", true];
        }

        return ["", false];
    }

    function checkConfigForExternalSearch() {
        const {identity_type_id, nationality_id} = userCtrl.selectedUserData;
        // console.log({ nationality_id, identity_type_id });

        userCtrl.isExternalSearchEnable = false;

        userSvc.checkConfigForExternalSearch(nationality_id, identity_type_id)
            .then((resp) => {
                userCtrl.isExternalSearchEnable = resp.showExternalSearch;
                userCtrl.externalSearchSourceName = resp.value;
                // console.log({
                //     isExternalSearchEnable: userCtrl.isExternalSearchEnable,
                //     externalSearchSourceName: userCtrl.externalSearchSourceName
                // });
                UtilsSvc.isAppendLoader(false);
            })
            .catch((error) => {
                userCtrl.isExternalSearchEnable = false;
                console.error(error);
                UtilsSvc.isAppendLoader(false);
            });
    }


    // function isNextButtonShouldDisable() {
    //     const {
    //         step,
    //         selectedUserData: {
    //             first_name,
    //             last_name,
    //             date_of_birth,
    //             gender_id,
    //             identity_number,
    //             openemis_no,
    //             user_id
    //         },
    //         isIdentityUserExist,
    //         externalSearchSourceName,
    //         isExternalSearchEnable
    //     } = userCtrl;
    //
    //     const checkVars = {
    //         step,
    //         first_name,
    //         last_name,
    //         date_of_birth,
    //         gender_id,
    //         identity_number,
    //         openemis_no,
    //         user_id,
    //         isIdentityUserExist,
    //         externalSearchSourceName,
    //         isExternalSearchEnable
    //     };
    //
    //     const isInternalSearch = checkVars.step === "internal_search";
    //     const isExternalSearch = checkVars.step === "external_search";
    //
    //     // console.log(checkVars);
    //
    //     if (checkVars.isIdentityUserExist && isInternalSearch) return true;
    //     if (checkVars.openemis_no && !checkVars.user_id && isInternalSearch) return true;
    //     if (checkVars.identity_number && !checkVars.user_id && !checkVars.isExternalSearchEnable && isInternalSearch) return true;
    //     if (isInternalSearch && !checkVars.isExternalSearchEnable && !(checkVars.first_name && checkVars.last_name && checkVars.date_of_birth && checkVars.gender_id)) return true;
    //     if (isExternalSearch && checkVars.externalSearchSourceName === 'UNHCR' && !checkVars.identity_number) return true;
    //     if (isExternalSearch && !(checkVars.first_name && checkVars.last_name && checkVars.date_of_birth && checkVars.gender_id)) return true;
    //
    //     return false;
    // }

    function isNextButtonShouldDisable () {
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
                userSvc.getCspdData(param)
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

        const result = await userSvc.checkUserAlreadyExistByIdentity({
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
            userCtrl.error.identity_number = result.data.message;
            $window.scrollTo({bottom: 0});
        } else {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
            userCtrl.unsetError('identity_number');
        }
        return false;
    }

    userCtrl.createCustomFieldsArray = function() {
        directorySvc.createCustomFieldsArray(userCtrl);
    }
    function getFilteredSections(customFields) {
        return Array.from(new Set(customFields.map(item => item.section)));
    }

    function filterBySection(customFields, section) {
        return customFields.filter(item => item.section === section);
    }

    function initializeFieldData(fieldData) {
        fieldData.answer = '';
        fieldData.errorMessage = '';

        switch (fieldData.field_type) {
            case 'TEXT':
            case 'TEXTAREA':
            case 'NOTE':
            case 'FILE':
                fieldData.answer = fieldData.values || '';
                break;
            case 'DROPDOWN':
                initializeDropdownField(fieldData);
                break;
            case 'DATE':
                initializeDateField(fieldData);
                break;
            case 'TIME':
                initializeTimeField(fieldData);
                break;
            case 'CHECKBOX':
                initializeCheckboxField(fieldData);
                break;
            case 'DECIMAL':
            case 'NUMBER':
                initializeNumberField(fieldData);
                break;
        }
    }

    function initializeDropdownField(fieldData) {
        // console.log(fieldData);
        fieldData.selectedOptionId = '';

        try {
            fieldData.answer = fieldData.values?.[0]?.dropdown_val ?? null;
        } catch (e) {
            console.error(e, fieldData);
            fieldData.answer = null;
        }

        fieldData.selectedOption = null;
        fieldData.option.forEach(option => {
            if (option.option_id === fieldData.answer) {
                fieldData.selectedOption = option.option_name;
            }
        });
    }

    // POCOR-9664 - Initialize date field with datepicker options and parse existing value
    function initializeDateField(fieldData) {
        fieldData.isDatepickerOpen = false;
        fieldData.params = parseParams(fieldData.params);
        fieldData.datePickerOptions = {showWeeks: false};
        if (fieldData.values && fieldData.values !== 'null') {
            const parsedDate = new Date(fieldData.values);
            fieldData.answer = isNaN(parsedDate.getTime()) ? null : parsedDate;
        } else {
            fieldData.answer = null;
        }
    }

    function initializeTimeField(fieldData) {
        fieldData.hourStep = 1;
        fieldData.minuteStep = 5;
        fieldData.isMeridian = true;
        fieldData.params = parseParams(fieldData.params);

        const startTime = parseTime(fieldData.params?.start_time);
        const endTime = parseTime(fieldData.params?.end_time);

        if (fieldData.values) {
            const [hours, minutes] = fieldData.values.split(':').map(Number);
            fieldData.answer = new Date(new Date().setHours(hours, minutes));
        } else {
            fieldData.answer = new Date();
        }
    }

    function initializeCheckboxField(fieldData) {
        fieldData.answer = [];
        fieldData.option.forEach(option => option.selected = false);
        if (fieldData.values?.length) {
            fieldData.values.forEach(value => {
                fieldData.answer.push(value.checkbox_val.toString());
                fieldData.option.forEach(option => {
                    if (option.option_id === value.checkbox_val.toString()) {
                        option.selected = true;
                    }
                });
            });
        }
    }

    function initializeNumberField(fieldData) {
        fieldData.params = parseParams(fieldData.params);
        fieldData.answer = Number(fieldData.values);
    }

    function parseParams(params) {
        return params ? JSON.parse(params) : null;
    }

    function parseTime(time) {
        if (!time) return null;
        const [timePart, meridian] = time.split(' ');
        let [hours, minutes] = timePart.split(':').map(Number);
        if (hours === 12) {
            hours = meridian === 'PM' ? hours : 0;
        } else {
            hours = meridian === 'AM' ? hours : hours + 12;
        }
        return {hours, minutes};
    }

    userCtrl.checkDateOfBirth = function() {
        directorySvc.checkDateOfBirth($scope);
    };

    userCtrl.checkIdentityNumber = function() {
        const {nationality_id, identity_type_id, identity_number} = userCtrl.selectedUserData;
        if (identity_number) {
            userCtrl.unsetError('identity_number');
        }
        if (nationality_id && identity_type_id && identity_number) {
            userCtrl.unsetAllErrors();
        }
    };

    userCtrl.setError = function(field, message) {
        userCtrl.error[field] = message;
    };

    userCtrl.unsetError = function(field) {

        delete userCtrl.error[field];
    };

    userCtrl.unsetAllErrors = function() {
        userCtrl.error = {};
    };

}
