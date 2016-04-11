//Controller Action Angular Functions v.1.0.1
angular.module('kd.drt', ['kd.common.svc'])
    .directive('kdOnChangeElement', function(kdCommonSvc) {
        return {
            restrict: 'A',
            link: function(scope, elem, attr) {

                elem.on('change', function(event) {
                    kdCommonSvc.changeOptions(scope, elem.val(), attr);
                });
            
            }
        };
    })
    .directive('kdOnClickElement', function(kdCommonSvc) {
        return {
            restrict: 'A',
            link: function(scope, elem, attr) {

                elem.on('click', function(event) {
                    var clickAction = kdCommonSvc[attr.kdOnClickElement];
                    clickAction(scope, elem, attr);
                });

            }
        };
    })
    ;
