angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc', 'kd-angular-tree-dropdown'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', '$filter', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, $filter, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
    var scope = $scope;

    scope.step = "user_details";
    scope.selectedUserData = {};
    scope.internalGridOptions = null;
    scope.externalGridOptions = null;
    scope.postRespone = null;
    scope.translateFields = null;
    scope.genderOptions = [];
    scope.userTypeOptions = [];
    scope.nationality_class = 'input select error';
    scope.identity_type_class = 'input select error';
    scope.identity_class = 'input string';
    scope.messageClass = '';
    scope.message = '';
    scope.nationalitiesOptions = [];
    scope.identityTypeOptions = [];
    scope.contactTypeOptions = [];
    scope.addressAreaOption = [];
    scope.birthplaceAreaOption = [];
    scope.isGuardianAdding = false;
    scope.pageSize = 10;
    scope.rowsThisPage = [];
    scope.selectedUser;
    scope.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    scope.error = {};
    scope.customFields = [];
    scope.customFieldsArray = [];
    var todayDate = new Date();
    scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
    scope.redirectToGuardian = false;
    scope.isInternalSearchSelected = false;
    scope.isExternalSearchSelected = false;
    scope.datepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    scope.addressAreaId = null;
    scope.birthplaceAreaId = null;
    scope.isIdentityUserExist = false;
    scope.isExternalSearchEnable = false;
    scope.externalSearchSourceName = '';
    scope.isSearchResultEmpty = false; 

    scope.disableFields = {
        username: false,
        password: false
    }

    $window.savePhoto = function(event) {
        let photo = event.files[0];
        scope.selectedUserData.photo = photo;
        scope.selectedUserData.photo_name = photo.name;
        let fileReader = new FileReader();
        fileReader.readAsDataURL(photo);
        fileReader.onload = () => {
            console.log(fileReader.result);
            scope.selectedUserData.photo_base_64 = fileReader.result;
        }
    }

    angular.element(document).ready(function () {
        UtilsSvc.isAppendLoader(true);
        console.log(angular.baseUrl);
        DirectoryaddSvc.init(angular.baseUrl);
        scope.translateFields = {
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
        scope.initGrid();
        scope.getUserTypes();
    });

    scope.getUniqueOpenEmisId = function() {
        UtilsSvc.isAppendLoader(true);
        if((scope.isExternalSearchSelected || scope.isInternalSearchSelected) && scope.selectedUserData.openemis_no && !isNaN(Number(scope.selectedUserData.openemis_no.toString()))){
            scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
            scope.generatePassword();
            return;
        }
        DirectoryaddSvc.getUniqueOpenEmisId()
            .then(function(response) {
                scope.selectedUserData.openemis_no = response;
                scope.selectedUserData.username = angular.copy(scope.selectedUserData.openemis_no);
                scope.generatePassword();
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getInternalSearchData = function() {
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
                    institution_id: null,
                    user_type_id: scope.selectedUserData.user_type_id,
                    nationality_id: nationality_id,
                    nationality_name: nationality_name,
                    identity_type_name: identity_type_name,
                    identity_type_id: identity_type_id
                };
                DirectoryaddSvc.getInternalSearchData(param)
                .then(function(response) {
                    var gridData = response.data.data;
                    if(!gridData)
                        gridData = [];
                    var totalRowCount = response.data.total === 0 ? 1 : response.data.total;
                    scope.isSearchResultEmpty = gridData.length === 0;  
                    return scope.processInternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.internalGridOptions.api.setDatasource(dataSource);
        scope.internalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.processInternalGridUserRecord = function(userRecords, params, totalRowCount) {
        console.log(userRecords);
        if (userRecords.length === 0)
        {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }
        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.getExternalSearchData = function() {
        var param = {
            first_name: scope.selectedUserData.first_name,
            last_name: scope.selectedUserData.last_name,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            openemis_no:scope.selectedUserData.openemis_no
        }
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                DirectoryaddSvc.getExternalSearchData(param)
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
                    scope.isSearchResultEmpty = gridData.length === 0;  
                    return scope.processExternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.processExternalGridUserRecord = function(userRecords, params, totalRowCount) {
        console.log(userRecords);
        if (userRecords.length === 0)
        {
            params.failCallback([], totalRowCount);
            UtilsSvc.isAppendLoader(false);
            return;
        }

        var lastRow = totalRowCount;
        scope.rowsThisPage = userRecords;

        params.successCallback(scope.rowsThisPage, lastRow);
        // scope.externalDataLoaded = true;
        UtilsSvc.isAppendLoader(false);
        return userRecords;
    }

    scope.generatePassword = function() {
        DirectoryaddSvc.generatePassword()
        .then(function(response) {
            if (scope.selectedUserData.password == '' || typeof scope.selectedUserData.password == 'undefined') {
                scope.selectedUserData.password = response;
            }
            scope.getContactTypes();
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getUserTypes = function() {
        DirectoryaddSvc.getUserTypes()
        .then(function(response) {
            scope.userTypeOptions = response.data;
            scope.getGenders();
        }, function(error) {
            console.log(error);
            scope.getGenders();
        });
    }

    scope.getGenders = function() {
        DirectoryaddSvc.getGenders()
        .then(function(response) {
            scope.genderOptions = response.data;
            scope.getNationalities();
        }, function(error) {
            console.log(error);
            scope.getNationalities();
        });
    }

    scope.getNationalities = function() {
        DirectoryaddSvc.getNationalities()
        .then(function(response) {
            scope.nationalitiesOptions = response.data;
            scope.getIdentityTypes();

        }, function(error) {
            console.log(error);
            scope.getIdentityTypes();
        });
    }

    scope.getIdentityTypes = function() {
        DirectoryaddSvc.getIdentityTypes()
        .then(function(response) {
            scope.identityTypeOptions = response.data;
            scope.checkConfigForExternalSearch()
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getContactTypes = function() {
        DirectoryaddSvc.getContactTypes()
        .then(function(response) {
            scope.contactTypeOptions = response.data;
            if(scope.selectedUserData.userType.name === 'Students') {
                scope.getStudentCustomFields();
            } else if(scope.selectedUserData.userType.name === 'Staff') {
                scope.getStaffCustomFields();
            } else {
                UtilsSvc.isAppendLoader(false);
            }
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getRedirectToGuardian = function(){
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.getRedirectToGuardian()
        .then(function(resp) {
            scope.redirectToGuardian = resp.data[0].redirecttoguardian_status;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.setName = function() {
        var userData = scope.selectedUserData;
        userData.name = '';
        if (userData.hasOwnProperty('first_name')) {
            userData.name = userData.first_name.trim();
        }
        scope.appendName(userData, 'middle_name', true);
        scope.appendName(userData, 'third_name', true);
        scope.appendName(userData, 'last_name', true);
        scope.selectedUserData = userData;
    }

    scope.appendName = function(dataObj, variableName, trim) {
        if (dataObj.hasOwnProperty(variableName)) {
            if (trim === true) {
                dataObj[variableName] = dataObj[variableName].trim();
            }
            if (dataObj[variableName] != null && dataObj[variableName] != '') {
                dataObj.name = dataObj.name + ' ' + dataObj[variableName];
            }
        }
        return dataObj;
    }

    scope.changeGender = function() {
        var userData = scope.selectedUserData;
        if (userData.hasOwnProperty('gender_id')) {
            var genderOptions = scope.genderOptions;
            for(var i = 0; i < genderOptions.length; i++) {
                if (genderOptions[i].id == userData.gender_id) {
                    userData.gender = {
                        name: genderOptions[i].name
                    };
                }
            }
            scope.selectedUserData = userData;
        }
    }

    scope.changeUserType = function() {
        var userData = scope.selectedUserData;
        if (userData.hasOwnProperty('user_type_id')) {
            var userTypeOptions = scope.userTypeOptions;
            for(var i = 0; i < userTypeOptions.length; i++) {
                if (userTypeOptions[i].id == userData.user_type_id) {
                    userData.userType = {
                        name: userTypeOptions[i].name
                    };
                }
            }
            scope.selectedUserData = userData;
        }
    }

    scope.changeNationality =  function() {
        var nationalityId = scope.selectedUserData.nationality_id;
        if (nationalityId === null)
        {
            scope.selectedUserData.nationality_name = "";
        }
        var options = scope.nationalitiesOptions;
        var identityOptions = scope.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == nationalityId) {
                if (options[i].identity_type_id == null) {
                    scope.selectedUserData.identity_type_id = identityOptions['0'].id;
                    scope.selectedUserData.identity_type_name = identityOptions['0'].name;
                } else {
                    scope.selectedUserData.identity_type_id = options[i].identity_type_id;
                    scope.selectedUserData.identity_type_name = options[i].identity_type.name;
                }
                scope.selectedUserData.nationality_name = options[i].name;
                break;
            }
        }
    }

    scope.changeIdentityType =  function() {
        var identityType = scope.selectedUserData.identity_type_id;
        if (identityType == null)
        {
            scope.selectedUserData.identity_type_id = '';
            scope.selectedUserData.identity_number = '';
            scope.selectedUserData.identity_type_name = '';
        }
        var options = scope.identityTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == identityType) {
                scope.selectedUserData.identity_type_name = options[i].name;
                break;
            }
        }
    }

    scope.changeContactType =  function() {
        var contactType = scope.selectedUserData.contact_type_id;
        var options = scope.contactTypeOptions;
        for (var i = 0; i < options.length; i++) {
            if (options[i].id == contactType) {
                scope.selectedUserData.contact_type_name = options[i].name;
                break;
            }
        }
    }

    scope.goToInternalSearch = function(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=true;
                    scope.isExternalSearchSelected=false;
                    scope.selectUserFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.internalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
            setTimeout(function(){
                scope.getInternalSearchData();
            }, 1500);
        }, function(error){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=true;
                    scope.isExternalSearchSelected=false;
                    scope.selectUserFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.internalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
            setTimeout(function(){
                scope.getInternalSearchData();
            }, 1500);
        });
    }

    scope.goToExternalSearch = function(){
        UtilsSvc.isAppendLoader(true);
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=false;
                    scope.isExternalSearchSelected=true;
                    scope.selectUserFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
            setTimeout(function(){
                // scope.getExternalSearchData();
                if (scope.externalSearchSourceName === 'Jordan CSPD'){
                    scope.getCSPDSearchData();
                }else{
                    scope.getExternalSearchData();
                }
            }, 1500);
        }, function(error){
            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=false;
                    scope.isExternalSearchSelected=true;
                    scope.selectUserFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
            setTimeout(function(){
                if (scope.externalSearchSourceName === 'Jordan CSPD'){
                    scope.getCSPDSearchData();
                }else{
                    scope.getExternalSearchData();
                }
            }, 1500);
        });
    }

    scope.validateDetails = async function ()
    {
        scope.error = {};
        if (!scope.selectedUserData.user_type_id)
        {
            scope.error.user_type_id = 'This field cannot be left empty';
            return;
        }
        if(scope.step === 'user_details') {
            const [blockName, hasError] = checkUserDetailValidationBlocksHasError();

            if(blockName==='Identity' && hasError){
                if (!scope.selectedUserData.nationality_id)
                {
                    scope.error.nationality_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.identity_type_id)
                {
                    scope.error.identity_type_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.identity_number)
                {
                    scope.error.identity_number = 'This field cannot be left empty';
                }
            }else if (blockName === 'General_Info' && hasError)
            {
                if (!scope.selectedUserData.user_type_id)
                {
                    scope.error.user_type_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.first_name)
                {
                    scope.error.first_name = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.last_name)
                {
                    scope.error.last_name = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.gender_id)
                {
                    scope.error.gender_id = 'This field cannot be left empty';
                }
                if (!scope.selectedUserData.date_of_birth)
                {
                    scope.error.date_of_birth = 'This field cannot be left empty';
                } else
                {
                    scope.selectedUserData.date_of_birth = $filter('date')(scope.selectedUserData.date_of_birth, 'yyyy-MM-dd');
                }
            }
            if (hasError) return;
            scope.step = 'internal_search';
            scope.internalGridOptions = null;
            scope.goToInternalSearch();
            await checkUserAlreadyExistByIdentity();
        }
        if(scope.step === 'confirmation') {
            let isCustomFieldNotValidated = false;
            if(!scope.selectedUserData.username){
                scope.error.username = 'This field cannot be left empty';
            }
            if(!scope.selectedUserData.password){
                scope.error.password = 'This field cannot be left empty';
            }
            scope.customFieldsArray.forEach((customField) => {
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
            if(!scope.selectedUserData.username || !scope.selectedUserData.password || isCustomFieldNotValidated){
                return;
            }
            scope.saveDetails();
        }
    }

    scope.goToPrevStep = function(){
        if(scope.isInternalSearchSelected) {
            scope.isInternalSearchSelected=false;
            scope.step = 'user_details';
            scope.internalGridOptions = null;
            // scope.goToInternalSearch();
        } else {
            switch(scope.step){
                case 'internal_search': {
                    scope.step = 'user_details';
                    if (scope.isSearchResultEmpty) {
                        scope.selectedUserData.openemis_no = "";
                    }
                    break;
                }
                case 'external_search': 
                    scope.step = 'internal_search';
                    scope.internalGridOptions = null;
                    scope.goToInternalSearch();
                    break;
                case 'confirmation': {
                    if (scope.isExternalSearchEnable)
                    {
                        scope.step = 'external_search';
                        scope.externalGridOptions = null;
                        scope.goToExternalSearch();
                     }
                    else
                    {
                        scope.step = 'internal_search';
                        scope.internalGridOptions = null;
                        scope.goToInternalSearch();
                    }
                    return;
                }
            }
        }
    }

    scope.goToNextStep = async function() {
        debugger;
        if(scope.step === 'confirmation'){
            const result = await scope.checkUserExistByIdentityFromConfiguration();
            if(result)return;
         }
        if(scope.isInternalSearchSelected) {
            scope.step = 'confirmation';
            scope.getUniqueOpenEmisId();
        } else {
            switch(scope.step){
                case 'user_details': 
                    scope.internalGridOptions = null;
                    scope.validateDetails();
                    break;
                case 'internal_search': {
                    if (scope.isExternalSearchEnable)
                    {
                        scope.step = 'external_search';
                        scope.externalGridOptions = null;
                        UtilsSvc.isAppendLoader(true);
                        scope.goToExternalSearch();
                    } else
                    {
                        scope.step = 'confirmation';
                        scope.getUniqueOpenEmisId();
                    }
                    return;
                }
                case 'external_search': 
                    scope.step = 'confirmation';
                    scope.getUniqueOpenEmisId();
                    break;
            }
        }
    }

    scope.confirmUser = async function () {
        if(scope.step === 'confirmation'){
            const result = await scope.checkUserExistByIdentityFromConfiguration();
            if(result)return;
         }
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        scope.step = "summary";
        var todayDate = new Date();
        scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
        if(scope.selectedUserData.userType.name === 'Students')
            scope.getRedirectToGuardian();
    }

    scope.goToFirstStep = function () {
        scope.step = 'user_details';
        scope.selectedUserData = {};
    }

    scope.cancelProcess = function() {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
    }
    
    scope.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.account_type, field: "account_type", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=true;
                    scope.isExternalSearchSelected=false;
                    scope.selectUserFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.internalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isExternalSearchSelected=true;
                    scope.isInternalSearchSelected=false;
                    scope.selectUserFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
        }, function(error){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isExternalSearchSelected=false;
                    scope.isInternalSearchSelected=true;
                    scope.selectUserFromInternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.internalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                    scope.isInternalSearchSelected=false;
                    scope.isExternalSearchSelected=true;
                    scope.selectUserFromExternalSearch(_e.node.data.id);
                    $scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                    if (angular.isDefined(scope.externalGridOptions.api)) {
                        setTimeout(function() {
                            scope.setGridData();
                        })
                    }
                },
            };
        });
    };

    scope.selectUserFromInternalSearch = function(id) {
        scope.selectedUser = id;
        scope.isInternalSearchSelected = true;
        scope.getUserData();

        if (scope.isIdentityUserExist)
        {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
        }

        scope.disableFields = {
            username: true,
            password: true
        }
    }

    scope.selectUserFromExternalSearch = function(id) {
        scope.selectedUser = id;
        scope.isInternalSearchSelected = false;
        scope.getUserData();
        scope.disableFields = {
            username: false,
            password: false
        }
    }

    scope.setUserData = function (selectedData)
    {
        scope.selectedUserData.addressArea = {
            id: selectedData.address_area_id,
            name: selectedData.area_name,
            code: selectedData.area_code
        };
        scope.selectedUserData.birthplaceArea = {
            id: selectedData.birthplace_area_id,
            name: selectedData.birth_area_name,
            code: selectedData.birth_area_code
        };
        scope.selectedUserData.openemis_no = selectedData.openemis_no;
        scope.selectedUserData.first_name = selectedData.first_name;
        scope.selectedUserData.middle_name = selectedData.middle_name;
        scope.selectedUserData.third_name = selectedData.third_name;
        scope.selectedUserData.last_name = selectedData.last_name;
        scope.selectedUserData.preferred_name = selectedData.preferred_name;
        scope.selectedUserData.date_of_birth = selectedData.date_of_birth;
        scope.selectedUserData.email = selectedData.email;
        scope.selectedUserData.gender_id = selectedData.gender_id;
        scope.selectedUserData.gender = {name: selectedData.gender};
        scope.selectedUserData.nationality_id = selectedData.nationality_id;
        scope.selectedUserData.nationality_name = selectedData.nationality;
        scope.selectedUserData.identity_type_id = selectedData.identity_type_id;
        scope.selectedUserData.identity_type_name = selectedData.identity_type;
        scope.selectedUserData.identity_number = selectedData.identity_number;
        scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
        scope.selectedUserData.password = selectedData.password;
        scope.selectedUserData.address = selectedData.address;
        scope.selectedUserData.postalCode = selectedData.postal_code;
        scope.selectedUserData.address_area_id = selectedData.address_area_id;
        scope.selectedUserData.birthplace_area_id = selectedData.birthplace_area_id;
        scope.selectedUserData.addressArea = {name: selectedData.area_name};
        scope.selectedUserData.birthplaceArea = {name: selectedData.birth_area_name};
        scope.selectedUserData.userId = selectedData.id;
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
        $window.localStorage.setItem('address_area', JSON.stringify({id: selectedData.address_area_id, name: selectedData.area_name}));
        scope.addressAreaId = selectedData.address_area_id;
        scope.birthplaceAreaId = selectedData.birthplace_area_id;

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

    scope.setExternalUserData = function (selectedData)
    {
        if (scope.externalSearchSourceName = 'Jordan CSPD')
        {
            DirectoryaddSvc.getUniqueOpenEmisId().then((response) =>
            {
                const selectedObjectWithOpenemisNo =  Object.assign({}, selectedData, {'openemis_no':response})
                selectedData = selectedObjectWithOpenemisNo;
                scope.selectedUserData.addressArea = {
                    id: selectedData.address_area_id,
                    name: selectedData.area_name,
                    code: selectedData.area_code
                };
                scope.selectedUserData.birthplaceArea = {
                    id: selectedData.birthplace_area_id,
                    name: selectedData.birth_area_name,
                    code: selectedData.birth_area_code
                };
                scope.selectedUserData.openemis_no = selectedData.openemis_no;
                scope.selectedUserData.first_name = selectedData.first_name;
                scope.selectedUserData.middle_name = selectedData.middle_name;
                scope.selectedUserData.third_name = selectedData.third_name;
                scope.selectedUserData.last_name = selectedData.last_name;
                scope.selectedUserData.preferred_name = selectedData.preferred_name;
                scope.selectedUserData.date_of_birth = selectedData.date_of_birth;
                scope.selectedUserData.email = selectedData.email;
                scope.selectedUserData.gender_id = selectedData.gender_id;
                scope.selectedUserData.gender = { name: selectedData.gender };
                scope.selectedUserData.nationality_id = selectedData.nationality_id;
                scope.selectedUserData.nationality_name = selectedData.nationality;
                scope.selectedUserData.identity_type_id = selectedData.identity_type_id;
                scope.selectedUserData.identity_type_name = selectedData.identity_type;
                scope.selectedUserData.identity_number = selectedData.identity_number;
                scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
                scope.selectedUserData.password = selectedData.password;
                scope.selectedUserData.address = selectedData.address;
                scope.selectedUserData.postalCode = selectedData.postal_code;

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
            scope.selectedUserData.addressArea = {
                id: selectedData.address_area_id,
                name: selectedData.area_name,
                code: selectedData.area_code
            };
            scope.selectedUserData.birthplaceArea = {
                id: selectedData.birthplace_area_id,
                name: selectedData.birth_area_name,
                code: selectedData.birth_area_code
            };
            scope.selectedUserData.openemis_no = selectedData.openemis_no;
            scope.selectedUserData.first_name = selectedData.first_name;
            scope.selectedUserData.middle_name = selectedData.middle_name;
            scope.selectedUserData.third_name = selectedData.third_name;
            scope.selectedUserData.last_name = selectedData.last_name;
            scope.selectedUserData.preferred_name = selectedData.preferred_name;
            scope.selectedUserData.date_of_birth = selectedData.date_of_birth;
            scope.selectedUserData.email = selectedData.email;
            scope.selectedUserData.gender_id = selectedData.gender_id;
            scope.selectedUserData.gender = {name: selectedData.gender};
            scope.selectedUserData.nationality_id = selectedData.nationality_id;
            scope.selectedUserData.nationality_name = selectedData.nationality;
            scope.selectedUserData.identity_type_id = selectedData.identity_type_id;
            scope.selectedUserData.identity_type_name = selectedData.identity_type;
            scope.selectedUserData.identity_number = selectedData.identity_number;
            scope.selectedUserData.username = selectedData.username ? selectedData.username : angular.copy(selectedData.openemis_no);
            scope.selectedUserData.password = selectedData.password;
            scope.selectedUserData.address = selectedData.address;
            scope.selectedUserData.postalCode = selectedData.postal_code;
    
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

    scope.getUserData = function() {
        var log = [];
        angular.forEach(scope.rowsThisPage , function(value) {
            if (value.id == scope.selectedUser) {
                if(scope.isInternalSearchSelected)
                    scope.setUserData(value);
                else
                    scope.setExternalUserData(value);
            }
        }, log);
    }

    scope.addGuardian = function(){
        if($window.localStorage.getItem('studentOpenEmisId')) {
            $window.localStorage.removeItem('studentOpenEmisId');
        }
        $window.localStorage.setItem('studentOpenEmisId', scope.selectedUserData.openemis_no);
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian';
    }
    
    scope.getStudentCustomFields = function() {
        let userId = scope.selectedUserData.userId ? scope.selectedUserData.userId : null;
        DirectoryaddSvc.getStudentCustomFields(userId).then(function(resp){
            scope.customFields = resp.data;
            scope.customFieldsArray = [];
            scope.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.getStaffCustomFields = function() {
        let userId = scope.selectedUserData.userId ? scope.selectedUserData.userId : null;
        DirectoryaddSvc.getStaffCustomFields(userId).then(function(resp){
            scope.customFields = resp.data;
            scope.customFieldsArray = [];
            scope.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.createCustomFieldsArray = function() {
        if (scope.customFields === "null") return;
        if(scope.customFields && scope.customFields.length > 0) {
            var selectedCustomField = scope.customFields;
            var filteredSections = Array.from(new Set(scope.customFields.map((item)=> scope.mapBySection(item))));
            filteredSections.forEach((section)=>{
                let filteredArray = selectedCustomField.filter((item) => scope.filterBySection(item, section));
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
        
    }

    scope.mapBySection = function(item) {
        return item.section;
    }

    scope.filterBySection = function(item, section) {
        return section === item.section;
    }

    scope.changeOption = function(field, optionId){
        field.option.forEach((option) => {
            if(option.option_id === optionId){
                field.selectedOption = option.option_name;
            }
        })
    }

    scope.changed = function(answer){
        console.log(answer);
    }

    scope.selectOption = function (field) {
        field.answer = [];
        field.option.forEach((option) => {
            if(option.selected) {
                field.answer.push(option.option_id);
            }
        })
    }

    scope.onDecimalNumberChange = function(field) {
        let timer;
        if(timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(()=>{
            field.answer = parseFloat(field.answer.toFixed(field.params.precision));
        }, 3000);
    }

    scope.saveDetails = function() {
        const addressAreaRef = DirectoryaddSvc.getAddressArea();
        addressAreaRef && (scope.selectedUserData.addressArea = addressAreaRef);
        const birthplaceAreaRef = DirectoryaddSvc.getBirthplaceArea();
        birthplaceAreaRef && (scope.selectedUserData.birthplaceArea = birthplaceAreaRef);
        let param = {
            user_type: scope.selectedUserData.user_type_id,
            openemis_no: scope.selectedUserData.openemis_no,
            first_name: scope.selectedUserData.first_name,
            middle_name: scope.selectedUserData.middle_name,
            third_name: scope.selectedUserData.third_name,
            last_name: scope.selectedUserData.last_name,
            preferred_name: scope.selectedUserData.preferred_name,
            gender_id: scope.selectedUserData.gender_id,
            date_of_birth: scope.selectedUserData.date_of_birth,
            identity_number: scope.selectedUserData.identity_number,
            identity_type_id: scope.selectedUserData.identity_type_id,
            identity_type_name: scope.selectedUserData.identity_type_name,
            nationality_id: scope.selectedUserData.nationality_id,
            nationality_name: scope.selectedUserData.nationality_name,
            username: scope.selectedUserData.username,
            password: scope.selectedUserData.password,
            postal_code: scope.selectedUserData.postalCode,
            address: scope.selectedUserData.address,
            birthplace_area_id: DirectoryaddSvc.getBirthplaceAreaId(),
            address_area_id: DirectoryaddSvc.getAddressAreaId(),
            contact_type: scope.selectedUserData.contact_type_id,
            contact_value: scope.selectedUserData.contactValue,
            photo_name: scope.selectedUserData.photo_name,
            photo_content: scope.selectedUserData.photo_base_64,
            custom: [],
        };
        if(scope.selectedUserData.userType.name === 'Students') {
            scope.customFieldsArray.forEach((customField)=> {
                customField.data.forEach((field)=> {
                    if(field.field_type !== 'CHECKBOX') {
                        let fieldData = {
                            custom_field_id: field.student_custom_field_id,
                            text_value:"",
                            number_value:null,
                            decimal_value:"",
                            textarea_value:"",
                            time_value:"",
                            date_value:"",
                            file:"",
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
                        param.custom.push(fieldData);
                    } else {
                        field.answer.forEach((id )=> {
                            let fieldData = {
                                custom_field_id: field.student_custom_field_id,
                                text_value:"",
                                number_value: Number(id),
                                decimal_value:"",
                                textarea_value:"",
                                time_value:"",
                                date_value:"",
                                file:"",
                            };
                            param.custom.push(fieldData);
                        });
                    }
                })
            });
        }
        if(scope.selectedUserData.userType.name === 'Staff') {
            scope.customFieldsArray.forEach((customField)=> {
                customField.data.forEach((field)=> {
                    if(field.field_type !== 'CHECKBOX') {
                        let fieldData = {
                            custom_field_id: field.staff_custom_field_id,
                            text_value:"",
                            number_value:null,
                            decimal_value:"",
                            textarea_value:"",
                            time_value:"",
                            date_value:"",
                            file:"",
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
                        param.custom.push(fieldData);
                    } else {
                        field.answer.forEach((id )=> {
                            let fieldData = {
                                custom_field_id: field.staff_custom_field_id,
                                text_value:"",
                                number_value: Number(id),
                                decimal_value:"",
                                textarea_value:"",
                                time_value:"",
                                date_value:"",
                                file:"",
                            };
                            param.custom.push(fieldData);
                        });
                    }
                })
            });
        }
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.saveDirectoryData(param).then(function(resp){
            scope.confirmUser();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    async function checkUserAlreadyExistByIdentity()
    {
        const result = await DirectoryaddSvc.checkUserAlreadyExistByIdentity({
            'identity_type_id': scope.selectedUserData.identity_type_id,
            'identity_number': scope.selectedUserData.identity_number,
            'nationality_id': scope.selectedUserData.nationality_id
        });
        if (result.data.user_exist === 1)
        {
            scope.messageClass = 'alert_warn';
            scope.message = result.data.message;
            scope.isIdentityUserExist = true;
        } else
        {
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
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
        const { first_name, last_name, gender_id, date_of_birth, identity_type_id, identity_number, nationality_id, openemis_no } = scope.selectedUserData;
        const isGeneralInfodHasError = (!first_name || !last_name || !gender_id || !date_of_birth)
        const isOpenEmisNoHasError = openemis_no !== "" && openemis_no !== undefined;
        const isIdentityHasError = identity_number?.length>1  && (nationality_id === undefined || nationality_id==="" || nationality_id === null  || identity_type_id===""|| identity_type_id===undefined || identity_type_id=== null)
        const isSkipableForIdentity = identity_number?.length>1 && nationality_id > 0 && identity_type_id >0;

        if (isIdentityHasError)
        {
            return ['Identity', true]
        }
        if (isSkipableForIdentity)
        {
            return ['Identity', false]
        }
        if (isOpenEmisNoHasError)
        {
            return ["OpenEMIS_ID", false];
        }
      
        if (isGeneralInfodHasError)
        {
            return ["General_Info", true];
        }

        return ["", false];
    }
    scope.checkConfigForExternalSearch=function checkConfigForExternalSearch()
    {
        DirectoryaddSvc.checkConfigForExternalSearch().then(function (resp)
        {
            scope.isExternalSearchEnable = resp.showExternalSearch;
            scope.externalSearchSourceName = resp.value;
            UtilsSvc.isAppendLoader(false);
        }, function (error)
        {
            scope.isExternalSearchEnable = false;
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.isNextButtonShouldDisable = function isNextButtonShouldDisable() {
        const { step, selectedUserData, isIdentityUserExist } = scope;
        const { first_name, last_name, date_of_birth, gender_id } = selectedUserData;
      
        if (isIdentityUserExist && step === "internal_search") {
          return true;
        }
      
        if (step === "external_search" && (!first_name|| !last_name || !date_of_birth|| !gender_id)) {
          return true;
        }
        return false;
    }

    scope.getCSPDSearchData = function getCSPDSearchData() {
        var param = {            
            identity_number: scope.selectedUserData.identity_number,
        };
        var dataSource = {
            pageSize: scope.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                DirectoryaddSvc.getCspdData(param)
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
                    var totalRowCount = gridData.length === 0 ? 1 : gridData.length;
                    scope.isSearchResultEmpty = gridData.length === 0;  
                    return scope.processExternalGridUserRecord(gridData, params, totalRowCount);
                }, function(error) {
                    console.log(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        scope.externalGridOptions.api.setDatasource(dataSource);
        scope.externalGridOptions.api.sizeColumnsToFit(); 
    }

    scope.checkUserExistByIdentityFromConfiguration = async function checkUserExistByIdentityFromConfiguration()
    {
        const { identity_type_id, identity_number, nationality_id } = scope.selectedUserData;
        // scope.error.nationality_id = "";
        scope.error.identity_type_id = ""
        scope.error.identity_number = "";

        /* if (!nationality_id)
        {
            scope.error.nationality_id =
                "This field cannot be left empty";

                return false;
        } */
        if (!identity_type_id)
        {
            scope.error.identity_type_id =
                "This field cannot be left empty";
                return false;
        }
        if (!identity_number)
        {
            scope.error.identity_number =
                "This field cannot be left empty";

                return false;
        }

        const result =
            await DirectoryaddSvc.checkUserAlreadyExistByIdentity({
                identity_type_id: identity_type_id,
                identity_number: identity_number,
              /*   nationality_id: nationality_id, */
            });
 
        if (result.data.user_exist === 1)
        { 
            scope.messageClass = 'alert_warn';
            scope.message = 'This identity has already existed in the system.';
            scope.isIdentityUserExist = true;
            scope.error.identity_number =
            "This identity has already existed in the system.";
            $window.scrollTo({bottom:0});
        } else
        { 
            scope.messageClass = '';
            scope.message = '';
            scope.isIdentityUserExist = false;
            scope.error.identity_number ==""
        }
        return result.data.user_exist === 1;
    }
}
