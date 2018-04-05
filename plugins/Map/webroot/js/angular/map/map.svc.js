angular
    .module('map.svc', ['kd.data.svc', 'kd.session.svc'])
    .service('MapSvc', MapSvc);

MapSvc.$inject = ['$q', 'KdDataSvc', 'KdSessionSvc'];

function MapSvc($q, KdDataSvc, KdSessionSvc) {
    var service = {
        init: init,
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('Map');
        KdDataSvc.init({ConfigItemsTable: 'Configuration.ConfigItems'});
    };
}
