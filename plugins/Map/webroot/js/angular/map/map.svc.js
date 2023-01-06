angular
    .module('map.svc', ['kd.data.svc', 'kd.session.svc'])
    .service('MapSvc', MapSvc);

MapSvc.$inject = ['$q', 'KdDataSvc', 'KdSessionSvc'];

function MapSvc($q, KdDataSvc, KdSessionSvc) {
    var service = {
        init: init,
        translate: translate,
        getMapConfig: getMapConfig,
        getMapData: getMapData,
        getConfigItemValue: getConfigItemValue
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('Map');
        KdDataSvc.init({ConfigItemsTable: 'Configuration.ConfigItems'});
        KdDataSvc.init({InstitutionsTable: 'Institution.Institutions'});
    };

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    };

    function getMapConfig() {
        var promises = [];
        promises[0] = this.getConfigItemValue('map_zoom');
        promises[1] = this.getConfigItemValue('map_center_longitude');
        promises[2] = this.getConfigItemValue('map_center_latitude');

        return $q.all(promises);
    };

    function getMapData() {
        var success = function(response, deferred) {
            var institutions = response.data.data;

            if (response.data.total > 0) {
                deferred.resolve(institutions);
            } else {
                deferred.reject('No Institutions');
            }
        };

        return InstitutionsTable
            .find('map')
            .ajax({success: success, defer: true});
    };

    function getConfigItemValue(code) {
        var success = function(response, deferred) {
            var results = response.data.data;

            if (angular.isObject(results) && results.length > 0) {
                var configItemValue = (results[0].value.length > 0) ? results[0].value : results[0].default_value;
                deferred.resolve(configItemValue);
            } else {
                deferred.reject('There is no ' + code + ' configured');
            }
        };

        return ConfigItemsTable
            .where({code: code})
            .ajax({success: success, defer: true});
    };
}
