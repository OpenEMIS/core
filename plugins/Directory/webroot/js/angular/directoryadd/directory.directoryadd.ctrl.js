angular.module('directory.directoryadd.ctrl', ['utils.svc', 'alert.svc', 'aggrid.locale.svc', 'directory.directoryadd.svc'])
    .controller('DirectoryAddCtrl', DirectoryAddController);

DirectoryAddController.$inject = ['$scope', '$q', '$window', '$http', 'UtilsSvc', 'AlertSvc', 'AggridLocaleSvc', 'DirectoryaddSvc'];

function DirectoryAddController($scope, $q, $window, $http, UtilsSvc, AlertSvc, AggridLocaleSvc, DirectoryaddSvc) {
    var scope = $scope;

    scope.step = "user_details";
    scope.selectedUserData = {};
    scope.internalGridOptions = null;
    scope.postRespone=null;
    scope.genderOption = {};

    angular.element(document).ready(function () {
        DirectoryaddSvc.init(angular.baseUrl);
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

    angular.element(document.querySelector('#wizard')).on('changed.fu.wizard', function(evt, data) {
        // Step 1 - User details
        if (data.step == 1) {
            scope.step = 'user_details';
            scope.getUniqueOpenEmisId();
            scope.generatePassword();
        }
        // Step 2 - Internal search
        else if (data.step == 2) {
            scope.step = 'internal_search';
        }
        // Step 3 - External search
        else if (data.step == 3) {
            scope.step = 'external_search';
        }
        // Step 4 - Create user
        else if (data.step == 4) {
            scope.step = 'confirmation';
        }
        // Step 5 - Add Staff
        else if (data.step == 5) {
            // Work around for alert reset
            scope.step = 'add_staff';
        }
        // Step 6 - Transfer Staff
        else if (data.step == 6) {
            scope.step = 'summary';
        }
    });
}