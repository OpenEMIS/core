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
                var options = {
                    format: 'dd-mm-yyyy',
                    todayBtn: 'linked',
                    orientation: 'auto',
                    autoclose: true,
                    autoUpdateInput: false
                };
                if (typeof angular.datepickers === 'undefined') {
                    angular.datepickers = [];
                }
                datepicker = elem.datepicker(options);
                angular.datepickers.push(datepicker);

                // This is a temp work-around to make datepickers rendered through angular directive to work as expexted.
                // If the page has a datepicker input rendered through cakephp way,
                // then this event attachment to document element might collide.
                // Must re-visit this.
                var _document = jQuery( document );
                _document.on('DOMMouseScroll mousewheel scroll', function() {
                        window.clearTimeout( t );
                        t = window.setTimeout( function() {
                            for (var i in angular.datepickers) {
                                angular.datepickers[i].datepicker('place');
                            }
                        });
                    }
                );
            }
        };
    })
    ;
