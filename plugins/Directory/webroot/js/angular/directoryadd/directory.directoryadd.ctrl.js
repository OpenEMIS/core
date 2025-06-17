angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc', 'kd-angular-tree-dropdown'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', '$filter', '$timeout', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc', 'KdDataSvc']; //POCOR-8014-n

function DirectoryAddController($scope, $q, $window, $http, $filter, $timeout, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc, KdDataSvc) {
    var scope = $scope;
    const userCtrl = $scope;
    const userSvc = DirectoryaddSvc;
    const directorySvc = DirectoryaddSvc;
    userCtrl.step = "user_details";
    userCtrl.selectedUserData = {};
    userCtrl.internalGridOptions = null;
    userCtrl.externalGridOptions = null;
    userCtrl.postRespone = null;
    userCtrl.translateFields = null;
    userCtrl.genderOptions = [];
    userCtrl.userTypeOptions = [];
    userCtrl.nationality_class = 'input select error';
    userCtrl.identity_type_class = 'input select error';
    userCtrl.identity_class = 'input string';
    userCtrl.messageClass = '';
    userCtrl.message = '';
    userCtrl.nationalitiesOptions = [];
    userCtrl.identityTypeOptions = [];
    userCtrl.contactTypeOptions = [];
    userCtrl.addressAreaOption = [];
    userCtrl.birthplaceAreaOption = [];
    userCtrl.isGuardianAdding = false;
    userCtrl.pageSize = 10;
    userCtrl.rowsThisPage = [];
    userCtrl.selectedUser;
    userCtrl.dobDatepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    userCtrl.error = {};
    userCtrl.customFields = [];
    userCtrl.customFieldsArray = [];
    var todayDate = new Date();
    userCtrl.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');
    userCtrl.redirectToGuardian = false;
    userCtrl.isInternalSearchSelected = false;
    userCtrl.isExternalSearchSelected = false;
    userCtrl.canSkipNationality = false;
    userCtrl.canSkipIdentity = false;
    userCtrl.datepickerOptions = {
        minDate: new Date('01/01/1900'),
        maxDate: new Date(),
        showWeeks: false
    };
    userCtrl.addressAreaId = null;
    userCtrl.birthplaceAreaId = null;
    userCtrl.isIdentityUserExist = false;
    userCtrl.isExternalSearchEnable = false;
    userCtrl.externalSearchSourceName = '';
    userCtrl.isSearchResultEmpty = true;
    userCtrl.isSearchResultEmpty = false;

    userCtrl.disableFields = {
        username: false,
        password: false
    }

    $window.savePhoto = function(event) {
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

            // Remove specific items from local storage

            ['address_area', 'address_area_id', 'birthplace_area', 'birthplace_area_id', 'studentOpenEmisId'].forEach(item => {
                if ($window.localStorage.getItem(item)) {
                    $window.localStorage.removeItem(item);
        }
            });
            // scope.initGrid();
            loadUserData();
        }

        function getGenders() {
            return directorySvc.setGenders(userCtrl)
        }

        function getUserTypes() {
            return directorySvc.setUserTypes(userCtrl);
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

        function loadUserData() {
                getGenders()
                .then(getUserTypes)
                .then(getNationalities)
                .then(getIdentityTypes)
                .then(getContactTypes)
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

    userCtrl.changeUserType = function () {
        directorySvc.changeUserType(scope);
    };

// user_details function

    userCtrl.changeNationality = function() {
        directorySvc.changeNationality(scope);
    };

    userCtrl.changeIdentityType = function() {
        directorySvc.changeIdentityType(scope);
        };

    userCtrl.changeIdentityNumber = function() {
        directorySvc.changeIdentityNumber(scope);
    };

    userCtrl.changeContactValue = function() {
        directorySvc.changeContactValue(scope);
    };

    userCtrl.setName = function() {
        directorySvc.setName(scope);
    };

    userCtrl.changeGender = function() {
        directorySvc.changeGender(scope);
    };

    userCtrl.changeDateOfBirth = function() {
        directorySvc.changeDateOfBirth(scope);
    };

    userCtrl.setError = function(field, message) {
        directorySvc.setError(userCtrl.error, field, message);
    };

    userCtrl.unsetError = function(field) {
        directorySvc.unsetError(userCtrl.error, field);
    };

    userCtrl.unsetCustomError = function(field) {
        field.errorMessage = null;
        directorySvc.unsetError(userCtrl.error, field.name);
    };

    userCtrl.unsetAllErrors = function() {
        userCtrl.error = {};
    };

    userCtrl.validateUserDetails = function () {
        directorySvc.validateUserDetails(scope);
    };

    userCtrl.validateConfirmDetails = function () {
        directorySvc.validateConfirmDetails(scope);
    };

    userCtrl.goToNextStep = async function () {
        if (userCtrl.step === 'confirmation') {
            const result =
                await userCtrl.checkUserExistByIdentityFromConfiguration();
            // if (result) return;
        }

        if (userCtrl.isInternalSearchSelected) {
            userCtrl.processNewUser();
        } else {
            switch (userCtrl.step) {
                case 'user_details':
                    scope.internalGridOptions = null;
                    scope.validateUserDetails();
                    break;
                case 'internal_search':
                    if (scope.isExternalSearchEnable) {
                        scope.step = 'external_search';
                        scope.externalGridOptions = null;
                // UtilsSvc.isAppendLoader(true);
                        scope.goToExternalSearch();
                    } else {
                        scope.processNewUser();
                    }
                    return;
                case 'external_search':
                    scope.processNewUser();
                    break;
            }
        }
    };

    userCtrl.goToPrevStep = function () {
        userCtrl.error = {};
        userCtrl.disableFields = {
            username: false,
            password: false
                    }
        const userTypeId = userCtrl.selectedUserData.user_type_id;
        const userType = userCtrl.selectedUserData.userType;
        userCtrl.selectedUserData = {};
        userCtrl.selectedUserData.user_type_id = userTypeId;
        userCtrl.selectedUserData.userType = userType;
        userCtrl.selectedUser = null;
        if (userCtrl.isInternalSearchSelected) {
            userCtrl.isInternalSearchSelected = false;
            userCtrl.step = 'user_details';
            userCtrl.internalGridOptions = null;
            // userCtrl.goToInternalSearch();
                        }else{
            switch (userCtrl.step) {
                case 'internal_search': {
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
            }
        }
            }

    userCtrl.goToFirstStep = function () {
        userCtrl.step = 'user_details';
        userCtrl.selectedUserData = {};
    }

    userCtrl.cancelProcess = function () {
        location.href = angular.baseUrl + '/Directory/Directories/Directories/index';
    }

    userCtrl.goToInternalSearch = function () {
        directorySvc.goToInternalSearch(scope);
    };

    userCtrl.goToExternalSearch = function () {
        directorySvc.goToExternalSearch(scope);
    }

    userCtrl.processGridUserRecord = function (userRecords, params, totalRowCount) {
        // console.log(userRecords);
        if (userRecords.length === 0)
        {
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

    userCtrl.getRedirectToGuardian = function () {
        UtilsSvc.isAppendLoader(true);

        userSvc.getRedirectToGuardian()
        .then(function(resp) {
                userCtrl.redirectToGuardian = resp.data[0].redirecttoguardian_status;
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    userCtrl.confirmUser = async function () {
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        scope.step = "summary";

        const todayDate = new Date();
        scope.todayDate = $filter('date')(todayDate, 'yyyy-MM-dd HH:mm:ss');

        if (scope.selectedUserData.userType.name === 'Students') {
            scope.getRedirectToGuardian();
        }
    };

    userCtrl.processNewUser = function () {
        scope.step = 'confirmation';
        UtilsSvc.isAppendLoader(true);

        scope.checkUserExistByIdentityFromConfiguration()
            .then(result => {
                if (!result) {
                    return scope.getUniqueOpenEmisId();
    }
            })
            .then(() => {
                return scope.generatePassword();
            })
            .then(() => {
                if (scope.selectedUserData.userType.name === 'Students') {
                    return scope.getStudentCustomFields();
                } else if (scope.selectedUserData.userType.name === 'Staff') {
                    // return Promise.resolve();
                    return scope.getStaffCustomFields();
                }
            })
            .then(() => {
                if (scope.selectedUserData.userType.name === 'Students') {
                    return scope.getRedirectToGuardian();
                }
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

    userCtrl.getUniqueOpenEmisId = function () {
        return directorySvc.setUniqueOpenEmisId(scope);
    };

    userCtrl.generatePassword = function () {
        return directorySvc.setPassword(scope);
    };

    userCtrl.initGrid = function () {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
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
                        userCtrl.isInternalSearchSelected = true;
                        userCtrl.isExternalSearchSelected = false;
                        userCtrl.selectUserFromInternalSearch(_e.node.data.id);
                        scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                        if (angular.isDefined(userCtrl.internalGridOptions.api)) {
                        setTimeout(function() {
                                userCtrl.setGridData();
                        })
                    }
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
                        userCtrl.selectUserFromExternalSearch(id);
                    scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                    onGridReady: function () {
                        if (angular.isDefined(userCtrl.externalGridOptions.api)) {
                            setTimeout(function () {
                                userCtrl.setGridData();
                            })
                        }
                    },
            };
        }, function(error){
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
                        userCtrl.isExternalSearchSelected = false;
                        userCtrl.isInternalSearchSelected = true;
                        userCtrl.selectUserFromInternalSearch(_e.node.data.id);
                    scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                        if (angular.isDefined(userCtrl.internalGridOptions.api)) {
                        setTimeout(function() {
                                userCtrl.setGridData();
                        })
                    }
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
                        userCtrl.selectUserFromExternalSearch(id);
                    scope.$apply();
                },
                onGridSizeChanged: function() {
                    this.api.sizeColumnsToFit();
                },
                onGridReady: function() {
                        if (angular.isDefined(userCtrl.externalGridOptions.api)) {
                        setTimeout(function() {
                                userCtrl.setGridData();
                        })
                    }
                },
            };
        });
    };

    userCtrl.selectUserFromInternalSearch = function (id) {
        userCtrl.selectedUser = id;
        userCtrl.isInternalSearchSelected = true;
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
        userCtrl.isInternalSearchSelected = false;
        userCtrl.isExternalSearchSelected = true;
        userCtrl.selectedUser = id;
        userCtrl.getUserData();
        userCtrl.disableFields = {
            username: false,
            password: false
        };
    }

    userCtrl.setUserData = function (selectedData) {
        // console.log(selectedData);
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
    }

    userCtrl.setExternalUserData = function (selectedData) {
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

    userCtrl.getUserData = function () {
        var log = [];
        angular.forEach(userCtrl.rowsThisPage, function (value) {
            if (value.id == userCtrl.selectedUser) {
                if (userCtrl.isInternalSearchSelected)
                    userCtrl.setUserData(value);
                else
                    userCtrl.setExternalUserData(value);
            }
        }, log);
    }

    scope.addGuardian = function(){
        let params = {
            openemis_no: scope.selectedUserData.openemis_no
        };
        var queryString = KdDataSvc.urlsafeB64Encode(JSON.stringify(params));
        $window.location.href = angular.baseUrl + '/Directory/Directories/Addguardian/' + queryString;
    }

    scope.getStudentCustomFields = function() {
        let userId = scope.selectedUserData.userId ? scope.selectedUserData.userId : null;
        DirectoryaddSvc.getStudentCustomFields(userId).then(function(resp){

            scope.customFields = resp.data;
            scope.customFieldsArray = [];
            scope.createCustomFieldsArray();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
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
            console.error(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.createCustomFieldsArray = function() {
        directorySvc.createCustomFieldsArray(scope);
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
            contact_value: scope.selectedUserData.contact_value,
            email: scope.selectedUserData.email,
            mobile_number: scope.selectedUserData.mobile_number,

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
            // console.log('after save');
            // console.log(resp);
            scope.selectedUserData.user_id = resp.data.id;
            scope.confirmUser();
            UtilsSvc.isAppendLoader(false);
        }, function(error){
            console.error(error);
            userCtrl.message =  error.data.message || error.statusText || error.toString();
            userCtrl.messageClass = 'alert-danger';
            UtilsSvc.isAppendLoader(false);
        });
    }

    userCtrl.isNextButtonShouldDisable = function isNextButtonShouldDisable() {
        return directorySvc.isNextButtonShouldDisable(userCtrl);
    }


    userCtrl.getCSPDSearchData = function getCSPDSearchData() {
        var param = userCtrl.selectedUserData;  //POCOR-7916
        var dataSource = {
            pageSize: userCtrl.pageSize,
            getRows: function (params) {
                UtilsSvc.isAppendLoader(true);
                param.limit = params.endRow - params.startRow;
                param.page = params.endRow / (params.endRow - params.startRow);
                userSvc.getCspdData(param)
                .then(function(response) {
                    var gridData = response.data.data; //POCOR-7916
                    if(!gridData)gridData = [];
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
                }, function(error) {
                    console.error(error);
                    UtilsSvc.isAppendLoader(false);
                });
            }
        };
        userCtrl.externalGridOptions.api.setDatasource(dataSource);
        userCtrl.externalGridOptions.api.sizeColumnsToFit();
    }

    userCtrl.checkUserExistByIdentityFromConfiguration = async function () {
        const {identity_type_id, identity_number} = userCtrl.selectedUserData;

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
            const result = await userSvc.checkUserExistByIdentity({
                identity_type_id,
                identity_number,
                nationality_id: userCtrl.selectedUserData.nationality_id,
        });

            if (result.data.user_exist === 1) {
                userCtrl.messageClass = 'alert_warn';
                userCtrl.message = result.data.message;
                userCtrl.isIdentityUserExist = true;
                // userCtrl.error.identity_number = result.data.message;
            $window.scrollTo({bottom:0});

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

}
}
