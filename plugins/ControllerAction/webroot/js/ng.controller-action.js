//Controller Action Angular Functions v.1.0.1
angular.module('caModule', ['kordit.service', 'ui.bootstrap'])
    .controller('caModuleCtrl', function(korditService) {

        this.changeOptions = function(scope, elem, attr) {
            var dataType = attr.caOnChangeElement;
            var target = attr.caOnChangeTarget;
            var targetUrl = attr.caOnChangeSourceUrl + elem.val();
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
                    scope.$root.$broadcast('onChangeOptions', target, targetOptions);
                
                }, function(error) {
                    console.log('Failure...', error);
                });
        };

        this.alert = function(scope, elem, attr) {
            alert('showing off');
        }

        this.addRow = function(scope, elem, attr) {
            // console.log('adding row');
            var target = attr.caOnClickTarget;
            var targetUrl = attr.caOnClickSourceUrl;// + elem.val();
            var response = korditService.ajax({url:targetUrl});
            response  
                .then(function(data) {
                    // console.log(data.data);
                    scope.$root.$broadcast('onClickComplete', 'addRow', target, data.data);
                
                }, function(error) {
                    console.log('Failure...', error);
                });
                
        };

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
                    caModuleCtrl.changeOptions(scope, elem, attr);
                });

            }
        };
    })
    .directive('caOnChangeTargetElement', function() {
        return {
            restrict: 'A',
            bindToController: {
              elements: '='
            },
            controllerAs: 'changeTarget',
            controller: function() {

                this.elements = {};

                this.registerElement = function(caId) {
                    this.elements[caId] = [];
                };

            },
            templateUrl: function(elem, attr) {

                if (typeof attr.caOnChangeTargetElementTemplateUrl !== 'undefined') {
                    return attr.caOnChangeTargetElementTemplateUrl;
                } else {
                    // attr.caId;
                    return '/controller_action/templates/caOnChangeTarget.html';
                }

            },
            link: function(scope, elem, attr, changeTarget) {

                changeTarget.registerElement(attr.caId);

                scope.$on('onChangeOptions', function (event, target, options) {
                    changeTarget.elements[target] = options;
                })

            }
        };
    })
    ;
