angular
    .module('directory.directoryadd.svc', ['kd.orm.svc', 'alert.svc'])
    .service('DirectoryaddSvc', DirectoryaddSvc);

DirectoryaddSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc', 'AlertSvc', 'UtilsSvc'];

function DirectoryaddSvc($http, $q, $filter, KdOrmSvc, AlertSvc, UtilsSvc) {

    var models = {
        Genders: 'User.Genders',
        Nationalities: 'FieldOption.Nationalities'
    };

    var service = {
        init: init,
        getUniqueOpenEmisId: getUniqueOpenEmisId,
        generatePassword: generatePassword,
        getUserTypes: getUserTypes,
        getGenders: getGenders,
        getNationalities: getNationalities,
        getIdentityTypes: getIdentityTypes,
        getInternalSearchData: getInternalSearchData,
        getExternalSearchData: getExternalSearchData,
        getContactTypes: getContactTypes,
        getRelationType: getRelationType,
        getRedirectToGuardian: getRedirectToGuardian
    };
    return service;
    
    function init(baseUrl){
        KdOrmSvc.base(baseUrl);
        KdOrmSvc.controllerAction('Directory');
        KdOrmSvc.init(models);
    }

    function getUniqueOpenEmisId() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getUniqueOpenemisId/';
        console.log(url);
        $http.get(url)
        .then(function(response){
            console.log("response");
            console.log(response);
            deferred.resolve(response.data.openemis_no);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function generatePassword() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getAutoGeneratedPassword/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response.data.password);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getUserTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getUserType/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getGenders() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getGenders/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getNationalities() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getNationalities/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }

    function getIdentityTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getIdentityTypes/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getContactTypes() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getContactType/';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getInternalSearchData(first_name ,last_name) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryInternalSearch?fname=' + first_name + '&lname=' + last_name;
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getExternalSearchData(first_name ,last_name) {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/directoryInternalSearch?fname=' + first_name + '&lname=' + last_name;
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getRelationType() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getRelationshipType';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    };

    function getRedirectToGuardian() {
        var deferred = $q.defer();
        var url = angular.baseUrl + '/Directories/getRedirectToGuardian';
        $http.get(url)
        .then(function(response){
            deferred.resolve(response);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred.promise;
    }
};