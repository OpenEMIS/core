angular
    .module('directory.directoryadd.svc', ['kd.data.svc', 'alert.svc'])
    .service('DirectoryaddSvc', DirectoryaddSvc);

DirectoryaddSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function DirectoryaddSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    var service = {
        init: init,
        translate: translate,
        getTranslatedText: getTranslatedText,
        getAcademicPeriodOptions: getAcademicPeriodOptions,
        getWeekListOptions: getWeekListOptions,
        getStaffAttendances: getStaffAttendances,
        getColumnDefs: getColumnDefs,
    };
    return service;
    
    function init(){
        alert("Init service");
    }
};