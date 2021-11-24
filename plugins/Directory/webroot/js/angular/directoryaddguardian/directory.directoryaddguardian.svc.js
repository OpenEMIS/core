angular
    .module('directory.directoryaddguardian.svc', ['kd.orm.svc', 'alert.svc'])
    .service('DirectoryaddguardianSvc', DirectoryaddguardianSvc);

DirectoryaddguardianSvc.$inject = ['$http', '$q', '$filter', 'KdOrmSvc', 'AlertSvc', 'UtilsSvc'];

function DirectoryaddguardianSvc($http, $q, $filter, KdOrmSvc, AlertSvc, UtilsSvc) {
  
};