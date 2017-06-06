angular
    .module('sg.tree.svc', ['kd.data.svc'])
    .service('SgTreeSvc', SgTreeSvc);

SgTreeSvc.$inject = ['$q', 'KdDataSvc'];

function SgTreeSvc($q, KdDataSvc) {
    var service = {
        init: init,
        getRecords: getRecords
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('SgTree');
    }

    function getRecords(model, areaIds = [], displayCountry = 0) {
        KdDataSvc.init({area: model});
        var success = function(response, deferred) {
            var returnData = response.data.data;
            deferred.resolve(returnData);
        };
        return area
            .select()
            .find('areaList', {'authorisedAreaIds': KdDataSvc.urlsafeB64Encode(JSON.stringify(areaIds)), 'displayCountry': displayCountry})
            .ajax({success: success, defer:true});
    }
};