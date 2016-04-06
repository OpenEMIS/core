//Controller Action Angular Functions v.1.0.1
angular.module('caModule', ['kordit.service', 'ui.bootstrap'])
    .controller('caModuleCtrl', function(korditService, $scope) {

        var ctrl = this;

        ctrl.onChangeTargets = {};

        ctrl.registerOnChangeTargets = function(caId) {
            ctrl.onChangeTargets[caId] = [];
        };

        $scope.$on('onChangeOptions', function (event, target, options, sourceAttr) {
            ctrl.onChangeTargets[target] = options;
            $scope.$broadcast('onChangeCompleteCallback', target, $scope, sourceAttr, ctrl);
        })

        ctrl.changeOptions = function(scope, id, attr) {
            var dataType = attr.caOnChangeElement;
            var target = attr.caOnChangeTarget;
            var targetUrl = attr.caOnChangeSourceUrl + id;
            var response = korditService.ajax({url:targetUrl});
            response  
                .then(function(data) {
   
                    targetOptions = [];
                    if (dataType=='data') {
                        targetOptions = data.data;
                    } else {
                        for (var id in data.data) {
                            targetOptions.push({"id":id, "name":data.data[id]});
                        }
                    }
                    scope.$emit('onChangeOptions', target, targetOptions, attr);
                    
                }, function(error) {
                    console.log('Failure...', error);
                });

        };

        ctrl.alert = function(scope, elem, attr) {
            alert('showing off');
        }

        ctrl.addRow = function(scope, elem, attr) {
            var target = attr.caOnClickTarget;
            var targetUrl = attr.caOnClickSourceUrl;
            var response = korditService.ajax({url:targetUrl});
            response  
                .then(function(data) {
                    scope.$root.$broadcast('onClickComplete', 'addRow', target, data.data);                
                }, function(error) {
                    console.log('Failure...', error);
                });
        };

        // $scope.onReadyFunction = korditService.onReadyFunction;

    })
    .directive('caOnClickElement', function() {
        return {
            restrict: 'A',
            scope: {},
            controller: 'caModuleCtrl',
            link: function(scope, elem, attr, caModuleCtrl) {

                elem.on('click', function(event) {
                    var clickAction = caModuleCtrl[attr.caOnClickElement];
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
    .directive('caOnChangeElement', function() {
        return {
            /**
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
             * 
             */
            restrict: 'A',
            scope: {},
            controller: 'caModuleCtrl',
            link: function(scope, elem, attr, caModuleCtrl) {

                elem.on('change', function(event) {
                    caModuleCtrl.changeOptions(scope, elem.val(), attr);
                });

            }
        };
    })
    .directive('caOnChangeTargetElement', function() {
        return {
            restrict: 'A',
            controller: 'caModuleCtrl',
            controllerAs: 'targetCtrl',
            templateUrl: function(elem, attr) {

                if (typeof attr.caOnChangeTargetElementTemplateUrl !== 'undefined') {
                    return attr.caOnChangeTargetElementTemplateUrl;
                } else {
                    // attr.caId;
                    return '/controller_action/templates/caOnChangeTarget.html';
                }

            },
            link: function(scope, elem, attr, targetCtrl) {

                targetCtrl.registerOnChangeTargets(attr.caId);

            }
        };
    })
    ;
