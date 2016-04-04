//Controller Action Angular Functions v.1.0.1
angular.module('caModule', ['kordit.service', 'ui.bootstrap'])
    .controller('caOnChangeCtrl', function(korditService) {

        var apiUrl = "/restful/";
        this.changeOptions = function(scope, element, attrs) {
            var targetUrl = apiUrl + attrs.caOnChangeSourceUrl + element.val();
            var response = korditService.ajax({url:targetUrl});
            response  
                .then(function(data) {
   
                    targetOptions = [];
                    for (var id in data.data) {
                        targetOptions.push({"id":id, "name":data.data[id]});
                    }
                    scope.$root.$broadcast('onChangeOptions', targetOptions);
                
                }, function(error) {
                    console.log('Failure...', error);
                });
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
            bindToController: {
              targetOptions: '='
            },
            controller: 'caOnChangeCtrl',
            controllerAs: 'viewVars',
            link: function(scope, element, attrs, caOnChangeCtrl) {

                element.on('change', function(event) {
                    caOnChangeCtrl.changeOptions(scope, element, attrs);
                });

            }
        };
    })
    .directive('caOnChangeTargetElement', function() {
        return {
            restrict: 'A',
            scope: {},
            bindToController: {
              targetOptions: '='
            },
            controller: 'caOnChangeCtrl',
            controllerAs: 'viewVars',
            templateUrl: '/assessment/templates/caOnChangeTarget.html',
            link: function(scope, element, attrs, caOnChangeCtrl) {

                scope.targetOptions = [];

                scope.$on('onChangeOptions', function (event, targetOptions) {
                    scope.targetOptions = targetOptions;
                })

            }
        };
    })
    ;
