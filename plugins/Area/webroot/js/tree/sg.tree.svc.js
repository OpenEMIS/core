angular
    .module('sg.tree.svc', ['kd.data.svc'])
    .service('SgTreeSvc', SgTreeSvc);

SgTreeSvc.$inject = ['$q', 'KdDataSvc'];

function SgTreeSvc($q, KdDataSvc) {
    var service = {
        init: init,
        translate: translate,
        getRecords: getRecords
    };

    return service;

    function init(baseUrl) {
        KdDataSvc.base(baseUrl);
        KdDataSvc.controllerAction('SgTree');
    }

    function translate(data) {
        KdDataSvc.init({translation: 'translate'});
        var success = function(response, deferred) {
            var translated = response.data.translated;
            deferred.resolve(translated);
        };
        return translation.translate(data, {success:success, defer: true});
    }

    function getRecords(model, userId, displayCountry, selected, recordOnly) {
        displayCountry = (displayCountry === undefined)? 0: displayCountry;
        recordOnly = (recordOnly === undefined)? 0: recordOnly;
        KdDataSvc.init({area: model});
        var success = function(response, deferred) {
            var returnData = response.data.data;
            deferred.resolve(returnData);
        };
        return area
            .select()
            .find('areaList', {'userId': userId, 'displayCountry': displayCountry, 'selected': selected, 'recordOnly': recordOnly})
            .ajax({success: success, defer:true});
    }
};