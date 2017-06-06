angular
    .module('sg.tree.svc', ['kd.data.svc'])
    .service('SgTreeSvc', SgTreeSvc);

SgTreeSvc.$inject = ['$q', 'KdDataSvc'];

function SgTreeSvc($q, KdDataSvc) {
    var service = {
        init: init,
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('SgTree');
    }

    function getRecords(model, areaIds = []) {
        KdDataSvc.init({area: model});
        var success = function(response, deferred) {
            var returnData = response.data.data;
            deferred.resolve(returnData);
        };
        return area
            .select()
            .find('areaList', {'authorisedAreaIds':areaIds})
            .ajax({success: success, defer:true});
    }
};