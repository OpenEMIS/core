angular
    .module('institutions.students.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.students.svc', 'kd-angular-tree-dropdown'])
    .controller('InstitutionsStudentsCtrl', InstitutionStudentController);

InstitutionStudentController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStudentsSvc', '$rootScope'];

function InstitutionStudentController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStudentsSvc, $rootScope) {
    // ag-grid vars


    var StudentController = this;
    var test = $scope;

    StudentController.pageSize = 10;
    StudentController.step = 'user_details';
    StudentController.selectedStudentData = {};
    StudentController.internalGridOptions = null;
    StudentController.externalGridOptions = null;
    StudentController.postRespone = null;
    StudentController.translateFields = null;
    StudentController.nationality_class = 'input select error';
    StudentController.identity_type_class = 'input select error';
    StudentController.identity_class = 'input string';
    StudentController.messageClass = '';
    StudentController.message = '';
    StudentController.genderOptions = [];
    StudentController.nationalitiesOptions = [];
    StudentController.identityTypeOptions = [];
    StudentController.academicPeriodOptions = [];
    StudentController.educationGradeOptions = [];
    StudentController.classOptions = [];
    StudentController.selectedGuardianData = {};
    StudentController.isGuardianAdding = false;
    StudentController.guardianStep = 'user_details';
    StudentController.redirectToGuardian = false;
    StudentController.error = {};
    StudentController.institutionId = null;
    StudentController.customFields = [];
    StudentController.customFieldsArray = [];
    StudentController.selectedSection = '';
    StudentController.isInternalSearchSelected = false;
    StudentController.isExternalSearchSelected = false;
    StudentController.transferReasonsOptions = [];
    StudentController.isSameSchool = false;
    StudentController.isDiffSchool = false;
    StudentController.currentYear = new Date().getFullYear();
    StudentController.currentAcademicPeriod = $window.localStorage.getItem("currentAcademicPeriod");//POCOR-7733
    StudentController.currentAcademicPeriodName = $window.localStorage.getItem("currentAcademicPeriodName");//POCOR-7733
    StudentController.studentStatus = 'Pending Transfer';
    StudentController.StudentData = {};
    StudentController.isExternalSearchEnable = false;
    StudentController.externalSearchSourceName='';
    StudentController.datepickerOptions = {
        showWeeks: false
    };
    StudentController.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    StudentController.disableFields = {
        username: false,
        password:false
    }
    StudentController.isSearchResultEmpty = false;
    //controller function
    StudentController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StudentController.generatePassword = generatePassword;
    StudentController.changeGender = changeGender;
    StudentController.changeNationality = changeNationality;
    StudentController.changeIdentityType = changeIdentityType;
    StudentController.goToFirstStep = goToFirstStep;
    StudentController.goToNextStep = goToNextStep;
    StudentController.goToPrevStep = goToPrevStep;
    StudentController.confirmUser = confirmUser;
    StudentController.getGenders = getGenders;
    StudentController.getNationalities = getNationalities;
    StudentController.getIdentityTypes = getIdentityTypes;
    StudentController.setStudentName = setStudentName;
    StudentController.appendName = appendName;
    StudentController.initGrid = initGrid;
    StudentController.changeAcademicPeriod = changeAcademicPeriod;
    StudentController.changeEducationGrade = changeEducationGrade;
    StudentController.changeClass = changeClass;
    StudentController.cancelProcess = cancelProcess;
    StudentController.getAcademicPeriods = getAcademicPeriods;
    StudentController.getEducationGrades = getEducationGrades;
    StudentController.getClasses = getClasses;
    StudentController.getInternalSearchData = getInternalSearchData;
    StudentController.processInternalGridUserRecord = processInternalGridUserRecord;
    StudentController.getExternalSearchData = getExternalSearchData;
    StudentController.processExternalGridUserRecord = processExternalGridUserRecord;
    StudentController.addGuardian = addGuardian;
    StudentController.goToInternalSearch = goToInternalSearch;
    StudentController.goToExternalSearch = goToExternalSearch;
    StudentController.getRedirectToGuardian = getRedirectToGuardian;
    StudentController.getRelationType = getRelationType;
    StudentController.validateDetails = validateDetails;
    StudentController.saveStudentDetails = saveStudentDetails;
    StudentController.getStudentCustomFields=getStudentCustomFields;
    StudentController.createCustomFieldsArray = createCustomFieldsArray;
    StudentController.filterBySection = filterBySection;
    StudentController.mapBySection = mapBySection;
    StudentController.changeOption = changeOption;
    StudentController.changed = changed;
    StudentController.selectOption = selectOption;
    StudentController.onDecimalNumberChange = onDecimalNumberChange;
    StudentController.setStudentData = setStudentData;
    StudentController.changeTransferReason = changeTransferReason;
    StudentController.transferStudent = transferStudent;
    StudentController.setStudentDataFromExternalSearchData = setStudentDataFromExternalSearchData;
    StudentController.transferStudentNextStep = transferStudentNextStep;
    StudentController.checkConfigForExternalSearch = checkConfigForExternalSearch;
    StudentController.isIdentityUserExist = false;
    StudentController.isNextButtonShouldDisable = isNextButtonShouldDisable;
    StudentController.getCSPDSearchData = getCSPDSearchData;
    StudentController.checkUserExistByIdentityFromConfiguration=checkUserExistByIdentityFromConfiguration;
    //POCOR-6172-HINDOL[START]
    StudentController.multipleInstitutionsStudentEnrollment=true;
    StudentController.getMultipleInstitutionsStudentEnrollment=getMultipleInstitutionsStudentEnrollment
    //POCOR-6172-HINDOL[END]
    //POCOR-7224-HINDOL[START]
    StudentController.studentExistInTheSameSchool = studentExistInTheSameSchool;
    StudentController.nextStepFromStudentExistInTheSameSchool = nextStepFromStudentExistInTheSameSchool;
    StudentController.studentExistInTheOtherSchool = studentExistInTheOtherSchool;
    StudentController.nextStepFromStudentExistInTheOtherSchool = nextStepFromStudentExistInTheOtherSchool;
    StudentController.studentExistInUnfinishedWithdraw = studentExistInUnfinishedWithdraw;
    StudentController.nextStepFromStudentExistInUnfinishedWithdraw = nextStepFromStudentExistInUnfinishedWithdraw;
    StudentController.studentExistInUnfinishedTransfer = studentExistInUnfinishedTransfer;
    StudentController.nextStepFromStudentExistInUnfinishedTransfer = nextStepFromStudentExistInUnfinishedTransfer;
    StudentController.gotoConfirmStep = gotoConfirmStep;
    StudentController.gotoAddStudentStep = gotoAddStudentStep;
    //POCOR-7224-HINDOL[END]

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.init(angular.baseUrl);
        StudentController.institutionId = Number($window.localStorage.getItem("institution_id"));
        StudentController.translateFields = {
            'openemis_no': 'OpenEMIS ID',
            'name': 'Name',
            'gender_name': 'Gender',
            'date_of_birth': 'Date Of Birth',
            'nationality_name': 'Nationality',
            'identity_type_name': 'Identity Type',
            'identity_number': 'Identity Number',
            'account_type': 'Account Type'
        };
        if($window.localStorage.getItem('address_area')) {
            $window.localStorage.removeItem('address_area')
        }
        if($window.localStorage.getItem('address_area_id')) {
            $window.localStorage.removeItem('address_area_id')
        }
        if($window.localStorage.getItem('birthplace_area')) {
            $window.localStorage.removeItem('birthplace_area')
        }
        if($window.localStorage.getItem('birthplace_area_id')) {
            $window.localStorage.removeItem('birthplace_area_id')
        }
        if($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        StudentController.initGrid();
        StudentController.getGenders();
        $window.localStorage.removeItem('repeater_validation');
    });

    function getUniqueOpenEmisId() {
        if((StudentController.isInternalSearchSelected || StudentController.isExternalSearchSelected)  &&
            StudentController.selectedStudentData.openemis_no && !isNaN(Number(StudentController.selectedStudentData.openemis_no.toString()))) {
            StudentController.selectedStudentData.username = angular.copy(StudentController.selectedStudentData.openemis_no);
            return;
        }
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getUniqueOpenEmisId()
            .then(function(response) {
                StudentController.selectedStudentData.openemis_no = response;
                StudentController.selectedStudentData.username = angular.copy(StudentController.selectedStudentData.openemis_no);
                UtilsSvc.isAppendLoader(false);
            }, function(error) {
                UtilsSvc.isAppendLoader(false);
                console.log(error);
            });
    }

    function getInternalSearchData() {
        var first_name = '';
        var last_name = '';
        var openemis_no = '';
        var date_of_birth = '';
        var identity_number = '';
        var nationality_id = '';
        var nationality_name = '';
        var identity_type_name = '';
        var identity_type_id = '';

        first_name = StudentController.selectedStudentData.first_name;
        last_name = StudentController.selectedStudentData.last_name;
        date_of_birth = StudentController.selectedStudentData.date_of_birth;
        identity_number = StudentController.selectedStudentData.identity_number;
        openemis_no = StudentController.selectedStudentData.openemis_no;
        nationality_id = StudentController.selectedStudentData.nationality_id;
        nationality_name = StudentController.selectedStudentData.nationality_name;
        identity_type_name = StudentController.selectedStudentData.identity_type_name;
        identity_type_id = StudentController.selectedStudentData.identity_type_id;

        var dataSource = {
            pageSize: StudentController.pageSize,
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
                    institution_id: StudentController.institutionId,
                    user_type_id: 1,
                    nationality_id: nationality_id,
                    nationality_name: nationality_name,
                    identity_type_name: identity_type_name,
                    identity_type_id: identity_type_id
                }
                InstitutionsStudentsSvc.getInternalSearchData(param)
                    .then(function(response) {
                        var gridData = response.data.data;
                        if(!gridData)
                            gridData=[];

                        StudentController.isSearchResultEmpty = gridData.length === 0;
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        return StudentController.processInternalGridUserRecord(gridData, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        StudentController.internalGridOptions.api.setDatasource(dataSource);
        StudentController.internalGridOptions.api.sizeColumnsToFit();
    }

    function processInternalGridUserRecord(userRecords, params, totalRowCount) {
        // console.log(userRecords);
        if (userRecords.length === 0)
        {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = userRecords;

        params.successCallback(StudentController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function getExternalSearchData() {
        var param = {
            first_name: StudentController.selectedStudentData.first_name,
            last_name: StudentController.selectedStudentData.last_name,
            date_of_birth: StudentController.selectedStudentData.date_of_birth,
            identity_number: StudentController.selectedStudentData.identity_number,
            openemis_no: StudentController.selectedStudentData.openemis_no
        };
        var dataSource = {
            pageSize: StudentController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                InstitutionsStudentsSvc.getExternalSearchData(param)
                    .then(function(response) {
                        var gridData = response.data.data;
                        if(!gridData)
                            gridData = [];
                        gridData.forEach((data) => {
                            data.gender = data['gender.name'];
                            data.nationality = data['main_nationality.name'];
                            data.identity_type = data['main_identity_type.name'];
                            data.gender_id = data['gender.id'];
                            data.nationality_id = data['main_nationality.id'];
                            data.identity_type_id = data['main_identity_type.id'];
                        });
                        StudentController.isSearchResultEmpty = gridData.length === 0;
                        var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                        return StudentController.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        StudentController.externalGridOptions.api.setDatasource(dataSource);
        StudentController.externalGridOptions.api.sizeColumnsToFit();
    }

    function processExternalGridUserRecord(userRecords, params, totalRowCount) {
        // console.log(userRecords);
        if (userRecords.length === 0)
        {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }

        var lastRow = totalRowCount;
        StudentController.rowsThisPage = userRecords;
        params.successCallback(StudentController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function generatePassword() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.generatePassword()
            .then(function(response) {
                StudentController.selectedStudentData.password = response;
                StudentController.getAcademicPeriods();
            }, function(error) {
                console.log(error);
                StudentController.getAcademicPeriods();
            });
    }

    function getGenders(){
        InstitutionsStudentsSvc.getGenders().then(function(resp){
            StudentController.genderOptions = resp;
            StudentController.getNationalities();
        }, function(error){
            console.log(error);
            StudentController.getNationalities();
        });
    }

    //POCOR-6172-HINDOL[START]
    function getMultipleInstitutionsStudentEnrollment(){
        InstitutionsStudentsSvc.getMultipleInstitutionsStudentEnrollmentConfig()
            .then(function(resp){
                // console.log(resp);
                const config_value = resp.data[0].value == "1" ? true : false;
                StudentController.multipleInstitutionsStudentEnrollment = config_value;
            }, function(error){
                console.log(error);
            });
    }
    //POCOR-6172-HINDOL[END]

    function getNationalities(){
        InstitutionsStudentsSvc.getNationalities().then(function(resp){
            StudentController.nationalitiesOptions = resp.data;
            StudentController.getIdentityTypes();
        }, function(error){
            console.log(error);
            StudentController.getIdentityTypes();
        });
    }

    function getIdentityTypes(){
        InstitutionsStudentsSvc.getIdentityTypes().then(function(resp){
            StudentController.identityTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
        StudentController.checkConfigForExternalSearch()
    }

    function getAcademicPeriods() {
        InstitutionsStudentsSvc.getAcademicPeriods().then(function(resp){
            StudentController.academicPeriodOptions = resp.data;
            StudentController.getStudentCustomFields();
        }, function(error){
            console.log(error);
            StudentController.getStudentCustomFields();
        });
    }

    function getEducationGrades() {
        if(!StudentController.selectedStudentData.academic_period_id){
            StudentController.selectedStudentData.academic_period_id = StudentController.studentData.current_enrol_academic_period_id;
        }
        UtilsSvc.isAppendLoader(true);
        StudentController.selectedStudentData.education_grade_id = null;
        var param = {
            academic_periods: StudentController.selectedStudentData.academic_period_id,
            institution_id: StudentController.institutionId
        };
        InstitutionsStudentsSvc.getEducationGrades(param).then(function(resp){
            if(resp.data !== 'null')
                StudentController.educationGradeOptions = resp.data;
            else
                StudentController.educationGradeOptions = [];
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getClasses() {
        if(!StudentController.selectedStudentData.education_grade_id)
            return;
        var params = {
            academic_period: StudentController.selectedStudentData.academic_period_id,
            institution_id: StudentController.institutionId,
            grade_id: StudentController.selectedStudentData.education_grade_id
        };
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getClasses(params).then(function(resp){
            if(resp.data !== 'null')
                StudentController.classOptions = resp.data;
            else
                StudentController.classOptions = [];
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    $window.savePhoto = function(event) {
        let photo = event.files[0];
        StudentController.selectedStudentData.photo = photo;
        StudentController.selectedStudentData.photo_name = photo.name;
        let fileReader = new FileReader();
        fileReader.readAsDataURL(photo);
        fileReader.onload = () => {
            // console.log(fileReader.result);
            StudentController.selectedStudentData.photo_base_64 = fileReader.result;
        }
    }

    function getStudentCustomFields() {
        let studentId = StudentController.studentData && StudentController.studentData.id ? StudentController.studentData.id : null;
        InstitutionsStudentsSvc.getStudentCustomFields(studentId).then(function(resp){
            StudentController.customFields = resp.data;
            StudentController.customFieldsArray = [];
            StudentController.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function createCustomFieldsArray() {
        var selectedCustomField = StudentController.customFields;
        if (selectedCustomField === "null") return;
        var filteredSections = Array.from(new Set(StudentController.customFields.map((item)=> mapBySection(item))));
        filteredSections.forEach((section)=>{
            let filteredArray = selectedCustomField.filter((item) => StudentController.filterBySection(item, section));
            StudentController.customFieldsArray.push({sectionName: section , data: filteredArray});
        });
        StudentController.customFieldsArray.forEach((customField) => {
            customField.data.forEach((fieldData) => {
                fieldData.answer = '';
                fieldData.errorMessage = '';
                if(fieldData.field_type === 'TEXT' || fieldData.field_type === 'TEXTAREA' || fieldData.field_type === 'NOTE') {
                    fieldData.answer = fieldData.values ? fieldData.values : '';
                }
                if(fieldData.field_type === 'DROPDOWN') {
                    fieldData.selectedOptionId = '';
                    try{
                        fieldData.answer = fieldData.values && fieldData.values.length > 0 && fieldData.values[0].dropdown_val ? fieldData.values[0].dropdown_val.toString() : '';
                    }catch (e) {
                        console.error(e);
                        // console.log(customField);
                        console.log(fieldData);
                        fieldData.answer = "";
                    }
                    fieldData.option.forEach((option) => {
                        if(option.option_id === fieldData.answer) {
                            fieldData.selectedOption = option.option_name;
                        }
                    })
                }
                if(fieldData.field_type === 'DATE') {
                    fieldData.isDatepickerOpen = false;
                    let params = fieldData.params !== '' ? JSON.parse(fieldData.params) : null;
                    fieldData.params = params;
                    fieldData.datePickerOptions = {
                        minDate: fieldData.params && fieldData.params.start_date ? new Date(fieldData.params.start_date): new Date(),
                        maxDate: new Date('01/01/2100'),
                        showWeeks: false
                    };
                    fieldData.answer = new Date(fieldData.values);
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
                            fieldData.answer.push(value.checkbox_val.toString());
                            fieldData.option.forEach((option)=> {
                                if(option.option_id === value.checkbox_val.toString()) {
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

    function mapBySection(item) {
        return item.section;
    }

    function filterBySection(item, section) {
        return section === item.section;
    }

    function changeOption(field, optionId){
        field.option.forEach((option) => {
            if(option.option_id === optionId){
                field.selectedOption = option.option_name;
            }
        })
    }

    function changed(answer){
        console.log(answer);
    }

    function selectOption (field) {
        field.answer = [];
        field.option.forEach((option) => {
            if(option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    function onDecimalNumberChange(field) {
        let timer;
        if(timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(()=>{
            field.answer = parseFloat(field.answer.toFixed(field.params.precision));
        }, 3000);
    }

    function setStudentName() {
        var studentData = StudentController.selectedStudentData;
        studentData.name = '';

        if (studentData.hasOwnProperty('first_name')) {
            studentData.name = studentData.first_name.trim();
        }
        StudentController.appendName(studentData, 'middle_name', true);
        StudentController.appendName(studentData, 'third_name', true);
        StudentController.appendName(studentData, 'last_name', true);
        StudentController.selectedStudentData = studentData;
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
        var studentData = StudentController.selectedStudentData;
        if (studentData.hasOwnProperty('gender_id')) {
            var genderOptions = StudentController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == studentData.gender_id) {
                    studentData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StudentController.selectedStudentData = studentData;
        }
        StudentController.error.gender_id = ''
    }

    function changeNationality() {
        var nationalityId = StudentController.selectedStudentData.nationality_id;
        if (nationalityId === null)
        {
            StudentController.selectedStudentData.nationality_name = "";
        }
        var nationalityOptions = StudentController.nationalitiesOptions;
        var identityOptions = StudentController.identityTypeOptions;
        for (var i = 0; i < nationalityOptions.length; i++) {
            if (nationalityOptions[i].id == nationalityId) {
                if (nationalityOptions[i].identity_type_id == null) {
                    StudentController.selectedStudentData.identity_type_id = identityOptions['0'].id;
                    StudentController.selectedStudentData.identity_type_name = identityOptions['0'].name;
                } else {
                    StudentController.selectedStudentData.identity_type_id = nationalityOptions[i].identity_type_id;
                    StudentController.selectedStudentData.identity_type_name = nationalityOptions[i].identity_type.name;
                }
                StudentController.selectedStudentData.nationality_name = nationalityOptions[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StudentController.selectedStudentData.identity_type_id;
        if (identityType === null)
        {
            StudentController.selectedStudentData.identity_number = '';
            StudentController.selectedStudentData.identity_type_name = '';
            return;
        }
        var identityOptions = StudentController.identityTypeOptions;
        for (var i = 0; i < identityOptions.length; i++) {
            if (identityOptions[i].id == identityType) {
                StudentController.selectedStudentData.identity_type_name = identityOptions[i].name;
                break;
            }
        }
    }

    async function changeAcademicPeriod() {
        var academicPeriod = StudentController.selectedStudentData.academic_period_id;
        var academicPeriodOptions = StudentController.academicPeriodOptions;
        for (var i = 0; i < academicPeriodOptions.length; i++) {
            if (academicPeriodOptions[i].id == academicPeriod) {
                StudentController.selectedStudentData.academic_period_name = academicPeriodOptions[i].name;
                break;
            }
        }
        StudentController.error.academic_period_id = '';
        const startDateRangeResponse = await InstitutionsStudentsSvc.getStartDateFromAcademicPeriod({ academic_period_id:academicPeriod});
        const { start_date, end_date} = startDateRangeResponse.data[0];
        StudentController.getEducationGrades();
        var startDatePicker2 = angular.element(document.getElementById('Student_start_date'));
        startDatePicker2.datepicker("setStartDate", InstitutionsStudentsSvc.formatDate(start_date));
        startDatePicker2.datepicker("setEndDate", InstitutionsStudentsSvc.formatDate(end_date));
        StudentController.selectedStudentData.endDate = InstitutionsStudentsSvc.formatDate(end_date);
    }

    function changeClass() {
        var className = StudentController.selectedStudentData.class_id;
        var classOptions = StudentController.classOptions;
        for (var i = 0; i < classOptions.length; i++) {
            if (classOptions[i].id == className) {
                StudentController.selectedStudentData.education_grade_name = classOptions[i].name;
                break;
            }
        }
    }

    async function changeEducationGrade() {
        var educationGrade = StudentController.selectedStudentData.education_grade_id;
        var academicPeriod = StudentController.selectedStudentData.academic_period_id;
        var educationGradeOptions = StudentController.educationGradeOptions;
        for (var i = 0; i < educationGradeOptions.length; i++) {
            if (educationGradeOptions[i].education_grade_id == educationGrade) {
                StudentController.selectedStudentData.education_grade_name = educationGradeOptions[i].name;
                break;
            }
        }
        StudentController.error.education_grade_id = '';
        StudentController.getClasses();

        const date_of_birth = InstitutionsStudentsSvc.formatDate(StudentController.selectedStudentData.date_of_birth);
        // console.log(date_of_birth);
        // const params = {
        //     date_of_birth: StudentController.selectedStudentData.date_of_birth,
        //     education_grade_id: educationGrade,
        //     academic_period_id: academicPeriod };
        // console.log(params);
        if (StudentController.selectedStudentData.education_grade_id !== undefined &&
            date_of_birth !== undefined &&
            academicPeriod !== undefined)
        {
            const params = {
                date_of_birth: StudentController.selectedStudentData.date_of_birth,
                education_grade_id: educationGrade,
                academic_period_id: academicPeriod };
            // console.log(params);
            // POCOR-5672
            const dateOfBirthValidationResponse = await InstitutionsStudentsSvc.getDateOfBirthValidation(params);
            // console.log(dateOfBirthValidationResponse.data);
            const { validation_error, min_age, max_age, student_age } = dateOfBirthValidationResponse.data[0];
            if (validation_error === 1)
            {
                StudentController.error.date_of_birth = `The student is ${student_age} years old in the given Academic Period. The student should be between ${min_age} to ${max_age} years old`;
            } else if (validation_error === 0)
            {
                StudentController.error.date_of_birth = "";
            }
        }
    }

    function changeTransferReason() {
        StudentController.selectedStudentData.transferReason = {};
        var transferReason = StudentController.selectedStudentData.transfer_reason_id;
        var transferReasonOptions = StudentController.transferReasonsOptions;
        for (var i = 0; i < transferReasonOptions.length; i++) {
            if (transferReasonOptions[i].id == transferReason) {
                StudentController.selectedStudentData.transferReason.name = transferReasonOptions[i].name;
                break;
            }
        }
        StudentController.error.transfer_reason_id = '';
    }

    function goToInternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function(localeText){
                StudentController.internalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
                setTimeout(function(){
                    StudentController.getInternalSearchData();
                }, 1500);
            }, function(error){
                StudentController.internalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
                setTimeout(function(){
                    StudentController.getInternalSearchData();
                }, 1500);
            });
    }

    function goToExternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function(localeText){
                StudentController.externalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
                setTimeout(function(){
                    if (StudentController.externalSearchSourceName === 'Jordan CSPD'){
                        StudentController.getCSPDSearchData();
                    }else{
                        StudentController.getExternalSearchData();
                    }
                }, 1500);
            }, function(error){
                StudentController.externalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
                setTimeout(function(){
                    if (StudentController.externalSearchSourceName === 'Jordan CSPD'){
                        StudentController.getCSPDSearchData();
                    }else{
                        StudentController.getExternalSearchData();
                    }
                }, 1500);
            });
    }

    function goToPrevStep(){
        if(StudentController.isInternalSearchSelected) {
            StudentController.isInternalSearchSelected=false;
            StudentController.step = 'user_details';
            StudentController.internalGridOptions = null;
            // StudentController.goToInternalSearch();
        } else if(StudentController.isExternalSearchSelected) {
            StudentController.step = 'external_search';
            StudentController.externalGridOptions = null;
            StudentController.goToExternalSearch();
        } else {
            switch(StudentController.step){
                case 'internal_search': {
                    StudentController.selectedStudentData.date_of_birth = InstitutionsStudentsSvc.formatDate(StudentController.selectedStudentData.date_of_birth);
                    StudentController.step = 'user_details';
                    if (StudentController.isSearchResultEmpty) {
                        StudentController.selectedStudentData.openemis_no = "";
                    }
                    break;
                }
                case 'external_search':
                    StudentController.step = 'internal_search';
                    StudentController.internalGridOptions = null;
                    StudentController.goToInternalSearch();
                    break;
                case 'confirmation': {
                    if (StudentController.isExternalSearchEnable)
                    {
                        StudentController.step = 'external_search';
                        StudentController.externalGridOptions = null;
                        StudentController.goToExternalSearch();
                    } else
                    {
                        StudentController.step = 'internal_search';
                        StudentController.internalGridOptions = null;
                        StudentController.goToInternalSearch();
                    }
                    return;
                }
                case 'add_student':
                    StudentController.step = 'confirmation';
                    break;
            }
        }
    }


    //POCOR-6172-HINDOL[START]
    //POCOR-7224-HINDOL[START]

    function studentExistInTheSameSchool() {
        return (StudentController.isInternalSearchSelected
            && StudentController.studentData
            && StudentController.studentData.is_same_school)
    }

    function nextStepFromStudentExistInTheSameSchool() {
        StudentController.step = 'summary';
        StudentController.messageClass = 'alert-warning';
        StudentController.message = 'This student is already allocated to the current institution';
        StudentController.getRedirectToGuardian();
        StudentController.isInternalSearchSelected = false;
    }

    function studentExistInUnfinishedWithdraw() {
        return (StudentController.isInternalSearchSelected
            && StudentController.studentData
            && StudentController.studentData.is_pending_withdraw)
    }

    function nextStepFromStudentExistInUnfinishedWithdraw() {
        StudentController.step = 'summary';
        StudentController.messageClass = 'alert-warning';
        StudentController.message = `This student has an unfinished withdraw from 
        ${StudentController.studentData.pending_withdraw_institution_code} 
        - ${StudentController.studentData.pending_withdraw_institution_name}.
        Please connect responsible person to finish this operation`;
        // StudentController.getRedirectToGuardian();
        StudentController.isInternalSearchSelected = false;
    }

    function studentExistInUnfinishedTransfer() {
        return (StudentController.isInternalSearchSelected
            && StudentController.studentData
            && StudentController.studentData.is_pending_transfer);
    }

    function nextStepFromStudentExistInUnfinishedTransfer() {
        StudentController.step = 'summary';
        StudentController.messageClass = 'alert-warning';
        StudentController.message = `This student has unfinished tranfer from 
        ${StudentController.studentData.pending_transfer_prev_institution_code} 
        - ${StudentController.studentData.pending_transfer_prev_institution_name}
        to ${StudentController.studentData.pending_transfer_institution_code} 
        - ${StudentController.studentData.pending_transfer_institution_name}.
        Please connect responsible person to finish this operation`;
        // StudentController.getRedirectToGuardian();
        StudentController.isInternalSearchSelected = false;
    }

    function studentExistInTheOtherSchool() {
        return (StudentController.isInternalSearchSelected
            && StudentController.studentData
            && StudentController.studentData.is_diff_school
        )
    }

    function nextStepFromStudentExistInTheOtherSchool() {
        StudentController.step = 'summary';
        StudentController.messageClass = 'alert-warning';
        StudentController.message = `This student is already allocated 
        to ${StudentController.studentData.current_enrol_institution_code} 
        - ${StudentController.studentData.current_enrol_institution_name}`;
        StudentController.getStudentTransferReason();
        StudentController.isInternalSearchSelected = false;
    }

    function gotoConfirmStep() {
        StudentController.step = 'confirmation';
        StudentController.selectedStudentData.endDate = '31-12-' + StudentController.currentYear;
        StudentController.generatePassword();
    }

    function gotoAddStudentStep() {
        StudentController.step = 'add_student';
        StudentController.selectedStudentData.endDate = '31-12-' + StudentController.currentYear;
        StudentController.generatePassword();
    }

    async function goToNextStep() {

        StudentController.messageClass = '';
        StudentController.message = ``;
        if (StudentController.step === 'confirmation') {
            const studentExistByIdentityFromConfiguration =
                await StudentController.checkUserExistByIdentityFromConfiguration();

            if (studentExistByIdentityFromConfiguration)
                return;
        }

        if (StudentController.studentExistInUnfinishedWithdraw()) {
            // console.log('studentExistInUnfinishedWithdraw');
            StudentController.nextStepFromStudentExistInUnfinishedWithdraw();
            // console.log('studentExistInUnfinishedWithdraw');
            return;
        }

        if (StudentController.studentExistInUnfinishedTransfer()) {
            // console.log('studentExistInUnfinishedTransfer');
            StudentController.nextStepFromStudentExistInUnfinishedTransfer();
            // console.log('nextStepFromStudentExistInUnfinishedTransfer');
            return;
        }

        if (StudentController.studentExistInTheSameSchool()) {
            StudentController.nextStepFromStudentExistInTheSameSchool();
            return;
        }
        const single_institutions_student_enrollment =
            !(StudentController.multipleInstitutionsStudentEnrollment);
        if (single_institutions_student_enrollment) {
            if (StudentController.studentExistInTheOtherSchool()) {
                StudentController.nextStepFromStudentExistInTheOtherSchool();
                return;
            }
        }


        if (StudentController.isInternalSearchSelected) {
            StudentController.gotoConfirmStep();
            StudentController.isInternalSearchSelected = false;
            return;
        }

        if (StudentController.isExternalSearchSelected) {
            switch (StudentController.step) {
                case "external_search":
                    StudentController.gotoConfirmStep();
                    break;
                case "confirmation":
                    StudentController.gotoAddStudentStep();
                    break;
            }
            return;
        }

        switch (StudentController.step) {
            case 'user_details':
                StudentController.validateDetails();
                break;
            case 'internal_search': {
                if (StudentController.isExternalSearchEnable) {
                    StudentController.step = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    return;
                }
                StudentController.step = 'confirmation';
                StudentController.getUniqueOpenEmisId();
                return;
            }
                break;
            case 'external_search':
                StudentController.step = 'confirmation';
                StudentController.getUniqueOpenEmisId();
                break;
            case 'confirmation':
                StudentController.gotoAddStudentStep();
                break;
        }

    }
    //POCOR-6172-HINDOL[END]
    //POCOR-7224-HINDOL[END]

    async function validateDetails()
    {
        const [blockName, hasError] = checkUserDetailValidationBlocksHasError();
        StudentController.error.first_name = '';
        StudentController.error.last_name = '';
        StudentController.error.gender_id = '';
        StudentController.error.date_of_birth = '';
        StudentController.error.nationality_id = '';
        StudentController.error.identity_type_id = '';
        StudentController.error.identity_number = '';

        if(blockName==='Identity' && hasError){
            if (!StudentController.selectedStudentData.nationality_id)
            {
                StudentController.error.nationality_id = 'This field cannot be left empty';
            }
            if (!StudentController.selectedStudentData.identity_type_id)
            {
                StudentController.error.identity_type_id = 'This field cannot be left empty';
            }
            if (!StudentController.selectedStudentData.identity_number)
            {
                StudentController.error.identity_number = 'This field cannot be left empty';
            }
        } else if (blockName === "General_Info" && hasError)
        {
            if (!StudentController.selectedStudentData.first_name)
            {
                StudentController.error.first_name = 'This field cannot be left empty';
            }
            if (!StudentController.selectedStudentData.last_name)
            {
                StudentController.error.last_name = 'This field cannot be left empty';
            }
            if (!StudentController.selectedStudentData.gender_id)
            {
                StudentController.error.gender_id = 'This field cannot be left empty';
            }
            if (!StudentController.selectedStudentData.date_of_birth)
            {
                StudentController.error.date_of_birth = 'This field cannot be left empty';
            } else
            {
                StudentController.selectedStudentData.date_of_birth = $filter('date')(StudentController.selectedStudentData.date_of_birth, 'yyyy-MM-dd');
            }
        }

        /*  if(!StudentController.selectedStudentData.first_name
        || !StudentController.selectedStudentData.last_name
        || !StudentController.selectedStudentData.gender_id
        || !StudentController.selectedStudentData.date_of_birth){
             return;
         } */

        if (hasError)
        {
            return;
        }
        //POCOR-6172-HINDOL[START]
        StudentController.getMultipleInstitutionsStudentEnrollment();
        //POCOR-6172-HINDOL[END]
        StudentController.step = 'internal_search';
        /* StudentController.selectedStudentData.openemis_no = ''; */
        StudentController.internalGridOptions = null;
        StudentController.goToInternalSearch();
        await checkUserAlreadyExistByIdentity();
    }

    function confirmUser() {
        let isCustomFieldNotValidated = false;
        if(!StudentController.selectedStudentData.username){
            StudentController.error.username = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.password){
            StudentController.error.password = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.academic_period_id){
            StudentController.error.academic_period_id = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.education_grade_id){
            StudentController.error.education_grade_id = 'This field cannot be left empty';
        }
        // console.log("StudentController.selectedStudentData here");
        // console.log(StudentController.selectedStudentData);
        var res = InstitutionsStudentsSvc.getEducationGradeAddStudent(StudentController.selectedStudentData.education_grade_id, StudentController.selectedStudentData.first_name, StudentController.selectedStudentData.last_name,  StudentController.selectedStudentData.openemis_no); //POCOR-7386
        var res1 = $window.localStorage.getItem('repeater_validation');
        timer = setTimeout(()=>{
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"yes"') {
                StudentController.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
                $window.localStorage.removeItem('repeater_validation');
                return;
            }
        }, 3000);
        if(!StudentController.selectedStudentData.startDate){
            StudentController.error.startDate = 'This field cannot be left empty';
        }
        if (StudentController.error.date_of_birth !== '') return;
        StudentController.customFieldsArray.forEach((customField) => {
            customField.data.forEach((field) => {
                if(field.is_mandatory === 1) {
                    if(field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'DROPDOWN' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL' || field.field_type === 'DATE' || field.field_type === 'TIME') {
                        if(!field.answer) {
                            field.errorMessage = 'This field is required.';
                            isCustomFieldNotValidated = true;
                        }
                    } else if(field.field_type === 'CHECKBOX') {
                        if(field.answer.length === 0) {
                            field.errorMessage = 'This field is required.';
                            isCustomFieldNotValidated = true;
                        }
                    }
                }
            })
        });
        if(!StudentController.selectedStudentData.username
            || !StudentController.selectedStudentData.password
            || !StudentController.selectedStudentData.academic_period_id
            || !StudentController.selectedStudentData.startDate
            || isCustomFieldNotValidated){
            return;
        }
        timer = setTimeout(()=>{
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"no"') {
                StudentController.saveStudentDetails();
                $window.localStorage.removeItem('repeater_validation')
            }
        }, 3000);
    }

    function saveStudentDetails() {
        if(StudentController.multipleInstitutionsStudentEnrollment){
            if(typeof StudentController.studentData != "undefined"){
                if(typeof StudentController.studentData.is_diff_school != "undefined") {
                    StudentController.studentData.is_diff_school = 0;
                }
            }
            if(typeof StudentController.selectedStudentData != "undefined"){
                if(typeof StudentController.selectedStudentData.is_diff_school != "undefined") {
                    StudentController.selectedStudentData.is_diff_school = 0;
                }
            }
        }
        let startDate = StudentController.studentData
        && StudentController.studentData.is_diff_school > 0 ? $filter('date')(StudentController.selectedStudentData.transferStartDate, 'yyyy-MM-dd') : $filter('date')(StudentController.selectedStudentData.startDate, 'yyyy-MM-dd');
        const addressAreaRef = InstitutionsStudentsSvc.getAddressArea();
        addressAreaRef && (StudentController.selectedStudentData.addressArea = addressAreaRef);
        const birthplaceAreaRef = InstitutionsStudentsSvc.getBirthplaceArea();
        birthplaceAreaRef && (StudentController.selectedStudentData.birthplaceArea = birthplaceAreaRef)
        var params = {
            currentAcademicPeriod: StudentController.currentAcademicPeriod,//POCOR-7733
            currentAcademicPeriodName: StudentController.currentAcademicPeriodName,//POCOR-7733
            institution_id: StudentController.institutionId,
            openemis_no: StudentController.selectedStudentData.openemis_no,
            first_name: StudentController.selectedStudentData.first_name,
            middle_name: StudentController.selectedStudentData.middle_name,
            third_name: StudentController.selectedStudentData.third_name,
            last_name: StudentController.selectedStudentData.last_name,
            preferred_name: StudentController.selectedStudentData.preferred_name,
            gender_id: StudentController.selectedStudentData.gender_id,
            date_of_birth: StudentController.selectedStudentData.date_of_birth,
            identity_number: StudentController.selectedStudentData.identity_number,
            nationality_id: StudentController.selectedStudentData.nationality_id,
            nationality_name: StudentController.selectedStudentData.nationality_name,
            username: StudentController.selectedStudentData.username,
            password: StudentController.selectedStudentData.password,
            postal_code: StudentController.selectedStudentData.postalCode,
            address: StudentController.selectedStudentData.address,
            birthplace_area_id: InstitutionsStudentsSvc.getBirthplaceAreaId() === null ? StudentController.selectedStudentData.birthplace_area_id:InstitutionsStudentsSvc.getBirthplaceAreaId(),
            address_area_id: InstitutionsStudentsSvc.getAddressAreaId() === null ? StudentController.selectedStudentData.address_area_id : InstitutionsStudentsSvc.getAddressAreaId(),
            identity_type_id: StudentController.selectedStudentData.identity_type_id,
            identity_type_name: StudentController.selectedStudentData.identity_type_name,
            education_grade_id: StudentController.selectedStudentData.education_grade_id,
            academic_period_id: StudentController.selectedStudentData.academic_period_id,
            start_date: startDate,
            end_date: StudentController.selectedStudentData.endDate,
            institution_class_id: StudentController.selectedStudentData.class_id,
            student_status_id: 1,
            photo_base_64: StudentController.selectedStudentData.photo_base_64,
            photo_name: StudentController.selectedStudentData.photo_name,
            is_diff_school: StudentController.studentData && StudentController.studentData.is_diff_school ? StudentController.studentData.is_diff_school : 0,
            student_id: StudentController.studentData && StudentController.studentData.id ? StudentController.studentData.id : null,
            previous_institution_id: StudentController.studentData && StudentController.studentData.current_enrol_institution_id ? StudentController.studentData.current_enrol_institution_id : null,
            previous_academic_period_id: StudentController.studentData && StudentController.studentData.current_enrol_academic_period_id ? StudentController.studentData.current_enrol_academic_period_id : null,
            previous_education_grade_id: StudentController.studentData && StudentController.studentData.current_enrol_education_grade_id ? StudentController.studentData.current_enrol_education_grade_id : null,
            student_transfer_reason_id: StudentController.selectedStudentData.transfer_reason_id ? StudentController.selectedStudentData.transfer_reason_id : null,
            comment: StudentController.selectedStudentData.transferComment,
            custom: [],
        };
        StudentController.customFieldsArray.forEach((customField)=> {
            customField.data.forEach((field)=> {
                if(field.field_type !== 'CHECKBOX') {
                    let fieldData = {
                        student_custom_field_id: field.student_custom_field_id,
                        text_value:"",
                        number_value:null,
                        decimal_value:"",
                        textarea_value:"",
                        time_value:"",
                        date_value:"",
                        file:"",
                        institution_id: StudentController.institutionId,
                    };
                    if(field.field_type === 'TEXT' || field.field_type === 'NOTE') {
                        fieldData.text_value = field.answer;
                    }
                    if (field.field_type === 'TEXTAREA'){
                        fieldData.textarea_value = field.answer;
                    }
                    if(field.field_type === 'NUMBER') {
                        fieldData.number_value = field.answer;
                    }
                    if(field.field_type === 'DECIMAL') {
                        fieldData.decimal_value = String(field.answer);
                    }
                    if(field.field_type === 'DROPDOWN') {
                        fieldData.number_value = Number(field.answer);
                    }
                    if(field.field_type === 'TIME') {
                        let time = field.answer.toLocaleTimeString();
                        let timeArray = time.split(':');
                        fieldData.time_value = `${timeArray[0]}:${timeArray[1]}`;
                    }
                    if(field.field_type === 'DATE') {
                        fieldData.date_value = $filter('date')(field.answer, 'yyyy-MM-dd');
                    }
                    params.custom.push(fieldData);
                } else {
                    field.answer.forEach((id )=> {
                        let fieldData = {
                            student_custom_field_id: field.student_custom_field_id,
                            text_value:"",
                            number_value: Number(id),
                            decimal_value:"",
                            textarea_value:"",
                            time_value:"",
                            date_value:"",
                            file:"",
                            institution_id: StudentController.institutionId,
                        };
                        params.custom.push(fieldData);
                    });
                }
            });
        });
        //POCOR-7733 start
        if (params.is_diff_school > 0) {
            if (params.currentAcademicPeriod != params.previous_academic_period_id) {
                if (params.student_status_id == 1) {
                    StudentController.message = `This student is allocated to ${StudentController.studentData.current_enrol_institution_code} 
                                               - ${StudentController.studentData.current_enrol_institution_name} in a different
                                                 Academic Period. Transfer can only happen for students in current
                                                 Academic Period.`;
                    StudentController.messageClass = "alert-warning";
                    UtilsSvc.isAppendLoader(false);
                    return;
                }
            }
        }
        //POCOR-7733 end
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.saveStudentDetails(params).then(function(resp){


            if(resp) {
                //POCOR-6172-HINDOL[START]
                if(StudentController.studentData &&
                    //POCOR-6172-HINDOL[END]
                    StudentController.studentData.is_diff_school > 0
                ) {
                    StudentController.message ='Student transfer request is added successfully.';
                    StudentController.messageClass = 'alert-success';
                    UtilsSvc.isAppendLoader(false);
                    $window.history.back();
                } else {
                    StudentController.message ='Student is added successfully.';
                    StudentController.messageClass = 'alert-success';
                    StudentController.step = "summary";
                    var todayDate = new Date();
                    StudentController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
                    StudentController.getRedirectToGuardian();
                }
            }
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function transferStudent() {
        if (!StudentController.selectedStudentData.education_grade_id) {
            StudentController.error.education_grade_id = 'This field cannot be left empty';
        }
        // console.log(StudentController.selectedStudentData);
        var res = InstitutionsStudentsSvc.getEducationGrade(StudentController.selectedStudentData.education_grade_id, StudentController.selectedStudentData.openemis_no);
        // $validation = JSON.parse(res.data);

        let shouldSaveData = false;

        // timer = setTimeout(() => {
        var res1 = $window.localStorage.getItem('repeater_validation');
        timer = setTimeout(()=>{
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"yes"') {
                shouldSaveData = true;
                StudentController.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
                $window.localStorage.removeItem('repeater_validation');
                return;
            }
        }, 3000);

        //   if (res1 == '"yes"') {
        //     StudentController.error.education_grade_id = 'This student has completed the education grade before. Please assign to a different grade.';
        //     shouldSaveData = false;
        //   }
        // }, 3000);

        if (!StudentController.selectedStudentData.transferStartDate) {
            StudentController.error.transferStartDate = 'This field cannot be left empty';
        } else {
            StudentController.selectedStudentData.transferStartDate = $filter('date')(StudentController.selectedStudentData.transferStartDate, 'yyyy-MM-dd');
        }
        if (!StudentController.selectedStudentData.transfer_reason_id) {
            StudentController.error.transfer_reason_id = 'This field cannot be left empty';
        }

        if (!StudentController.selectedStudentData.education_grade_id || !StudentController.selectedStudentData.transferStartDate || !StudentController.selectedStudentData.transfer_reason_id) {
            return;
        }

        timer = setTimeout(()=>{
            var res1 = $window.localStorage.getItem('repeater_validation');
            if (res1 == '"no"') {
                StudentController.saveStudentDetails();
                $window.localStorage.removeItem('repeater_validation')
            }
        }, 3000);
    }

    function goToFirstStep() {
        if(!StudentController.isGuardianAdding){
            StudentController.step = 'user_details';
            StudentController.selectedStudentData = {};
        }
        else{
            StudentController.guardianStep = 'user_details';
            StudentController.selectedGuardianData = {};
        }
    }

    function cancelProcess() {
        $window.history.back();
    }

    function addGuardian () {
        if($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        $window.localStorage.setItem('studentOpenEmisId', StudentController.selectedStudentData.openemis_no);
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian';
    }

    function getRedirectToGuardian() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getRedirectToGuardian().then(function(resp){
            StudentController.redirectToGuardian = resp.data[0].redirecttoguardian_status;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getRelationType() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getRelationType().then(function(resp){
            StudentController.relationTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    StudentController.selectStudentFromInternalSearch = function(id) {
        StudentController.selectedStudent = id;
        StudentController.isInternalSearchSelected = true;
        StudentController.isExternalSearchSelected = false;
        StudentController.getStudentData();

        if (StudentController.isIdentityUserExist)
        {
            StudentController.messageClass = '';
            StudentController.message = '';
            StudentController.isIdentityUserExist = false;
        }

        StudentController.disableFields = {
            username: true,
            password:true
        }
    }

    StudentController.selectStudentFromExternalSearch = function(id) {
        StudentController.selectedStudent = id;
        StudentController.isInternalSearchSelected = false;
        StudentController.isExternalSearchSelected = true;
        StudentController.getStudentData();
        StudentController.disableFields = {
            username: false,
            password: false
        }
    }

    StudentController.getStudentData = function() {
        var log = [];
        angular.forEach(StudentController.rowsThisPage , function(value) {
            if (value.id == StudentController.selectedStudent) {
                StudentController.studentData = value;
                if(StudentController.isInternalSearchSelected) {
                    StudentController.studentData.currentlyAllocatedTo = value.current_enrol_institution_code + ' - ' + value.current_enrol_institution_name;
                    StudentController.setStudentData(value);
                }
                if(StudentController.isExternalSearchSelected) {
                    StudentController.setStudentDataFromExternalSearchData(value);
                }
            }
        }, log);
    }

    function setStudentData(selectedData) {
        StudentController.selectedStudentData.addressArea = {
            id: selectedData.address_area_id,
            name: selectedData.area_name,
            code: selectedData.area_code
        };
        StudentController.selectedStudentData.birthplaceArea = {
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name,
            code: selectedData.birth_area_code
        };
        StudentController.selectedStudentData.user_id = selectedData.id;
        StudentController.selectedStudentData.openemis_no = selectedData.openemis_no;
        StudentController.selectedStudentData.name = selectedData.name;//POCOR-7172
        StudentController.selectedStudentData.first_name = selectedData.first_name;
        StudentController.selectedStudentData.middle_name = selectedData.middle_name;
        StudentController.selectedStudentData.third_name = selectedData.third_name;
        StudentController.selectedStudentData.last_name = selectedData.last_name;
        StudentController.selectedStudentData.preferred_name = selectedData.preferred_name;
        StudentController.selectedStudentData.gender_id = selectedData.gender_id;
        StudentController.selectedStudentData.gender = {
            name: selectedData.gender
        };
        StudentController.selectedStudentData.date_of_birth = selectedData.date_of_birth;
        StudentController.selectedStudentData.email = selectedData.email;
        StudentController.selectedStudentData.identity_type_name = selectedData.identity_type;
        StudentController.selectedStudentData.identity_number = selectedData.identity_number;
        StudentController.selectedStudentData.nationality_name = selectedData.nationality;
        StudentController.selectedStudentData.address = selectedData.address;
        StudentController.selectedStudentData.postalCode = selectedData.postal_code;
        StudentController.selectedStudentData.addressArea.name = selectedData.area_name;
        StudentController.selectedStudentData.birthplaceArea.name = selectedData.birth_area_name;
        StudentController.selectedStudentData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
        var todayDate = new Date();
        StudentController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
        StudentController.isSameSchool = selectedData.is_same_school > 0 ? true : false;
        StudentController.isDiffSchool = selectedData.is_diff_school ? true : false;
        if(StudentController.multipleInstitutionsStudentEnrollment){
            StudentController.isDiffSchool = false;
        }
        if(selectedData.is_pending_withdraw){
            StudentController.isDiffSchool = false;
        }
        if(selectedData.is_pending_transfer){
            StudentController.isDiffSchool = false;
        }
        StudentController.selectedStudentData.currentlyAllocatedTo = selectedData.current_enrol_institution_code + ' - ' + selectedData.current_enrol_institution_name;

        StudentController.selectedStudentData.birthplace_area_id = selectedData.birthplace_area_id === undefined ? null : selectedData.birthplace_area_id;
        StudentController.selectedStudentData.address_area_id = selectedData.address_area_id === undefined ? null : selectedData.address_area_id;
        StudentController.selectedStudentData.birth_area_code = selectedData.birth_area_code === undefined ? '' : selectedData.birth_area_code;
        StudentController.selectedStudentData.area_code = selectedData.area_code === undefined ? '' : selectedData.area_code;

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

    function setStudentDataFromExternalSearchData(selectedData)
    {
        if(StudentController.externalSearchSourceName==='Jordan CSPD'){
            InstitutionsStudentsSvc.getUniqueOpenEmisId().then((response)=>{
                const selectedObjectWithOpenemisNo =  Object.assign({}, selectedData, {'openemis_no':response})
                selectedData = selectedObjectWithOpenemisNo;
                StudentController.selectedStudentData.addressArea = {
                    id: selectedData.address_area_id,
                    name: selectedData.area_name,
                    code: selectedData.area_code
                };
                StudentController.selectedStudentData.birthplaceArea = {
                    id: selectedData.birthplace_area_id,
                    name: selectedData.birth_area_name,
                    code: selectedData.birth_area_code
                };
                StudentController.selectedStudentData.openemis_no = selectedData.openemis_no;
                StudentController.selectedStudentData.first_name = selectedData.first_name;
                StudentController.selectedStudentData.middle_name = selectedData.middle_name;
                StudentController.selectedStudentData.third_name = selectedData.third_name;
                StudentController.selectedStudentData.last_name = selectedData.last_name;
                StudentController.selectedStudentData.preferred_name = selectedData.preferred_name;
                StudentController.selectedStudentData.gender_id = selectedData.gender_id;
                StudentController.selectedStudentData.gender = {
                    name: selectedData.gender
                };
                StudentController.selectedStudentData.date_of_birth = selectedData.date_of_birth;
                StudentController.selectedStudentData.email = selectedData.email;
                StudentController.selectedStudentData.identity_type_name = selectedData.identity_type;
                StudentController.selectedStudentData.identity_type_id = selectedData.identity_type_id;
                StudentController.selectedStudentData.identity_number = selectedData.identity_number;
                StudentController.selectedStudentData.nationality_name = selectedData.nationality;
                StudentController.selectedStudentData.address = selectedData.address;
                StudentController.selectedStudentData.postalCode = selectedData.postal_code;
                StudentController.selectedStudentData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
                var todayDate = new Date();
                StudentController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');

                StudentController.selectedStudentData.birthplace_area_id = selectedData.birthplace_area_id;
                StudentController.selectedStudentData.address_area_id = selectedData.address_area_id;
                StudentController.selectedStudentData.birth_area_code = selectedData.birth_area_code;
                StudentController.selectedStudentData.area_code = selectedData.area_code;
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
                StudentController.disableFields = {
                    username: false,
                    password: false,
                }
            })
        }else{
            StudentController.selectedStudentData.addressArea = {
                id: selectedData.address_area_id,
                name: selectedData.area_name,
                code: selectedData.area_code
            };
            StudentController.selectedStudentData.birthplaceArea = {
                id: selectedData.birthplace_area_id,
                name: selectedData.birth_area_name,
                code: selectedData.birth_area_code
            };
            StudentController.selectedStudentData.openemis_no = selectedData.openemis_no;
            StudentController.selectedStudentData.first_name = selectedData.first_name;
            StudentController.selectedStudentData.middle_name = selectedData.middle_name;
            StudentController.selectedStudentData.third_name = selectedData.third_name;
            StudentController.selectedStudentData.last_name = selectedData.last_name;
            StudentController.selectedStudentData.preferred_name = selectedData.preferred_name;
            StudentController.selectedStudentData.gender_id = selectedData.gender_id;
            StudentController.selectedStudentData.gender = {
                name: selectedData.gender
            };
            StudentController.selectedStudentData.date_of_birth = selectedData.date_of_birth;
            StudentController.selectedStudentData.email = selectedData.email;
            StudentController.selectedStudentData.identity_type_name = selectedData.identity_type;
            StudentController.selectedStudentData.identity_type_id = selectedData.identity_type_id;
            StudentController.selectedStudentData.identity_number = selectedData.identity_number;
            StudentController.selectedStudentData.nationality_name = selectedData.nationality;
            StudentController.selectedStudentData.address = selectedData.address;
            StudentController.selectedStudentData.postalCode = selectedData.postal_code;
            StudentController.selectedStudentData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
            StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
            var todayDate = new Date();
            StudentController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');

            StudentController.selectedStudentData.birthplace_area_id = selectedData.birthplace_area_id;
            StudentController.selectedStudentData.address_area_id = selectedData.address_area_id;
            StudentController.selectedStudentData.birth_area_code = selectedData.birth_area_code;
            StudentController.selectedStudentData.area_code = selectedData.area_code;
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
            StudentController.disableFields = {
                username: false,
                password: false,
            }
        }
    }

    StudentController.getStudentTransferReason = function() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getStudentTransferReason().then(function(resp){
            StudentController.transferReasonsOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
            StudentController.getEducationGrades();
        }, function(error){
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function initGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
            .then(function(localeText){
                StudentController.internalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };

                StudentController.externalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
            }, function(error){
                StudentController.internalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromInternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };

                StudentController.externalGridOptions = {
                    columnDefs: [
                        {headerName: StudentController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                        {headerName: StudentController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                        StudentController.selectStudentFromExternalSearch(_e.node.data.id);
                        $scope.$apply();
                    },
                    onGridSizeChanged: function() {
                        this.api.sizeColumnsToFit();
                    },
                };
            });
    };


    function transferStudentNextStep()
    {
        StudentController.step = 'transfer_student';
        var startDatePicker = angular.element(document.getElementById('Student_transfer_start_date'));
        var splitEndDate = StudentController.selectedStudentData.endDate.split('-');
        var endDateYear = splitEndDate[splitEndDate.length - 1];
        startDatePicker.datepicker("setStartDate", "01-01-" + endDateYear);
        startDatePicker.datepicker("setEndDate", '31-12-' + endDateYear);
    }

    async function checkUserAlreadyExistByIdentity()
    {

        const userData = StudentController.selectedStudentData;
        const userSvc = InstitutionsStudentsSvc;
        const result = await userSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id':userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id,
        });
        if (result.data.user_exist === 1)
        {
            StudentController.messageClass = 'alert-warning';
            StudentController.message = result.data.message;
            StudentController.isIdentityUserExist = true;
        } else
        {
            StudentController.messageClass = '';
            StudentController.message = '';
            StudentController.isIdentityUserExist = false;
        }
        /*  return result.data.user_exist === 1; */
    }

    /**
     * @desc 1)Identity Number is mandatory OR
     * @desc 2)OpenEMIS ID is mandatory OR
     * @desc 3)First Name, Last Name, Date of Birth and Gender are mandatory
     * @returns [ error block name | true or false]
     */
    function checkUserDetailValidationBlocksHasError()
    {
        const { first_name, last_name, gender_id, date_of_birth, identity_type_id, identity_number, openemis_no,nationality_id } = StudentController.selectedStudentData;
        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth)
        const isIdentityHasError = identity_number?.length>1  && (nationality_id === undefined || nationality_id==="" || nationality_id === null || identity_type_id===undefined || identity_type_id=== null || identity_type_id==="")
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        const isSkipableForIdentity = identity_number?.length >1 && nationality_id > 0 && identity_type_id >0;

        /**
         * New For POCOR-7351
         */
        if (isIdentityHasError)
        {
            return ['Identity', true]
        }
        if(isSkipableForIdentity){
            return ['Identity', false]
        }
        if (isOpenEmisNoHasError)
        {
            return ["OpenEMIS_ID", false];
        }
        /**
         * Prev
         */
        // if (isIdentityHasError)
        // {
        //     return ['Identity', false]
        // }
        if (isGeneralInfodHasError)
        {
            return ["General_Info", true];
        }

        return ["",false];
    }

    function checkConfigForExternalSearch()
    {
        InstitutionsStudentsSvc.checkConfigForExternalSearch().then(function (resp)
        {
            StudentController.isExternalSearchEnable = resp.showExternalSearch;
            StudentController.externalSearchSourceName = resp.value;
            UtilsSvc.isAppendLoader(false);
        }, function (error)
        {
            StudentController.isExternalSearchEnable = false;
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }
    function isNextButtonShouldDisable() {
        const { step, selectedStudentData, isIdentityUserExist } = StudentController;
        const { first_name, last_name, date_of_birth, gender_id } = selectedStudentData;

        if (isIdentityUserExist && step === "internal_search") {
            return true;
        }

        if (step === "external_search" && (!first_name|| !last_name || !date_of_birth|| !gender_id)) {
            return true;
        }
        return false;
    }


    function getCSPDSearchData() {
        var param = {
            identity_number: StudentController.selectedStudentData.identity_number,
        };
        var dataSource = {
            pageSize: StudentController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                InstitutionsStudentsSvc.getCspdData(param)
                    .then(function(response) {
                        var gridData = [response.data.data];
                        if(!gridData)gridData = [];
                        gridData.forEach((data) => {
                            data.name = `${data['first_name']} ${data['middle_name']} ${data['last_name']}`;
                            data.gender = data['gender_name'];
                            data.nationality = data['nationality_name'];
                            data.identity_type = data['identity_type_name'];
                            data.gender_id = data['gender_id'];
                            data.nationality_id = data['nationality_id'];
                            data.identity_type_id = data['identity_type_id'];
                        });
                        StudentController.isSearchResultEmpty = gridData.length === 0;
                        var totalRowCount = gridData.length === 0 ? 1 : gridData.length;
                        return StudentController.processExternalGridUserRecord(gridData, params, totalRowCount);
                    }, function(error) {
                        console.error(error);
                        UtilsSvc.isAppendLoader(false);
                    });
            }
        };
        StudentController.externalGridOptions.api.setDatasource(dataSource);
        StudentController.externalGridOptions.api.sizeColumnsToFit();
    }


    async function checkUserExistByIdentityFromConfiguration()
    {
        // console.log('checkUserExistByIdentityFromConfiguration');
        //POCOR-7481-HINDOL

        const userData = StudentController.selectedStudentData;
        const userSvc = InstitutionsStudentsSvc;
        const userCtrl = StudentController;
        const { identity_type_id, identity_number } = userData;
        if (!identity_type_id)
        {
            userCtrl.error.identity_type_id =
                "This field cannot be left empty";
            return false;
        }
        if (!identity_number)
        {
            userCtrl.error.identity_number =
                "This field cannot be left empty";
            return false;
        }

        const result = await userSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': userData.identity_type_id,
            'identity_number': userData.identity_number,
            'nationality_id':userData.nationality_id,
            'first_name': userData.first_name,
            'last_name': userData.last_name,
            'gender_id': userData.gender_id,
            'date_of_birth': userData.date_of_birth,
            'user_id': userData.user_id,
        });
        // StudentController.error.nationality_id = "";
        userCtrl.error.identity_type_id = ""
        userCtrl.error.identity_number = "";

        if (result.data.user_exist === 1)
        {
            userCtrl.messageClass = 'alert-warning';
            userCtrl.message = result.data.message;
            userCtrl.isIdentityUserExist = true;
            userCtrl.error.identity_number = result.data.message;
            $window.scrollTo({bottom:0});
        } else
        {
            userCtrl.messageClass = '';
            userCtrl.message = '';
            userCtrl.isIdentityUserExist = false;
            userCtrl.error.identity_number ==""
        }
        return result.data.user_exist === 1;
    }
}