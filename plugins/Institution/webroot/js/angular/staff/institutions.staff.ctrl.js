angular
    .module('institutions.staff.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'institutions.staff.svc', 'angular.chosen', 'kd-angular-tree-dropdown'])
    .controller('InstitutionsStaffCtrl', InstitutionStaffController);

InstitutionStaffController.$inject = ['$location', '$q', '$scope', '$window', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'InstitutionsStaffSvc', '$rootScope'];

function InstitutionStaffController($location, $q, $scope, $window, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, InstitutionsStaffSvc, $rootScope) {
    // ag-grid vars


    var StaffController = this;

    StaffController.pageSize = 10;
    StaffController.step = 'user_details';
    StaffController.selectedStaffData = {};
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
        minDate: new Date(),
        maxDate: new Date('01/01/2100'),
        showWeeks: false
    };
    StaffController.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };

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
            identity_number: StaffController.selectedStaffData.identity_number,
            nationality_id: StaffController.selectedStaffData.nationality_id,
            username: StaffController.selectedStaffData.username,
            password: StaffController.selectedStaffData.password,
            postal_code: StaffController.selectedStaffData.postalCode,
            address: StaffController.selectedStaffData.address,
            birthplace_area_id: InstitutionsStaffSvc.getBirthplaceAreaId(),
            address_area_id: InstitutionsStaffSvc.getAddressAreaId(),
            identity_type_id: StaffController.selectedStaffData.identity_type_id,
            start_date: StaffController.selectedStaffData.startDate,
            end_date: StaffController.selectedStaffData.endDate ? $filter('date')(StaffController.selectedStaffData.endDate, 'yyyy-MM-dd') : '',
            institution_position_id: 1,
            position_type_id: StaffController.selectedStaffData.position_type_id,
            staff_type_id: StaffController.selectedStaffData.staff_type_id,
            fte: StaffController.selectedStaffData.fte_id,
            shift_ids: StaffController.staffShiftsId,
        };
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.saveStaffDetails(params).then(function(resp){
            StaffController.message = 'Staff successfully added.';
            StaffController.messageClass = 'alert-success';
            StaffController.step = "summary";
            var todayDate = new Date();
            StaffController.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    function getUniqueOpenEmisId() {
        if(StaffController.selectedStaffData.openemis_no)
            return;
        UtilsSvc.isAppendLoader(true);
        InstitutionsStaffSvc.getUniqueOpenEmisId()
            .then(function(response) {
                StaffController.selectedStaffData.openemis_no = response;
                StaffController.selectedStaffData.username = response;
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
        first_name = StaffController.selectedStaffData.first_name;
        last_name = StaffController.selectedStaffData.last_name;
        date_of_birth = StaffController.selectedStaffData.date_of_birth;
        identity_number = StaffController.selectedStaffData.identity_number;
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
                }
                InstitutionsStaffSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData=[];
                    var totalRowCount = response.data.total;
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
                    var totalRowCount = response.data.total;
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
        UtilsSvc.isAppendLoader(true);
        var params = {
            institution_id: StaffController.institutionId,
            fte: StaffController.selectedStaffData.position_type_id === 'Full-Time' ? 1 : StaffController.selectedStaffData.fte_id,
            startDate: StaffController.selectedStaffData.startDate ? $filter('date')(StaffController.selectedStaffData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
            endDate: StaffController.selectedStaffData.endDate ? $filter('date')(StaffController.selectedStaffData.startDate, 'yyyy-MM-dd') : $filter('date')(new Date(), 'yyyy-MM-dd'),
        }
        InstitutionsStaffSvc.getPositions(params).then(function(resp){
            StaffController.institutionPositionOptions.availableOptions = resp.data;
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
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStudent(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.c(_e.node.data.id);
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
        switch(StaffController.step){
            case 'internal_search': 
                StaffController.selectedStaffData.date_of_birth = new Date(StaffController.selectedStaffData.date_of_birth);
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

    function validateDetails() {
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
            if(!StaffController.selectedStaffData.startDate || !StaffController.selectedStaffData.position_type_id || !StaffController.selectedStaffData.staff_type_id || !StaffController.staffShiftsId.length === 0 || StaffController.error.fte_id){
                return;
            }
            StaffController.saveStaffDetails();
        }
    }

    function goToNextStep() {
        switch(StaffController.step){
            case 'user_details': 
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
                StaffController.generatePassword();
                break;
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

    StaffController.selectStaff = function(id) {
        StaffController.selectedUser = id;
        StaffController.getStaffData();
    }

    StaffController.getStaffData = function() {
        var log = [];
        angular.forEach(StaffController.rowsThisPage , function(value) {
            if (value.id == StaffController.selectedUser) {
                StaffController.selectedStaffData = value;
            }
        }, log);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
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
                    {headerName: StaffController.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: StaffController.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
            };
        });
    };
    
}
