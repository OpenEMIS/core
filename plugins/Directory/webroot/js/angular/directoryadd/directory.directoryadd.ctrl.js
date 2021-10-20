angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
    var scope = $scope;

    scope.step = "user_details";
    scope.selectedUserData = {};
    scope.internalGridOptions = null;
    scope.externalGridOptions = null;
    scope.postRespone = null;
    scope.translateFields = null;
    scope.genderOptions = [
        {
            id: 1,
            name: 'Male'
        },
        {
            id: 2,
            name: 'Female'
        }
    ];
    scope.userTypeOptions = [
        {
            id: 1,
            name: 'Student'
        },
        {
            id: 2,
            name: 'Staff'
        },
        {
            id: 3,
            name: 'Guardian'
        },
        {
            id: 4,
            name: 'Other'
        }
    ];
    scope.nationality_class = 'input select error';
    scope.identity_type_class = 'input select error';
    scope.identity_class = 'input string';
    scope.messageClass = '';
    scope.message = '';

    angular.element(document).ready(function () {
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
    });

    scope.getUniqueOpenEmisId = function() {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.getUniqueOpenEmisId()
            .then(function(response) {
                var username = scope.selectedUserData.username;
                //POCOR-5878 starts
                if(username != scope.selectedUserData.openemis_no && (username == '' || typeof username == 'undefined')){
                    scope.selectedUserData.username = scope.selectedUserData.openemis_no;
                    scope.selectedUserData.openemis_no = scope.selectedUserData.openemis_no;
                }else{
                    if(username == scope.selectedUserData.openemis_no){
                        scope.selectedUserData.username = response;
                    }
                    scope.selectedUserData.openemis_no = response;
                }
                //POCOR-5878 ends
                UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
    }

    scope.generatePassword = function() {
        UtilsSvc.isAppendLoader(true);
        DirectoryaddSvc.generatePassword()
        .then(function(response) {
            if (scope.selectedUserData.password == '' || typeof scope.selectedUserData.password == 'undefined') {
                scope.selectedUserData.password = response;
            }
            UtilsSvc.isAppendLoader(false);
        }, function(error) {
            console.log(error);
            UtilsSvc.isAppendLoader(false);
        });
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

    scope.goToPrevStep = function(){
        switch(scope.step){
            case 'internal_search': 
                scope.step = 'user_details';
                break;
            case 'external_search': 
                scope.step = 'internal_search';
                break;
            case 'confirmation': 
                scope.step = 'external_search';
                break;
        }
    }

    scope.goToNextStep = function() {
        switch(scope.step){
            case 'user_details': 
                scope.step = 'internal_search';
                // scope.getUniqueOpenEmisId();
                // scope.generatePassword();
                break;
            case 'internal_search': 
                scope.step = 'external_search';
                break;
            case 'external_search': 
                scope.step = 'confirmation';
                break;
        }
    }

    scope.confirmUser = function () {
        scope.message = (scope.selectedUserData && scope.selectedUserData.userType ? scope.selectedUserData.userType.name : 'Student') + ' successfully added.';
        scope.messageClass = 'alert-success';
        scope.step = "summary";
    }

    scope.goToFirstStep = function () {
        scope.step = 'user_details';
    }

    // scope.selectStaff = function(id) {
    //     scope.selectedUser = id;
    //     scope.getStaffData();
    // }

    // scope.getStaffData = function() {
    //     var log = [];
    //     angular.forEach(scope.rowsThisPage , function(value) {
    //         if (value.id == scope.selectedUser) {
    //             scope.selectedUserData = value;
    //         }
    //     }, log);
    // }

    scope.initGrid = function() {
        AggridLocaleSvc.getTranslatedGridLocale()
        .then(function(localeText){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    scope.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_number, field: "identity_number", suppressMenu: true, suppressSorting: true}
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
                }
            };
        }, function(error){
            scope.internalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.openemis_no, field: "openemis_no", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };

            scope.externalGridOptions = {
                columnDefs: [
                    {headerName: scope.translateFields.name, field: "name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.gender_name, field: "gender_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.date_of_birth, field: "date_of_birth", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.nationality_name, field: "nationality_name", suppressMenu: true, suppressSorting: true},
                    {headerName: scope.translateFields.identity_type_name, field: "identity_type_name", suppressMenu: true, suppressSorting: true},
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
                    StaffController.selectStaff(_e.node.data.id);
                    $scope.$apply();
                }
            };
        });
    };
    
    scope.initGrid();
}