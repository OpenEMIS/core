angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.staff.svc', 'angular.chosen', 'kd-angular-tree-dropdown'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStaffSvc', '$rootScope'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStaffSvc, $rootScope) {
    // ag-grid vars

    console.log("Nov 21 - Works")

    var StaffController = this;

    StaffController.pageSize = 10;
    StaffController.step = 'user_details';
    StaffController.selectedStaffData = { };
    StaffController.internalGridOptions = null;
    StaffController.externalGridOptions = null;
    StaffController.postRespone = null;
    StaffController.translateFields = null;
    StaffController.nationality_class = 'input select error';
    StaffController.identity_type_class = 'input select error';
    StaffController.identity_class = 'input string';
    StaffController.messageClass = '';
    StaffController.message = '';
    StaffController.genderOptions = [];
    StaffController.nationalitiesOptions = [];
    StaffController.identityTypeOptions = [];
    StaffController.positionTypeOptions = [];
    StaffController.institutionPositionOptions = {
        availableOptions: [],
        selectedOption: ''
    };
    StaffController.staffTypeOptions = [];
    StaffController.shiftsOptions = [];
    StaffController.fteOptions = [];
    StaffController.shiftsId = [];
    StaffController.rowsThisPage= [];
    StaffController.institutionId = null;
    StaffController.error = {};
    StaffController.staffShiftsId=[];
    StaffController.datepickerOptions = {
        showWeeks: false
    };
    StaffController.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    StaffController.isInternalSearchSelected = false;
    StaffController.isExternalSearchSelected = false;
    StaffController.staffStatus = 'Pending';
    StaffController.customFields = [];
    StaffController.customFieldsArray = [];
    
    StaffController.user_identity_number = "";
    StaffController.isEnableBirthplaceArea = false;
    StaffController.isEnableAddressArea = false;
    StaffController.isIdentityUserExist = false;

    //controller function
    StaffController.getUniqueOpenEmisId = getUniqueOpenEmisId;
    StaffController.generatePassword = generatePassword;
    StaffController.changeGender = changeGender;
    StaffController.changeNationality = changeNationality;
    StaffController.changeIdentityType = changeIdentityType;
    StaffController.goToFirstStep = goToFirstStep;
    StaffController.goToNextStep = goToNextStep;
    StaffController.goToPrevStep = goToPrevStep;
    StaffController.confirmUser = confirmUser;
    StaffController.getGenders = getGenders;
    StaffController.getNationalities = getNationalities;
    StaffController.getIdentityTypes = getIdentityTypes;
    StaffController.setStaffName = setStaffName;
    StaffController.appendName = appendName;
    StaffController.initGrid = initGrid;
    StaffController.getPostionTypes = getPostionTypes;
    StaffController.getPositions = getPositions;
    StaffController.getStaffTypes = getStaffTypes;
    StaffController.getShifts = getShifts;
    StaffController.getFtes = getFtes;
    StaffController.changePositionType = changePositionType;
    StaffController.changePosition = changePosition;
    StaffController.changeStaffType = changeStaffType;
    StaffController.cancelProcess = cancelProcess;
    StaffController.changeFte = changeFte;
    StaffController.getInternalSearchData = getInternalSearchData;
    StaffController.getExternalSearchData = getExternalSearchData;
    StaffController.processInternalGridUserRecord = processInternalGridUserRecord;
    StaffController.processExternalGridUserRecord = processExternalGridUserRecord;
    StaffController.saveStaffDetails = saveStaffDetails;
    StaffController.validateDetails = validateDetails;
    StaffController.goToInternalSearch = goToInternalSearch;
    StaffController.goToExternalSearch = goToExternalSearch;
    StaffController.setstaffData = setstaffData;
    StaffController.setStaffDataFromExternalSearchData = setStaffDataFromExternalSearchData;
    StaffController.getStaffCustomFields = getStaffCustomFields;
    StaffController.createCustomFieldsArray = createCustomFieldsArray;
    StaffController.onDecimalNumberChange = onDecimalNumberChange;
    StaffController.changeOption = changeOption;
    StaffController.selectOption = selectOption;
    StaffController.filterBySection= filterBySection;
    StaffController.mapBySection= mapBySection;
    StaffController.transferStaffNextStep = transferStaffNextStep;
  
    
    $window.savePhoto = function(event) {
        let photo = event.files[0];
        StaffController.selectedStaffData.photo = photo;
        StaffController.selectedStaffData.photo_name = photo.name;
        let fileReader = new FileReader();
        fileReader.readAsDataURL(photo);
        fileReader.onload = () => {
            console.log(fileReader.result);
            StaffController.selectedStaffData.photo_base_64 = fileReader.result;
        }
    }

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        StaffController.initGrid();
        InstitutionsStaffSvc.init(angular.baseUrl);
        StaffController.institutionId = Number($window.localStorage.getItem("institution_id"));
        StaffController.translateFields = {
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
        StaffController.getGenders();
        StaffController.getPostionTypes();
    });

    function saveStaffDetails() {
        StaffController.selectedStaffData.addressArea = InstitutionsStaffSvc.getAddressArea();
        StaffController.selectedStaffData.birthplaceArea = InstitutionsStaffSvc.getBirthplaceArea();
        var params = {
            openemis_no: StaffController.selectedStaffData.openemis_no,
            first_name: StaffController.selectedStaffData.first_name,
            middle_name: StaffController.selectedStaffData.middle_name,
            third_name: StaffController.selectedStaffData.third_name,
            last_name: StaffController.selectedStaffData.last_name,
            preferred_name: StaffController.selectedStaffData.preferred_name,
            gender_id: StaffController.selectedStaffData.gender_id,
            date_of_birth: StaffController.selectedStaffData.date_of_birth,
            identity_number: StaffController.user_identity_number == "" ? StaffController.selectedStaffData.identity_number : StaffController.user_identity_number,
            nationality_id: StaffController.selectedStaffData.nationality_id,
            nationality_name: StaffController.selectedStaffData.nationality_name,
            username: StaffController.selectedStaffData.username,
            password: StaffController.isInternalSearchSelected ? '' : StaffController.selectedStaffData.password,
            postal_code: StaffController.selectedStaffData.postalCode,
            address: StaffController.selectedStaffData.address,
            birthplace_area_id: InstitutionsStaffSvc.getBirthplaceAreaId(),
            address_area_id: InstitutionsStaffSvc.getAddressAreaId(),
            identity_type_id: StaffController.selectedStaffData.identity_type_id,
            identity_type_name: StaffController.selectedStaffData.identity_type_name,
            start_date: StaffController.selectedStaffData.startDate,
            end_date: StaffController.selectedStaffData.endDate ? $filter('date')(StaffController.selectedStaffData.endDate, 'yyyy-MM-dd') : '',
            institution_position_id: StaffController.institutionPositionOptions.selectedOption ? StaffController.institutionPositionOptions.selectedOption.value : null,
            position_type_id: StaffController.selectedStaffData.position_type_id,
            staff_type_id: StaffController.selectedStaffData.staff_type_id,
            fte: StaffController.selectedStaffData.fte_id,
            shift_ids: StaffController.staffShiftsId,
            photo_name: StaffController.selectedStaffData.photo_name,
            photo_base_64: StaffController.selectedStaffData.photo_base_64,
            institution_id: StaffController.institutionId,
            is_same_school: StaffController.staffData && StaffController.staffData.is_same_school ? StaffController.staffData.is_same_school : 0,
            is_diff_school: StaffController.staffData && StaffController.staffData.is_diff_school ?  StaffController.staffData.is_diff_school : 0,
            staff_id: StaffController.staffData && StaffController.staffData.id ? StaffController.staffData.id : null,
            previous_institution_id: StaffController.staffData && StaffController.staffData.current_enrol_institution_id ? StaffController.staffData.current_enrol_institution_id : null,
            comment: StaffController.selectedStaffData.comment,
            custom: [],
        };
        StaffController.customFieldsArray.forEach((customField)=> {
            customField.data.forEach((field)=> {
                if(field.field_type !== 'CHECKBOX') {
                    let fieldData = {
                        staff_custom_field_id: field.staff_custom_field_id,
                        text_value:"",
                        number_value:null,
                        decimal_value:"",
                        textarea_value:"",
                        time_value:"",
                        date_value:"",
                        file:"",
                        institution_id: StaffController.institutionId,
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
                            staff_custom_field_id: field.staff_custom_field_id,
                            text_value:"",
                            number_value: Number(id),
                            decimal_value:"",
                            textarea_value:"",
                            time_value:"",
                            date_value:"",
                            file:"",
                            institution_id: StaffController.institutionId,
                        };
                        params.custom.push(fieldData);
                    });
                }
            })
        });
        UtilsSvc.isAppendLoader(true);

        if (StaffController.isExternalSearchSelected || StaffController.isInternalSearchSelected)
        {
            params = { ...params, identity_number: StaffController.user_identity_number }
            StaffController.selectedStaffData.identity_number = StaffController.user_identity_number;
        }
        InstitutionsStaffSvc.saveStaffDetails(params).then(function (resp)
        {
            StaffController.selectedStaffData.identity_number = resp.config.data.identity_number;

            UtilsSvc.isAppendLoader(false);
            if (
                StaffController.staffData
                && StaffController.staffData.current_enrol_institution_name != ""
                && StaffController.staffData.is_diff_school > 0)
            {
                StaffController.message = 'Staff transfer request is added successfully.';
                StaffController.messageClass = 'alert-success';
                $window.history.back();
            } else {
                StaffController.message = 'Staff is added successfully.';
                StaffController.messageClass = 'alert-success';
                StaffController.step = "summary";
                var todayDate = new Date();
                StaffController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
            }
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getUniqueOpenEmisId() {
        if(StaffController.selectedStaffData.openemis_no && !isNaN(Number(StaffController.selectedStaffData.openemis_no.toString()))) {
            StaffController.selectedStaffData.username = angular.copy(StaffController.selectedStaffData.openemis_no);
            return;
        }
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getUniqueOpenEmisId()
            .then(function(response) {
                StaffController.selectedStaffData.openemis_no = response;
                StaffController.selectedStaffData.username = angular.copy(StaffController.selectedStaffData.openemis_no);
                UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getInternalSearchData() {
        var first_name = '';
        var last_name = '';
        var openemis_no = null;
        var date_of_birth = '';
        var identity_number = '';

        var nationality_id = '';
        var nationality_name = ''; 
        var identity_type_name = '';
        var identity_type_id = '';

        first_name = StaffController.selectedStaffData.first_name;
        last_name = StaffController.selectedStaffData.last_name;
        date_of_birth = StaffController.selectedStaffData.date_of_birth;
        identity_number = StaffController.selectedStaffData.identity_number;
      
        nationality_id = StaffController.selectedStaffData.nationality_id;
        nationality_name = StaffController.selectedStaffData.nationality_name;
        identity_type_name = StaffController.selectedStaffData.identity_type_name;
        identity_type_id = StaffController.selectedStaffData.identity_type_id;

        var dataSource = {
            pageSize: StaffController.pageSize,
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
                    institution_id: StaffController.institutionId,
                    user_type_id: 2,
                    nationality_id: nationality_id,
                    nationality_name: nationality_name,
                    identity_type_name: identity_type_name,
                    identity_type_id: identity_type_id
                }
                InstitutionsStaffSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData=[];
                    var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                    return StaffController.processInternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StaffController.internalGridOptions.api.setDatasource(dataSource);
        StaffController.internalGridOptions.api.sizeColumnsToFit(); 
    }

    function processInternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = userRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function getExternalSearchData() {
        var param = {
            first_name: StaffController.selectedStaffData.first_name,
            last_name: StaffController.selectedStaffData.last_name,
            date_of_birth: StaffController.selectedStaffData.date_of_birth,
            identity_number: StaffController.selectedStaffData.identity_number,
        };
        var dataSource = {
            pageSize: StaffController.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                InstitutionsStaffSvc.getExternalSearchData(param)
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
                    return StaffController.processExternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        StaffController.externalGridOptions.api.setDatasource(dataSource);
        StaffController.externalGridOptions.api.sizeColumnsToFit(); 
    }

    function processExternalGridUserRecord(userRecords, params, totalRowCount) {
        console.log(userRecords);

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = userRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    function generatePassword() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.generatePassword()
        .then(function(response) {
            if (StaffController.selectedStaffData.password == '' || typeof StaffController.selectedStaffData.password == 'undefined') {
                StaffController.selectedStaffData.password = response;
            }
            StaffController.getPostionTypes();
        }, function(error) {
            console.log(error);
            StaffController.getPostionTypes();
        });
    }

    function getGenders(){
        InstitutionsStaffSvc.getGenders().then(function(resp){
            StaffController.genderOptions = resp;
            StaffController.getNationalities();
        }, function(error){
            console.log(error);
            StaffController.getNationalities();
        });
    }

    function getNationalities(){
        InstitutionsStaffSvc.getNationalities().then(function(resp){
            StaffController.nationalitiesOptions = resp.data;
            StaffController.getIdentityTypes();
        }, function(error){
            console.log(error);
            StaffController.getIdentityTypes();
        });
    }

    function getIdentityTypes(){
        InstitutionsStaffSvc.getIdentityTypes().then(function(resp){
            StaffController.identityTypeOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getPostionTypes(){
        InstitutionsStaffSvc.getPositionTypes().then(function(resp){
            StaffController.positionTypeOptions = resp.data;
            StaffController.getStaffTypes();
        }, function(error){
            console.log(error);
            StaffController.getStaffTypes();
        });
    }

    function getFtes(){
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getFtes().then(function(resp){
            StaffController.fteOptions = resp.data;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getPositions(){
        if(!StaffController.selectedStaffData.position_type_id || !StaffController.selectedStaffData.fte_id)
            return;
        UtilsSvc.isAppendLoader(true);
        var params = {
            institution_id: StaffController.institutionId,
            fte: StaffController.selectedStaffData.position_type_id === 'Full-Time' ? 1 : Number(StaffController.selectedStaffData.fte_id),
            startDate: StaffController.selectedStaffData.startDate ? $filter('date')(StaffController.selectedStaffData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
            endDate: StaffController.selectedStaffData.endDate ? $filter('date')(StaffController.selectedStaffData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
            openemis_no: StaffController.selectedStaffData.openemis_no,
        };
        InstitutionsStaffSvc.getPositions(params).then(function (resp)
        {
            resp.data = resp.data.filter((data) => data.disabled === false)
            StaffController.institutionPositionOptions.availableOptions = resp.data;
            StaffController.institutionPositionOptions.selectedOption = null;
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getStaffTypes(){
        InstitutionsStaffSvc.getStaffTypes().then(function(resp){
            StaffController.staffTypeOptions = resp.data;
            StaffController.getShifts();
        }, function(error){
            console.log(error);
            StaffController.getShifts();
        });
    }

    function getShifts(){
        InstitutionsStaffSvc.getShifts().then(function(resp){
            StaffController.shiftsOptions = resp.data;
            StaffController.getStaffCustomFields();
        }, function(error){
            console.log(error);
            StaffController.getStaffCustomFields();
        });
    }

    function getStaffCustomFields() {
        let staffId = StaffController.staffData && StaffController.staffData.id ? StaffController.staffData.id : null;
        InstitutionsStaffSvc.getStaffCustomFields(staffId).then(function(resp){
            StaffController.customFields = resp.data;
            StaffController.customFieldsArray = [];
            StaffController.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function mapBySection(item) {
        return item.section;
    }

    function filterBySection(item, section) {
        return section === item.section;
    }

    function createCustomFieldsArray() {
        var selectedCustomField = StaffController.customFields;
        var filteredSections = Array.from(new Set(StaffController.customFields.map((item)=> mapBySection(item))));
        filteredSections.forEach((section)=>{
            let filteredArray = selectedCustomField.filter((item) => StaffController.filterBySection(item, section));
            StaffController.customFieldsArray.push({sectionName: section , data: filteredArray});
        });
        StaffController.customFieldsArray.forEach((customField) => {
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
                    const splitDate = fieldData.values.split('-').map((d=> parseInt(d)));
                    fieldData.answer = fieldData.values === "" ? new Date() : new Date(splitDate[0], splitDate[1]-1, splitDate[2]) ;
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
    
    function onDecimalNumberChange(field) {
        let timer;
        if(timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(()=>{
            field.answer = parseFloat(field.answer.toFixed(field.params.precision));
        }, 3000);
    }

    function changeOption(field, optionId){
        field.option.forEach((option) => {
            if(option.option_id === optionId){
                field.selectedOption = option.option_name;
            }
        })
    }

    function selectOption (field) {
        field.answer = [];
        field.option.forEach((option) => {
            if(option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    function setStaffName() {
        var staffData = StaffController.selectedStaffData;
        staffData.name = '';

        if (staffData.hasOwnProperty('first_name')) {
            staffData.name = staffData.first_name.trim();
        }
        StaffController.appendName(staffData, 'middle_name', true);
        StaffController.appendName(staffData, 'third_name', true);
        StaffController.appendName(staffData, 'last_name', true);
        StaffController.selectedStaffData = staffData;
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

    function changeGender() {
        var userData = StaffController.selectedStaffData;
        if (userData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == userData.gender_id) {
                    userData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStaffData = userData;
        }
    }

    function changeNationality() {
        var nationalityId = StaffController.selectedStaffData.nationality_id;
        var options = StaffController.nationalitiesOptions;
        var identityOptions = StaffController.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                if (options[i].identity_type_id == null) {
                    StaffController.selectedStaffData.identity_type_id = identityOptions['0'].id;
                    StaffController.selectedStaffData.identity_type_name = identityOptions['0'].name;
                } else {
                    StaffController.selectedStaffData.identity_type_id = options[i].identity_type_id;
                    StaffController.selectedStaffData.identity_type_name = options[i].identity_type.name;
                }
                StaffController.selectedStaffData.nationality_name = options[i].name;
                break;
            }
        }
    }

    function changeIdentityType() {
        var identityType = StaffController.selectedStaffData.identity_type_id;
        var identityTypeOptions = StaffController.identityTypeOptions;
        for (var i = 0; i < identityTypeOptions.length; i++) {
            if (identityTypeOptions[i].id == identityType) {
                StaffController.selectedStaffData.identity_type_name = identityTypeOptions[i].name;
                break;
            }
        }
    }

    function changePositionType() {
        StaffController.selectedStaffData.fte_id = null;
        var positionType = StaffController.selectedStaffData.position_type_id;
        var positionTypeOptions = StaffController.positionTypeOptions;
        for (var i = 0; i < positionTypeOptions.length; i++) {
            if (positionTypeOptions[i].id == positionType) {
                StaffController.selectedStaffData.position_type_name = positionTypeOptions[i].name;
                break;
            }
        }
        if(positionType === 'Full-Time'){
            StaffController.selectedStaffData.fte_id = 1;
            StaffController.selectedStaffData.fte_name = '100%';
            StaffController.getPositions();
        }
        else{
            StaffController.getFtes();
        }
    }

    function changePosition() {
        var position = StaffController.selectedStaffData.position_id;
        var positionOptions = StaffController.institutionPositionOptions;
        for (var i = 0; i < positionOptions.length; i++) {
            if (positionOptions[i].id == position) {
                StaffController.selectedStaffData.position_name = positionOptions[i].name;
                break;
            }
        }
    }

    function changeStaffType() {
        var staffType = StaffController.selectedStaffData.staff_type_id;
        var staffTypeOptions = StaffController.staffTypeOptions;
        for (var i = 0; i < staffTypeOptions.length; i++) {
            if (staffTypeOptions[i].id == staffType) {
                StaffController.selectedStaffData.staff_type_name = staffTypeOptions[i].name;
                break;
            }
        }
    }

    function changeFte() {
        StaffController.institutionPositionOptions.selectedOption = null;
        var fte = StaffController.selectedStaffData.fte_id;
        var fteOptions = StaffController.fteOptions;
        for (var i = 0; i < fteOptions.length; i++) {
            if (fteOptions[i].id == fte) {
                StaffController.selectedStaffData.fte_name = fteOptions[i].name;
                break;
            }
        }
        StaffController.getPositions();
    }

    function goToInternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StaffController.getInternalSearchData();
            }, 1500);
        }, function(error){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StaffController.getInternalSearchData();
            }, 1500);
        });
    }

    function goToExternalSearch(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StaffController.getExternalSearchData();
            }, 1500);
        }, function(error){
            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
            setTimeout(function(){
                StaffController.getExternalSearchData();
            }, 1500);
        });
    }
    
    function goToPrevStep(){
        if(StaffController.isInternalSearchSelected) {
            StaffController.step = 'internal_search';
            StaffController.internalGridOptions = null;
            StaffController.goToInternalSearch();
        } else if(StaffController.isExternalSearchSelected) {
            StaffController.step = 'external_search';
            StaffController.externalGridOptions = null;
            StaffController.goToExternalSearch();
        } else {
            switch(StaffController.step){
                case 'internal_search': 
                    StaffController.selectedStaffData.date_of_birth = InstitutionsStaffSvc.formatDate(StaffController.selectedStaffData.date_of_birth);
                    StaffController.step = 'user_details';
                    break;
                case 'external_search': 
                    StaffController.step = 'internal_search';
                    StaffController.internalGridOptions = null;
                    StaffController.goToInternalSearch();
                    break;
                case 'confirmation': 
                    StaffController.step = 'external_search';
                    StaffController.externalGridOptions = null;
                    StaffController.goToExternalSearch();
                    break;
                case 'add_staff': 
                    StaffController.step = 'confirmation';
                    break;
            }
        }
    }

    function validateDetails() {
        StaffController.error = {};
        if(StaffController.step === 'user_details') {
            if(!StaffController.selectedStaffData.first_name){
                StaffController.error.first_name = 'This field cannot be left empty';
            }
            if(!StaffController.selectedStaffData.last_name){
                StaffController.error.last_name = 'This field cannot be left empty';
            }
            if(!StaffController.selectedStaffData.gender_id){
                StaffController.error.gender_id = 'This field cannot be left empty';
            }
            if(!StaffController.selectedStaffData.date_of_birth) {
                StaffController.error.date_of_birth = 'This field cannot be left empty';
            } else {
                StaffController.selectedStaffData.date_of_birth = $filter('date')(StaffController.selectedStaffData.date_of_birth, 'yyyy-MM-dd');
            }
    
            if(!StaffController.selectedStaffData.first_name || !StaffController.selectedStaffData.last_name || !StaffController.selectedStaffData.gender_id || !StaffController.selectedStaffData.date_of_birth){
                return;
            }
            StaffController.step = 'internal_search';
            StaffController.internalGridOptions = null;
            StaffController.goToInternalSearch();
        }
        if(StaffController.step === 'add_staff') {
            let shouldPositionRequired = false;
            let isCustomFieldNotValidated = false;
            if(!StaffController.selectedStaffData.startDate) {
            StaffController.error.start_date = 'This field cannot be left empty';
            } else {
                StaffController.selectedStaffData.startDate = $filter('date')(StaffController.selectedStaffData.startDate, 'yyyy-MM-dd');
            }
            if(!StaffController.selectedStaffData.position_type_id){
                StaffController.error.position_type_id = 'This field cannot be left empty';
            }
            if(StaffController.selectedStaffData.fte_id === 'Part-Time' && !StaffController.selectedStaffData.position_type_id){
                StaffController.error.fte_id = 'This field cannot be left empty';
            }
            if(!StaffController.selectedStaffData.staff_type_id){
                StaffController.error.staff_type_id = 'This field cannot be left empty';
            }
            if(StaffController.staffShiftsId.length === 0){
                StaffController.error.staffShiftsId = 'This field cannot be left empty';
            }
            StaffController.institutionPositionOptions.availableOptions.forEach((option)=>{
                if(!option.disabled){
                    shouldPositionRequired = true;
                }
            });
            if(shouldPositionRequired && !StaffController.institutionPositionOptions.selectedOption) {
                StaffController.error.position_id = 'This field cannot be left empty';
            }
            StaffController.customFieldsArray.forEach((customField) => {
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
            if(!StaffController.selectedStaffData.startDate || !StaffController.selectedStaffData.position_type_id || !StaffController.selectedStaffData.staff_type_id || !StaffController.staffShiftsId.length === 0 || StaffController.error.fte_id || StaffController.error.position_id || isCustomFieldNotValidated){
                return;
            } 
            if (
                StaffController.staffData
                && StaffController.staffData.current_enrol_institution_name != ""
                && StaffController.staffData.is_diff_school > 0)
            { 
                StaffController.step = 'transfer_staff';
                StaffController.messageClass = 'alert-warning';
                StaffController.message = `Staff is currently assigned to ${StaffController.staffData.currentlyAssignedTo}`
            } else
            {
                
                StaffController.saveStaffDetails();
            }
        }
    }

    async function goToNextStep()
    {
        /* Here check the user identity number is already exist or not  - PENDING*/
       
      
        if (StaffController.isInternalSearchSelected)
        {
           
            if (StaffController.staffData && StaffController.staffData.is_diff_school)
            {
                StaffController.messageClass = 'alert-warning';
                StaffController.message = `This staff is already allocated to ${StaffController.staffData.current_enrol_institution_code} - ${StaffController.staffData.current_enrol_institution_name}`;
                StaffController.step = 'summary';
                StaffController.isInternalSearchSelected = false;
                StaffController.generatePassword();
            } else
            {
                StaffController.step = 'confirmation';
                StaffController.isInternalSearchSelected = false;
                StaffController.generatePassword();
            }
        } else if(StaffController.isExternalSearchSelected) {
            StaffController.step = 'confirmation';
            StaffController.generatePassword();
            StaffController.isExternalSearchSelected = false;
        } else {
            switch(StaffController.step){
                case 'user_details': 
                    await checkUserAlreadyExistByIdentity();
                    StaffController.validateDetails();
                    break;
                case 'internal_search': 
                    StaffController.step = 'external_search';
                    StaffController.externalGridOptions = null;
                    StaffController.goToExternalSearch();
                    break;
                case 'external_search': 
                    StaffController.step = 'confirmation';
                    StaffController.getUniqueOpenEmisId();
                    break;
                case 'confirmation': 
                    StaffController.step = 'add_staff';
                    console.log(StaffController.selectedStaffData)
                    StaffController.generatePassword();
                    break;
            }
        }
    }

    function confirmUser() {
        StaffController.message = (StaffController.selectedStaffData && StaffController.selectedStaffData.userType ? StaffController.selectedStaffData.userType.name : 'Student') + ' successfully added.';
        StaffController.messageClass = 'alert-success';
        StaffController.step = "summary";
    }

    function goToFirstStep() {
        StaffController.step = 'user_details';
        StaffController.selectedStaffData = {};
    }

    function cancelProcess() {
        $window.history.back();
    }

    StaffController.selectStaffFromInternalSearch = function (id)
    {
        StaffController.selectedUser = id;
        StaffController.isInternalSearchSelected = true;
        StaffController.isExternalSearchSelected = false;
        StaffController.selectedStaffData.identity_number = StaffController.user_identity_number;
        StaffController.getStaffData();
        StaffController.getStaffCustomFields();

        if (StaffController.isIdentityUserExist)
        {
            StaffController.messageClass = '';
            StaffController.message = '';
            StaffController.isIdentityUserExist = false;
        }
    }

    StaffController.selectStaffFromExternalSearch = function(id) {
        StaffController.selectedUser = id;
        StaffController.isInternalSearchSelected = false;
        StaffController.isExternalSearchSelected = true;
        StaffController.selectedStaffData.identity_number = StaffController.user_identity_number;
        StaffController.getStaffData();
        StaffController.getStaffCustomFields();
    }

    StaffController.getStaffData = function() {
        var log = [];
        
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedUser) {
                StaffController.staffData = value;
                if(StaffController.isInternalSearchSelected) {
                    StaffController.staffStatus = 'Assigned';
            
                    // POCOR-5672 : fixed showing wrong institution name
                    StaffController.staffData.currentlyAssignedTo = value.current_enrol_institution_code + ' - ' + value.current_enrol_institution_name;
                    StaffController.staffData.requestedBy = value.institution_code + ' - ' + value.institution_name;
            
                    StaffController.setstaffData(value);
                }
                if(StaffController.isExternalSearchSelected) {
                    StaffController.setStaffDataFromExternalSearchData(value);
                }
            }
        }, log);
    }

    function setstaffData(selectedData)
    {
        const deepCopy = { ...selectedData };
        StaffController.selectedStaffData.addressArea = {
            id: deepCopy.address_area_id,
            name: deepCopy.area_name,
            code: deepCopy.area_code
        };
        StaffController.selectedStaffData.birthplaceArea = {
            id: deepCopy.birthplace_area_id,
            name: deepCopy.birth_area_name,
            code: deepCopy.birth_area_code
        };
        StaffController.selectedStaffData.openemis_no = selectedData.openemis_no;
        StaffController.selectedStaffData.first_name = selectedData.first_name;
        StaffController.selectedStaffData.middle_name = selectedData.middle_name;
        StaffController.selectedStaffData.third_name = selectedData.third_name;
        StaffController.selectedStaffData.last_name = selectedData.last_name;
        StaffController.selectedStaffData.preferred_name = selectedData.preferred_name;
        StaffController.selectedStaffData.gender = {
            name: selectedData.gender
        };
        StaffController.selectedStaffData.date_of_birth = selectedData.date_of_birth;
        StaffController.selectedStaffData.email = selectedData.email;
        StaffController.selectedStaffData.identity_type_name = selectedData.identity_type;
        StaffController.selectedStaffData.identity_number = selectedData.identity_number;
        StaffController.selectedStaffData.nationality_name = selectedData.nationality;
        StaffController.selectedStaffData.address = selectedData.address;
        StaffController.selectedStaffData.postalCode = selectedData.postal_code;
        StaffController.selectedStaffData.addressArea.name = selectedData.area_name;
        StaffController.selectedStaffData.birthplaceArea.name = selectedData.birth_area_name;
        StaffController.selectedStaffData.currentlyAssignedTo = selectedData.current_enrol_institution_code + ' - ' + selectedData.current_enrol_institution_name;
        StaffController.selectedStaffData.requestedBy = selectedData.institution_code + ' - ' + selectedData.institution_name;
        StaffController.selectedStaffData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        StaffController.user_identity_number = deepCopy.identity_number;

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

    function setStaffDataFromExternalSearchData(selectedData)
    {
        const deepCopy = { ...selectedData };
        StaffController.selectedStaffData.addressArea = {
            id: deepCopy.address_area_id,
            name: deepCopy.area_name,
            code: deepCopy.area_code
        };
        StaffController.selectedStaffData.birthplaceArea = {
            id: deepCopy.birthplace_area_id,
            name: deepCopy.birth_area_name,
            code: deepCopy.birth_area_code
        };
        StaffController.selectedStaffData.openemis_no = selectedData.openemis_no;
        StaffController.selectedStaffData.first_name = selectedData.first_name;
        StaffController.selectedStaffData.middle_name = selectedData.middle_name;
        StaffController.selectedStaffData.third_name = selectedData.third_name;
        StaffController.selectedStaffData.last_name = selectedData.last_name;
        StaffController.selectedStaffData.preferred_name = selectedData.preferred_name;
        StaffController.selectedStaffData.gender_id = selectedData.gender_id;
        StaffController.selectedStaffData.gender = {
            name: selectedData.gender
        };
        StaffController.selectedStaffData.date_of_birth = selectedData.date_of_birth;
        StaffController.selectedStaffData.email = selectedData.email;
        StaffController.selectedStaffData.identity_type_id = deepCopy.identity_type_id;
        StaffController.selectedStaffData.identity_type_name = deepCopy.identity_type;
        StaffController.selectedStaffData.identity_number = deepCopy.identity_number;
        StaffController.selectedStaffData.nationality_id = selectedData.nationality_id;
        StaffController.selectedStaffData.nationality_name = selectedData.nationality;
        StaffController.selectedStaffData.address = selectedData.address;
        StaffController.selectedStaffData.postalCode = selectedData.postal_code;
        StaffController.selectedStaffData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        StaffController.user_identity_number = deepCopy.identity_number;

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

    function initGrid() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };

            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        }, function(error){
            StaffController.internalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };

            StaffController.externalGridOptions = {
                columnDefs: [
                    {headerName: StaffController.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    StaffController.selectStaffFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        });
    };

    function reloadInternalDatasource(withData) {
        if (withData !== false) {
           StaffController.showExternalSearchButton = true;
        }
        InstitutionsStaffSvc.resetExternalVariable();
        delete StaffController.selectedStaff;
        StaffController.staffTypeId = '';
        StaffController.positionType = '';
        StaffController.endDate = '';
        StaffController.onChangeAcademicPeriod();
        StaffController.onChangePositionType();
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions, withData);
    };

    function reloadExternalDatasource(withData) {
        InstitutionsStaffSvc.resetExternalVariable();
        delete StaffController.selectedStaff;
        StaffController.staffTypeId = '';
        StaffController.positionType = '';
        StaffController.endDate = '';
        StaffController.onChangeAcademicPeriod();
        StaffController.onChangePositionType();
        StaffController.createNewExternalDatasource(StaffController.externalGridOptions, withData);
    };

    function clearInternalSearchFilters() {
        StaffController.internalFilterOpenemisNo = '';
        StaffController.internalFilterFirstName = '';
        StaffController.internalFilterLastName = '';
        StaffController.internalFilterIdentityNumber = '';
        StaffController.internalFilterDateOfBirth = '';
        StaffController.initialLoad = true;
        StaffController.createNewInternalDatasource(StaffController.internalGridOptions);
    }

    function createNewInternalDatasource(gridObj, withData) {
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                // AlertSvc.reset($scope); // POCOR-4009 commented out due to alert class not appear (only white text message appeared) when there is an empty field.
                if (withData) {
                   InstitutionsStaffSvc.getStaffRecords(
                    {
                        startRow: params.startRow,
                        endRow: params.endRow,
                        conditions: {
                            openemis_no: StaffController.internalFilterOpenemisNo,
                            first_name: StaffController.internalFilterFirstName,
                            last_name: StaffController.internalFilterLastName,
                            identity_number: StaffController.internalFilterIdentityNumber,
                            date_of_birth: StaffController.internalFilterDateOfBirth,
                        }
                    }
                    )
                    .then(function(response) {
                        if (response.conditionsCount == 0) {
                            StaffController.initialLoad = true;
                        } else {
                            StaffController.initialLoad = false;
                        }
                        var staffRecords = response.data;
                        var totalRowCount = response.total;
                        return StaffController.processStaffRecord(staffRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        AlertSvc.warning($scope, error);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function createNewExternalDatasource(gridObj, withData) {
        StaffController.externalDataLoaded = false;
        StaffController.initialLoad = true;
        var dataSource = {
            pageSize: pageSize,
            getRows: function (params) {
                AlertSvc.reset($scope);
                if (withData) {
                    InstitutionsStaffSvc.getExternalStaffRecords(
                        {
                            startRow: params.startRow,
                            endRow: params.endRow,
                            conditions: {
                                first_name: StaffController.internalFilterFirstName,
                                last_name: StaffController.internalFilterLastName,
                                identity_number: StaffController.internalFilterIdentityNumber,
                                date_of_birth: StaffController.internalFilterDateOfBirth
                            }
                        }
                    )
                    .then(function(response) {
                        var staffRecords = response.data;
                        var totalRowCount = response.total;
                        StaffController.initialLoad = false;
                        return StaffController.processExternalStaffRecord(staffRecords, params, totalRowCount);
                    }, function(error) {
                        console.log(error);
                        var status = error.status;
                        if (status == '401') {
                            var message = 'You have not been authorised to fetch from external data source.';
                            AlertSvc.warning($scope, message);
                        } else {
                            var message = 'External search failed, please contact your administrator to verify the external search attributes';
                            AlertSvc.warning($scope, message);
                        }
                        var staffRecords = [];
                        InstitutionsStaffSvc.init(angular.baseUrl);
                        return StaffController.processExternalStaffRecord(staffRecords, params, 0);
                    })
                    .finally(function(res) {
                        InstitutionsStaffSvc.init(angular.baseUrl);
                    });
                } else {
                    StaffController.rowsThisPage = [];
                    params.successCallback(StaffController.rowsThisPage, 0);
                    return [];
                }
            }
        };
        gridObj.api.setDatasource(dataSource);
        gridObj.api.sizeColumnsToFit();
    }

    function processExternalStaffRecord(staffRecords, params, totalRowCount) {
        for(var key in staffRecords) {
            var mapping = InstitutionsStaffSvc.getExternalSourceMapping();
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            staffRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(staffRecords[key][mapping.date_of_birth_mapping]);
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
            StaffController.appendName(staffRecords[key], mapping.middle_name_mapping);
            StaffController.appendName(staffRecords[key], mapping.third_name_mapping);
            StaffController.appendName(staffRecords[key], mapping.last_name_mapping);
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = staffRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function processStaffRecord(staffRecords, params, totalRowCount) {
        console.log(staffRecords);
        for(var key in staffRecords) {
            staffRecords[key]['institution_name'] = '-';
            staffRecords[key]['academic_period_name'] = '-';
            staffRecords[key]['education_grade_name'] = '-';
            if ((staffRecords[key].hasOwnProperty('institution_students') && staffRecords[key]['institution_students'].length > 0)) {
                staffRecords[key]['institution_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('institution')))? staffRecords[key].institution_students['0'].institution.name: '-';
                staffRecords[key]['academic_period_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('academic_period')))? staffRecords[key].institution_students['0'].academic_period.name: '-';
                staffRecords[key]['education_grade_name'] = ((staffRecords[key].institution_students['0'].hasOwnProperty('education_grade')))? staffRecords[key].institution_students['0'].education_grade.name: '-';
            }

            if (staffRecords[key]['main_nationality'] != null) {
                staffRecords[key]['nationality_name'] = staffRecords[key]['main_nationality']['name'];
            }
            if (staffRecords[key]['main_identity_type'] != null) {
                staffRecords[key]['identity_type_name'] = staffRecords[key]['main_identity_type']['name'];
            }

            staffRecords[key]['date_of_birth'] = InstitutionsStaffSvc.formatDate(staffRecords[key]['date_of_birth']);
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
                StaffController.appendName(staffRecords[key], 'middle_name');
                StaffController.appendName(staffRecords[key], 'third_name');
                StaffController.appendName(staffRecords[key], 'last_name');
            }
        }

        var lastRow = totalRowCount;
        StaffController.rowsThisPage = staffRecords;

        params.successCallback(StaffController.rowsThisPage, lastRow);
        StaffController.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return staffRecords;
    }

    function insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, userRecord, shiftId={}) {
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
            staff_type_id: staffTypeId,
            FTE: fte,
            start_date: startDate,
            end_date: endDate
        };
         var shiftData = {
                staff_id: staffId,
                shift_id: shiftId,
            };
       
        console.log("data",data);
        console.log("shiftData",shiftData);
        
        var deferred = $q.defer();

        InstitutionsStaffSvc.postAssignedStaff(data)
        .then(function(postResponse) {
            StaffController.postResponse = postResponse.data;
            UtilsSvc.isAppendLoader(false);
            StaffController.addStaffError = false;
            StaffController.transferStaffError = false;
            var log = [];
            var counter = 0;
            angular.forEach(postResponse.data.error , function(value) {
                counter++;
            }, log);
            
             if (counter == 0) {
           
                InstitutionsStaffSvc.postAssignedStaffShift(shiftData);
                AlertSvc.success($scope, 'The staff is added successfully.');
                $window.location.href = 'add?staff_added=true';
                deferred.resolve(StaffController.postResponse);
            }
            else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleReleaseRequestExists')) {
                AlertSvc.warning($scope, 'There is an existing release record for this staff.');
                $window.location.href = postResponse.data.error.staff_assignment.ruleReleaseRequestExists;
                deferred.resolve(StaffController.postResponse);
            }
            else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleTransferRequestExists')) {
                AlertSvc.warning($scope, 'There is an existing transfer record for this staff.');
                $window.location.href = postResponse.data.error.staff_assignment.ruleTransferRequestExists;
                deferred.resolve(StaffController.postResponse);
            } else if (counter == 1 && postResponse.data.error.hasOwnProperty('staff_assignment') && postResponse.data.error.staff_assignment.hasOwnProperty('ruleCheckStaffAssignment')) {
                InstitutionsStaffSvc.getStaffData(staffId, startDate, endDate)
                .then(function(response) {
                    StaffController.selectedStaff = response.id;
                    StaffController.selectedStaffData['institution_staff'] = response.institution_staff;
                    var idName = StaffController.selectedStaffData.openemis_no + ' - ' + StaffController.selectedStaffData.name;
                    var institutionName = StaffController.selectedStaffData['institution_staff'][0]['institution']['code_name'];
                    var currentInstitutionType = StaffController.selectedStaffData['institution_staff'][0]['institution']['institution_type_id'];
                    var currentInstitutionProvider = StaffController.selectedStaffData['institution_staff'][0]['institution']['institution_provider_id'];
                    var newInstitutionType = StaffController.institutionType;
                    var newInstitutionProvider = StaffController.institutionProvider;
                    var restrictStaffTransferByTypeConfig = StaffController.restrictStaffTransferByTypeValue[0]['value'];
                    var restrictStaffTransferByProviderConfig = StaffController.restrictStaffTransferByProviderValue[0]['value'];

                    if (restrictStaffTransferByTypeConfig == 1 && currentInstitutionType != newInstitutionType) {
                        StaffController.addStaffError = true;
                        AlertSvc.warning($scope, idName + ' is currently assigned to '+ institutionName +'. Staff transfer between different type is restricted.');
                    } else if (restrictStaffTransferByProviderConfig == 1 && currentInstitutionProvider != newInstitutionProvider) {
                        StaffController.addStaffError = true;
                        AlertSvc.warning($scope, idName + ' is currently assigned to '+ institutionName +'. Staff transfer between different provider is restricted.');
                    } else {
                        StaffController.transferStaffError = true;
                        AlertSvc.info($scope, idName + ' is currently assigned to '+ institutionName +'. By clicking save, a transfer request will be sent to the institution for approval');
                    }
                    deferred.resolve(StaffController.postResponse);
                }, function(error) {
                    StaffController.transferStaffError = true;
                    AlertSvc.warning($scope, 'Staff is currently assigned to another Institution.');
                    deferred.resolve(StaffController.postResponse);
                });

            } else {
                StaffController.addStaffError = true;
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                deferred.resolve(StaffController.postResponse);
            }

        }, function(error) {
            console.log(error);
            AlertSvc.warning($scope, error);
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function onAddNewStaffClick() {
        StaffController.createNewStaff = true;
        StaffController.completeDisabled = false;
        StaffController.selectedStaffData = {};
        StaffController.selectedStaffData.first_name = '';
        StaffController.selectedStaffData.last_name = '';
        StaffController.selectedStaffData.date_of_birth = '';
        StaffController.initNationality();
        StaffController.initIdentityType();
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
        StaffController.postForm().then(function(response) {
            if (StaffController.addStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "addStaff"
                });
            } else if (StaffController.transferStaffError) {
                angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
                    step: "transferStaff"
                });
            }
        }, function(error) {
            console.log(errors);
            // error handling here
        });
    }

    function onExternalSearchClick() {
        angular.element(document.querySelector('#wizard')).wizard('selectedItem', {
            step: "externalSearch"
        });
    }

    function selectStaff(id) {
        StaffController.selectedStaff = id;
        StaffController.getStaffData();
    }

    function setStaffName() {
        var staffData = StaffController.selectedStaffData;
        staffData.name = '';

        if (staffData.hasOwnProperty('first_name')) {
            staffData.name = staffData.first_name.trim();
        }
        StaffController.appendName(staffData, 'middle_name', true);
        StaffController.appendName(staffData, 'third_name', true);
        StaffController.appendName(staffData, 'last_name', true);
        StaffController.selectedStaffData = staffData;
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
        var staffData = StaffController.selectedStaffData;
        if (staffData.hasOwnProperty('gender_id')) {
            var genderOptions = StaffController.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == staffData.gender_id) {
                    staffData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            StaffController.selectedStaffData = staffData;
        }
    }

    function getStaffData() {
        var log = [];
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedStaff) {
                StaffController.selectedStaffData = value;
            }
        }, log);
    }

    function onChangeAcademicPeriod() {
        AlertSvc.reset($scope);

        if (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption')) {
            // StaffController.startDate = InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date);
        }

        var startDatePicker = angular.element(document.getElementById('Staff_start_date'));
        startDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        startDatePicker.datepicker("setEndDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.end_date));
        // startDatePicker.datepicker("setDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        var endDatePicker = angular.element(document.getElementById('Staff_end_date'));
        endDatePicker.datepicker("setStartDate", InstitutionsStaffSvc.formatDate(StaffController.academicPeriodOptions.selectedOption.start_date));
        StaffController.onChangeFTE();
    }

    function postTransferForm() {
        var startDate = StaffController.startDate;
        var startDateArr = startDate.split("-");
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var positionType = StaffController.positionType;
        var institutionPositionId = (StaffController.institutionPositionOptions.hasOwnProperty('selectedOption') && StaffController.institutionPositionOptions.selectedOption != null) ? StaffController.institutionPositionOptions.selectedOption.value: '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = StaffController.fte;
        var staffTypeId = (StaffController.staffTypeId != null && StaffController.staffTypeId.hasOwnProperty('id')) ? StaffController.staffTypeId.id : '';
        var data = {
            staff_id: StaffController.selectedStaff,
            new_start_date: startDate,
            new_end_date: StaffController.endDate,
            new_staff_type_id: staffTypeId,
            new_FTE: fte,
            new_institution_position_id: institutionPositionId,
            status_id: 0,
            assignee_id: -1,
            new_institution_id: StaffController.institutionId,
            previous_institution_id: StaffController.selectedStaffData.institution_staff[0]['institution']['id'], //POCOR-6909
            // previous_institution_id: StaffController.selectedStaffData['id'],//POCOR-6704
            comment: StaffController.comment
        };

        InstitutionsStaffSvc.addStaffTransferRequest(data)
        .then(function(response) {
            var data = response.data;
            if (data.error.length == 0) {
                AlertSvc.success($scope, 'Staff transfer request is added successfully.');
                $window.location.href = 'add?staff_transfer_added=true';
            } else if (data.error.hasOwnProperty('staff_assignment') && data.error.staff_assignment.hasOwnProperty('ruleTransferRequestExists')) {
                AlertSvc.warning($scope, 'There is an existing transfer record for this staff.');
                $window.location.href = data.error.staff_assignment.ruleTransferRequestExists;
            } else {
                console.log(response);
                AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
            }
        }, function(error) {
            console.log(error);
            AlertSvc.error($scope, 'There is an error in adding staff transfer request.');
        })
    }

    function postForm() {
        var deferred = $q.defer();
        console.log("StaffController"+StaffController);
        var academicPeriodId = (StaffController.academicPeriodOptions.hasOwnProperty('selectedOption'))? StaffController.academicPeriodOptions.selectedOption.id: '';
        var positionType = StaffController.positionType;
        var institutionPositionId = (StaffController.institutionPositionOptions.hasOwnProperty('selectedOption') && StaffController.institutionPositionOptions.selectedOption != null) ? StaffController.institutionPositionOptions.selectedOption.value: '';
        institutionPositionId = (institutionPositionId == undefined) ? '' : institutionPositionId;
        var fte = StaffController.fte;
        var staffTypeId = (StaffController.staffTypeId != null && StaffController.staffTypeId.hasOwnProperty('id')) ? StaffController.staffTypeId.id : '';
        var startDate = StaffController.startDate;
        var startDateArr = startDate.split("-");
        var shiftId = StaffController.staffShiftsId;
        startDate = startDateArr[2] + '-' + startDateArr[1] + '-' + startDateArr[0];
        for(i = 0; i < startDateArr.length; i++) {
            if (startDateArr[i] == undefined || startDateArr[i] == null || startDateArr[i] == '') {
                startDate = undefined;
            }
        }
        var endDate = StaffController.endDate;

        if (!StaffController.createNewStaff) {
            if (StaffController.externalSearch) {
                var staffData = StaffController.selectedStaffData;
                var amendedStaffData = Object.assign({}, staffData);
                amendedStaffData.date_of_birth = InstitutionsStaffSvc.formatDate(amendedStaffData.date_of_birth);
                //POCOR-6576 - added shiftId parameter as shiftId) was missing ealier
                return StaffController.addStaffUser(amendedStaffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId);
            } else {
                var staffId = StaffController.selectedStaff;
                return StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, {}, shiftId);
            }
        } else {
            if (StaffController.selectedStaffData != null) {
                var staffData = {};
                var log = [];
                angular.forEach(StaffController.selectedStaffData, function(value, key) {
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
                return StaffController.addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId);
            }
        }
    }

    function addStaffUser(staffData, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, shiftId) {
        var deferred = $q.defer();
        var newStaffData = staffData;
        
        newStaffData['academic_period_id'] = academicPeriodId;
        newStaffData['start_date'] = startDate;
        if (!StaffController.externalSearch) {
            newStaffData['nationality_id'] = StaffController.Staff.nationality_id;
            newStaffData['identity_type_id'] = StaffController.Staff.identity_type_id;
        }
        newStaffData['position_type'] = positionType;
        newStaffData['institution_position_id'] = institutionPositionId;
        newStaffData['staff_type_id'] = staffTypeId;
        newStaffData['FTE'] = fte;
        newStaffData['staff_shifts_id'] = shiftId;
        InstitutionsStaffSvc.addUser(newStaffData)
        .then(function(user){
            if (user[0].error.length === 0) {
                var staffId = user[0].data.id;
                deferred.resolve(StaffController.insertStaffData(staffId, academicPeriodId, institutionPositionId, positionType, fte, staffTypeId, startDate, endDate, user[1], shiftId));
            } else {
                StaffController.postResponse = user[0];
                AlertSvc.error($scope, 'The record is not added due to errors encountered.');
                deferred.resolve(StaffController.postResponse);
            }
        }, function(error){
            console.log(error);
            deferred.reject(error);
            AlertSvc.warning($scope, error);
        });

        return deferred.promise;
    }


    angular.element(document.querySelector('#wizard')).on('actionclicked.fu.wizard', function(evt, data) {
        // evt.preventDefault();
        AlertSvc.reset($scope);

        if (angular.isDefined(StaffController.postResponse)){
            delete StaffController.postResponse;
            $scope.$apply();
        }
        // To go to add student page if there is a student selected from the internal search
        // or external search
        if (data.step == 3 && data.direction == 'next') {
            if (StaffController.validateNewUser()) {
                evt.preventDefault();
            };
        }
    });

    function validateNewUser() {
        var remain = false;
        var empty = {'_empty': 'This field cannot be left empty'};
        StaffController.postResponse = {};
        StaffController.postResponse.error = {};
        if (StaffController.selectedStaffData.first_name == '') {
            StaffController.postResponse.error.first_name = empty;
            remain = true;
        }

        if (StaffController.selectedStaffData.last_name == '') {
            StaffController.postResponse.error.last_name = empty;
            remain = true;
        }
        if (StaffController.selectedStaffData.gender_id == '' || StaffController.selectedStaffData.gender_id == null) {
            StaffController.postResponse.error.gender_id = empty;
            remain = true;
        }

        if (StaffController.selectedStaffData.date_of_birth == '') {
            StaffController.postResponse.error.date_of_birth = empty;
            remain = true;
        }

        if (StaffController.StaffNationalities == 1 && (StaffController.Staff.nationality_id == '' || StaffController.Staff.nationality_id == undefined)) {
            remain = true;
        }

        if (StaffController.selectedStaffData.username == '' || StaffController.selectedStaffData.username == undefined) {
            StaffController.postResponse.error.username = empty;
            remain = true;
        }

        if (StaffController.selectedStaffData.password == '' || StaffController.selectedStaffData.password == undefined) {
            StaffController.postResponse.error.password = empty;
            remain = true;
        }

        var arrNumber = [{}];

        if (StaffController.StaffIdentities == 1 && (StaffController.selectedStaffData.identity_number == '' || StaffController.selectedStaffData.identity_number == undefined)) {
            arrNumber[0]['number'] = empty;
            StaffController.postResponse.error.identities = arrNumber;
            remain = true;
        }

        var arrNationality = [{}];
        if (StaffController.StaffNationalities == 1 && (StaffController.Staff.nationality_id == '' || StaffController.Staff.nationality_id == undefined)) {
            arrNationality[0]['nationality_id'] = empty;
            StaffController.postResponse.error.nationalities = arrNationality;
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

    function getUniqueOpenEmisId() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getUniqueOpenEmisId()
            .then(function(response) {
                var username = StaffController.selectedStaffData.username;
                //POCOR-5878 starts
                if(username != StaffController.selectedStaffData.openemis_no && (username == '' || typeof username == 'undefined')){
                    StaffController.selectedStaffData.username = StaffController.selectedStaffData.openemis_no;
                    StaffController.selectedStaffData.openemis_no = StaffController.selectedStaffData.openemis_no;
                }else{
                    if(username == StaffController.selectedStaffData.openemis_no){
                        StaffController.selectedStaffData.username = response;
                    }
                    StaffController.selectedStaffData.openemis_no = response;
                }
                //POCOR-5878 ends
                UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function generatePassword() {
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.generatePassword()
        .then(function(response) {
            if (StaffController.selectedStaffData.password == '' || typeof StaffController.selectedStaffData.password == 'undefined') {
                StaffController.selectedStaffData.password = response;
            }
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    angular.element(document.querySelector('#wizard')).on('finished.fu.wizard', function(evt, data) {
        //return; 
        // The last complete step is now transfer staff, add transfer staff logic function call here
        StaffController.postTransferForm();
    });

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        StaffController.addStaffButton = false;
        // Step 1 - Internal search
        if (data.step == 1) {
            StaffController.Staff.identity_type_name = StaffController.defaultIdentityTypeName;
            StaffController.Staff.identity_type_id = StaffController.defaultIdentityTypeId;
            delete StaffController.postResponse;
            StaffController.reloadInternalDatasource(true);
            StaffController.createNewStaff = false;
            StaffController.externalSearch = false;
            StaffController.step = 'internal_search';
        }
        // Step 2 - External search
        else if (data.step == 2) {
            StaffController.Staff.identity_type_name = StaffController.externalIdentityType;
            StaffController.Staff.identity_type_id = StaffController.defaultIdentityTypeId;
            delete StaffController.postResponse;
            StaffController.reloadExternalDatasource(true);
            StaffController.createNewStaff = false;
            StaffController.externalSearch = true;
            StaffController.step = 'external_search';
        }
        // Step 3 - Create user
        else if (data.step == 3) {
            StaffController.externalSearch = false;
            StaffController.createNewStaff = true;
            StaffController.step = 'create_user';
            StaffController.getUniqueOpenEmisId();
            StaffController.generatePassword();
            InstitutionsStaffSvc.resetExternalVariable();
        }
        // Step 4 - Add Staff
        else if (data.step == 4) {
            if (StaffController.externalSearch) {
                StaffController.getUniqueOpenEmisId();
            }
            // Work around for alert reset
            StaffController.createNewInternalDatasource(StaffController.internalGridOptions, true);
            StaffController.step = 'add_staff';
        }
        // Step 5 - Transfer Staff
        else if (data.step == 5) {
            StaffController.step = 'transfer_staff';
        }
    });

    function transferStaffNextStep()
    {
        StaffController.step = 'transfer_staff';
    }

    async function checkUserAlreadyExistByIdentity()
    {
        const result = await InstitutionsStaffSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': StaffController.selectedStaffData.identity_type_id,
            'identity_number': StaffController.selectedStaffData.identity_number,
            'nationality_id': StaffController.selectedStaffData.nationality_id
        });
        if (result.data.user_exist===1)
        {
            StaffController.messageClass = 'alert-warning';
            StaffController.message = result.data.message;
            StaffController.isIdentityUserExist = true;
        } else
        {
            StaffController.messageClass = '';
            StaffController.message = '';
            StaffController.isIdentityUserExist = false;
        }
       /*  return result.data.user_exist === 1; */
    }
}
