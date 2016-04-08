//Controller Action Angular Functions v.1.0.1
angular.module('kd.drt', ['kd.common.svc'])
    .directive('kdOnChangeElement', function(kdCommonSvc) {
        pr(kdCommonSvc.ctrl);
        pr(kdCommonSvc.baseUrl);
        pr(kdCommonSvc);
        return {
            /*
             * When you create a directive, it is restricted to attribute and elements only by default. In order to create directives that are triggered by class name, you need to use the restrict option.
             * The restrict option is typically set to:
             * 
             * 'A' - only matches attribute name
             * 'E' - only matches element name
             * 'C' - only matches class name
             * 'M' - only matches comment
             * These restrictions can all be combined as needed:
             * 
             * 'AEC' - matches either attribute or element or class name
             */ 
            restrict: 'A',
            // controller: 'kdCtrl',
            link: function(scope, elem, attr, kdCtrl) {

                elem.on('change', function(event) {
                    kdCtrl.changeOptions(elem.val(), attr);
                });

            }
        };
    })
    .directive('skdOnChangeTargetElement', ['kdCommonSvc', function(kdCommonSvc) {
        return {
            restrict: 'A',
            controller: 'kdCtrl',
            templateUrl: function(elem, attr) {

                if (typeof attr.kdOnChangeTargetElementTemplateUrl !== 'undefined') {
                    pr(kdCommonSvc.baseUrl);
                    var templateUrl = (attr.kdOnChangeTargetElementTemplateUrl).replace('{{baseUrl}}', 'core');
                    return templateUrl;
                } else {
                    // attr.caId;
                    return '/js/angular/kdModule/templates/kdOnChangeTarget.html';
                }

            },
            link: function(scope, elem, attr, kdCtrl) {

                kdCtrl.registerOnChangeTargets(attr.caId);

            }
        };
    }])
    // .directive('caOnClickElement', function() {
    //     return {
    //         restrict: 'A',
    //         // require: "^kdCtrl"
    //         transclude: true,
    //         controller: 'kdCtrl',
    //         link: function(scope, elem, attr, kdCtrl) {

    //             elem.on('click', function(event) {
    //                 var clickAction = scope[attr.caOnClickElement];
    //                 clickAction(scope, elem, attr);
    //             });

    //         }
    //     };
    // })
    // .directive('caOnClickTargetElement', function() {
    //     return {
    //         restrict: 'A',
    //         bindToController: {
    //           handlers: '='
    //         },
    //         controllerAs: 'clickTarget',
    //         controller: function() {

    //             this.handlers = {
    //                 'addRow': {},
    //                 'alert': {}
    //             };

    //             this.registerElement = function(handler, caId) {
    //                 this.handlers[handler][caId] = [];
    //             };

    //             this.removeRow = function(caId, index) {
    //                 this.handlers.addRow[caId].splice(index, 1);
    //             };

    //         },
    //         link: function(scope, elem, attr, clickTarget) {

    //             clickTarget.registerElement(attr.caOnClickTargetHandler, attr.caId);

    //             scope.$on('onClickComplete', function (event, handler, target, data) {
    //                 clickTarget.handlers[handler][target].push(data);
    //             })

    //         }
    //     };
    // })
    ;
