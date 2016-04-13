//Controller Action Angular Functions v.1.0.1
angular.module('kd.drt', ['kd.common.svc'])
    .directive('kdOnChangeElement', function(kdCommonSvc) {
        return {
            restrict: 'A',
            link: function(scope, elem, attr) {

                elem.on('change', function(event) {
                    kdCommonSvc.changeOptions(scope, elem.val(), attr);
                });

                var selectedValue = (typeof attr.kdSelectedValue !== 'undefined') ? attr.kdSelectedValue : 0;
                if (elem.val()!='' || elem.val()>0) {
                    kdCommonSvc.changeOptions(scope, elem.val(), attr);
                } else if (selectedValue>0) {
                    elem.val(selectedValue);
                    kdCommonSvc.changeOptions(scope, selectedValue, attr);
                }
            
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
    .directive('hidden', function(kdCommonSvc) {
        return {
            restrict: 'E',
            replace: true,
            templateUrl: function (elem, attr) {
                return angular.baseUrl + '/Angular/inputs?attributes='+attr.attributes;
            },
            link: function(scope, elem, attr) {

            }
        };
    })
    .directive('textbox', function(kdCommonSvc) {
        return {
            restrict: 'E',
            replace: true,
            templateUrl: function (elem, attr) {
                return angular.baseUrl + '/Angular/inputs?attributes='+attr.attributes;
            },
            link: function(scope, elem, attr) {

            }
        };
    })
    .directive('datepicker', function(kdCommonSvc) {
        return {
            restrict: 'E',
            replace: true,
            templateUrl: function (elem, attr) {
                return angular.baseUrl + '/Angular/inputs?attributes='+attr.attributes;
            },
            link: function(scope, elem, attr) {

            }
        };
    })
    ;
