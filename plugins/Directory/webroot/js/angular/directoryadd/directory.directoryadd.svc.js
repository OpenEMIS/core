angular
    .module('directory.directoryadd.svc', ['kd.data.svc', 'alert.svc'])
    .service('DirectoryaddSvc', DirectoryaddSvc);

DirectoryaddSvc.$inject = ['$http', '$q', '$filter', 'KdDataSvc', 'AlertSvc', 'UtilsSvc'];

function DirectoryaddSvc($http, $q, $filter, KdDataSvc, AlertSvc, UtilsSvc) {
    alert("Hello");
    console.log("HEllO");
};