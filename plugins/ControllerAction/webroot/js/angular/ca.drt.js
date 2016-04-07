//Controller Action Angular Functions v.1.0.1
angular.module('ca.drt', [])
    .directive('caOnChangeElement', function() {
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
            controller: 'caCtrl',
            link: function(scope, elem, attr, caCtrl) {

                elem.on('change', function(event) {
                    caCtrl.changeOptions(elem.val(), attr);
                });

            }
        };
    })
    .directive('caOnChangeTargetElement', function() {
        return {
            restrict: 'A',
            controller: 'caCtrl',
            templateUrl: function(elem, attr) {

                if (typeof attr.caOnChangeTargetElementTemplateUrl !== 'undefined') {
                    return attr.caOnChangeTargetElementTemplateUrl;
                } else {
                    // attr.caId;
                    return '/controller_action/js/angular/templates/caOnChangeTarget.html';
                }

            },
            link: function(scope, elem, attr, caCtrl) {

                caCtrl.registerOnChangeTargets(attr.caId);
                // console.log(scope);
                // console.log(caCtrl);
                // scope.$watch
            }
        };
    })
    .directive('caOnClickElement', function() {
        return {
            restrict: 'A',
            controller: 'caCtrl',
            link: function(scope, elem, attr, caCtrl) {

                elem.on('click', function(event) {
                    var clickAction = caCtrl[attr.caOnClickElement];
                    clickAction(scope, elem, attr);
                });

            }
        };
    })
    .directive('caOnClickTargetElement', function() {
        return {
            restrict: 'A',
            bindToController: {
              handlers: '='
            },
            controllerAs: 'clickTarget',
            controller: function() {

                this.handlers = {
                    'addRow': {},
                    'alert': {}
                };

                this.registerElement = function(handler, caId) {
                    this.handlers[handler][caId] = [];
                };

                this.removeRow = function(caId, index) {
                    this.handlers.addRow[caId].splice(index, 1);
                };

            },
            link: function(scope, elem, attr, clickTarget) {

                clickTarget.registerElement(attr.caOnClickTargetHandler, attr.caId);

                scope.$on('onClickComplete', function (event, handler, target, data) {
                    clickTarget.handlers[handler][target].push(data);
                })

            }
        };
    })
    ;
