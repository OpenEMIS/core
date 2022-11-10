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
    StudentController.studentStatus = 'Pending Transfer';
    StudentController.StudentData = {};

    StudentController.datepickerOptions = {
        showWeeks: false
    };
    StudentController.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };

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
    });

    function getUniqueOpenEmisId() {
        if(StudentController.selectedStudentData.openemis_no && !isNaN(Number(StudentController.selectedStudentData.openemis_no.toString()))) {
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
        var openemis_no = null;
        var date_of_birth = '';
        var identity_number = '';
        first_name = StudentController.selectedStudentData.first_name;
        last_name = StudentController.selectedStudentData.last_name;
        date_of_birth = StudentController.selectedStudentData.date_of_birth;
        identity_number = StudentController.selectedStudentData.identity_number;
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
                }
                InstitutionsStudentsSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData=[];
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
        console.log(userRecords);

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
        console.log(userRecords);

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
            console.log(error);
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
            console.log(fileReader.result);
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
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function createCustomFieldsArray() {
        var selectedCustomField = StudentController.customFields;
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
                    fieldData.answer = fieldData.values && fieldData.values.length > 0 ? fieldData.values[0].dropdown_val.toString() : '';
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
        var educationGradeOptions = StudentController.educationGradeOptions;
        for (var i = 0; i < educationGradeOptions.length; i++) {
            if (educationGradeOptions[i].education_grade_id == educationGrade) {
                StudentController.selectedStudentData.education_grade_name = educationGradeOptions[i].name;
                break;
            }
        }
        StudentController.error.education_grade_id = '';
        StudentController.getClasses();

        // POCOR-5672
        const dateOfBirthValidationResponse =await InstitutionsStudentsSvc.getDateOfBirthValidation({ date_of_birth: StudentController.selectedStudentData.date_of_birth, education_grade_id: educationGrade })
        const { validation_error,min_age,max_age } = dateOfBirthValidationResponse.data[0];
        if (validation_error === 1)
        {
            StudentController.error.date_of_birth = `The student should be between ${min_age} to ${max_age} years old`;
        } else if (validation_error === 0)
        {
            StudentController.error.date_of_birth = "";
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
                StudentController.getExternalSearchData();
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
                StudentController.getExternalSearchData();
            }, 1500);
        });
    }

    function goToPrevStep(){
        if(StudentController.isInternalSearchSelected) {
            StudentController.step = 'internal_search';
            StudentController.internalGridOptions = null;
            StudentController.goToInternalSearch();
        } else if(StudentController.isExternalSearchSelected) {
            StudentController.step = 'external_search';
            StudentController.externalGridOptions = null;
            StudentController.goToExternalSearch();
        } else {
            switch(StudentController.step){
                case 'internal_search': 
                    StudentController.selectedStudentData.date_of_birth = InstitutionsStudentsSvc.formatDate(StudentController.selectedStudentData.date_of_birth);
                    StudentController.step = 'user_details';
                    break;
                case 'external_search': 
                    StudentController.step = 'internal_search';
                    StudentController.internalGridOptions = null;
                    StudentController.goToInternalSearch();
                    break;
                case 'confirmation': 
                    StudentController.step = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
                case 'add_student': 
                    StudentController.step = 'confirmation';
                    break;
            }
        }
    }

    function goToNextStep() {
        if(StudentController.isInternalSearchSelected) {
            if(StudentController.studentData && StudentController.studentData.is_same_school) {
                StudentController.step = 'summary';
                StudentController.messageClass = 'alert-warning';
                StudentController.message = 'This student is already allocated to the current institution';
                StudentController.getRedirectToGuardian();
                StudentController.isInternalSearchSelected = false;
        } else if(StudentController.studentData && StudentController.studentData.is_diff_school) {
                StudentController.messageClass = 'alert-warning';
                StudentController.message = `This student is already allocated to ${StudentController.studentData.current_enrol_institution_code} - ${StudentController.studentData.current_enrol_institution_name}`;
                StudentController.step = 'summary';
                StudentController.getStudentTransferReason();
                StudentController.isInternalSearchSelected = false;
        } else {
                StudentController.step = 'confirmation';
                StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
                StudentController.generatePassword();
                StudentController.isInternalSearchSelected = false;
            }
        } else if(StudentController.isExternalSearchSelected) {

            switch (StudentController.step)
            {
                case "external_search":
                    StudentController.step = 'confirmation';
                    StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
                    StudentController.generatePassword();
                    break;
                
                case "confirmation":
                    StudentController.step = 'add_student';
                    StudentController.selectedStudentData.endDate = '31-12-' + new Date().getFullYear();
                    StudentController.generatePassword();
                    break;
            }
        } else {
            switch(StudentController.step){
                case 'user_details': 
                    StudentController.validateDetails();
                    break;
                case 'internal_search': 
                    StudentController.step = 'external_search';
                    StudentController.externalGridOptions = null;
                    StudentController.goToExternalSearch();
                    break;
                case 'external_search': 
                    StudentController.step = 'confirmation';
                    if(!StudentController.selectedStudentData.openemis_no) {
                        StudentController.getUniqueOpenEmisId();
                    }
                    break;
                case 'confirmation': 
                    StudentController.step = 'add_student';
                    StudentController.selectedStudentData.endDate = '31-12-' + StudentController.currentYear;
                    StudentController.generatePassword();
                    break;
            }
        }
    }

    function validateDetails() {
        if(!StudentController.selectedStudentData.first_name){
            StudentController.error.first_name = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.last_name){
            StudentController.error.last_name = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.gender_id){
            StudentController.error.gender_id = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.date_of_birth) {
            StudentController.error.date_of_birth = 'This field cannot be left empty';
        } else {
            StudentController.selectedStudentData.date_of_birth = $filter('date')(StudentController.selectedStudentData.date_of_birth, 'yyyy-MM-dd');
        }

        if(!StudentController.selectedStudentData.first_name || !StudentController.selectedStudentData.last_name || !StudentController.selectedStudentData.gender_id || !StudentController.selectedStudentData.date_of_birth){
            return;
        }
        StudentController.step = 'internal_search';
        StudentController.selectedStudentData.openemis_no = null;
        StudentController.internalGridOptions = null;
        StudentController.goToInternalSearch();
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
        if(!StudentController.selectedStudentData.username || !StudentController.selectedStudentData.password || !StudentController.selectedStudentData.academic_period_id || !StudentController.selectedStudentData.startDate || isCustomFieldNotValidated){
            return;
        }
        StudentController.saveStudentDetails();
    }

    function saveStudentDetails() {
        let startDate = StudentController.studentData && StudentController.studentData.is_diff_school > 0 ? $filter('date')(StudentController.selectedStudentData.transferStartDate, 'yyyy-MM-dd') : $filter('date')(StudentController.selectedStudentData.startDate, 'yyyy-MM-dd');
        StudentController.selectedStudentData.addressArea = InstitutionsStudentsSvc.getAddressArea();
        StudentController.selectedStudentData.birthplaceArea = InstitutionsStudentsSvc.getBirthplaceArea();
        var params = {
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
            birthplace_area_id: StudentController.studentData && StudentController.studentData.is_diff_school > 0 ? StudentController.studentData.birthplace_area_id : InstitutionsStudentsSvc.getBirthplaceAreaId(),
            address_area_id: StudentController.studentData && StudentController.studentData.is_diff_school > 0 ? StudentController.studentData.address_area_id : InstitutionsStudentsSvc.getAddressAreaId(),
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
                    if(field.field_type === 'TEXT' || field.field_type === 'NOTE' || field.field_type === 'TEXTAREA') {
                        fieldData.text_value = field.answer;
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
            })
        });
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.saveStudentDetails(params).then(function(resp){
            if(resp) {
                if(StudentController.studentData && StudentController.studentData.is_diff_school > 0) {
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
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function transferStudent() {
        if(!StudentController.selectedStudentData.education_grade_id){
            StudentController.error.education_grade_id = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.transferStartDate) {
            StudentController.error.transferStartDate = 'This field cannot be left empty';
        } else {
            StudentController.selectedStudentData.transferStartDate = $filter('date')(StudentController.selectedStudentData.transferStartDate, 'yyyy-MM-dd');
        }
        if(!StudentController.selectedStudentData.transfer_reason_id){
            StudentController.error.transfer_reason_id = 'This field cannot be left empty';
        }
        if(!StudentController.selectedStudentData.education_grade_id || !StudentController.selectedStudentData.transferStartDate || !StudentController.selectedStudentData.transfer_reason_id) {
            return;
        }
        StudentController.saveStudentDetails();
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
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getRelationType() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getRelationType().then(function(resp){
            StudentController.relationTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    StudentController.selectStudentFromInternalSearch = function(id) {
        StudentController.selectedStudent = id;
        StudentController.isInternalSearchSelected = true;
        StudentController.isExternalSearchSelected = false;
        StudentController.getStudentData();
    }

    StudentController.selectStudentFromExternalSearch = function(id) {
        StudentController.selectedStudent = id;
        StudentController.isInternalSearchSelected = false;
        StudentController.isExternalSearchSelected = true;
        StudentController.getStudentData();
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
        StudentController.selectedStudentData.addressArea = {};
        StudentController.selectedStudentData.birthplaceArea = {};
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
        StudentController.isDiffSchool = selectedData.is_diff_school > 0 ? true : false;
        StudentController.selectedStudentData.currentlyAllocatedTo = selectedData.current_enrol_institution_code + ' - ' + selectedData.current_enrol_institution_name;
    }

    function setStudentDataFromExternalSearchData(selectedData) {
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
    }

    StudentController.getStudentTransferReason = function() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStudentsSvc.getStudentTransferReason().then(function(resp){
            StudentController.transferReasonsOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
            StudentController.getEducationGrades();
        }, function(error){
            console.log(error);
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
}