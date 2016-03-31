//Controller Action Angular Functions v.1.0.1
angular.module('caModule', ['ngRoute', 'kordit.service'])
    .directive('ngCaOnChange', function() {
        // console.log('activated');
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
            scope: {
                // orientation: '@'
            },
            // template: '<div class="split-panes {{orientation}}" ng-transclude></div>',
            controller: ['$scope', '$http', '$routeParams', '$location', 'korditService',  function($scope, $http, $routeParams, $location, korditService) {
            // controller: ['$scope', '$http', '$location',  function($scope, $http, $location) {

                // var httpParams = ($location.absUrl()).split('/Loads/Publishes');
                // var indexUrl = httpParams[0] + '/Loads/Publishes';
                // var id = httpParams[1].replace('/edit/', '');
                // var gatewayUrl = indexUrl + '/gateway/' + id;

                // $scope.calculator.func = 'ajaxGetResults';//serialize
                // $http.post(gatewayUrl, korditService.serialize($scope.calculator), {
                //         headers: {
                //             'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8'
                //         }
                //     }).
                //     success(function(data, status, headers, config) {
                //         if (data!=null && typeof data.error !== 'undefined') {
                //             $('#alert-modal').find('.modal-body').html(data.error);
                //             $('#trigger-modal').trigger('click');
                //         } else if (data!=null) {
                //             if (!data.status) {
                //                 $scope.results = data;
                //                 $scope.spinnerWrapper = false;
                //             } else {
                //                 $scope.results = null;
                //                 $('#alert-modal').find('.modal-body').html(data.message);
                //                 $('#trigger-modal').trigger('click');
                //             }
                //         } else {
                //             $scope.results = null;
                //             $('#alert-modal').find('.close').trigger('click');
                //         }
                //     }).
                //     error(function(data, status, headers, config) {
                //         $('#alert-modal').find('.modal-body').html('<p>An error has occurred</p>');
                //     });


                $http.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
                var apiUrl = "/rest/";
            
                this.onChange = function(element, attrs) {
                    // console.log(element.val());
                    var targetUrl = apiUrl + attrs.caOnChangeSourceUrl + element.val();
                    console.log(targetUrl);
                    $http.get(targetUrl, {
                            params: {}
                        }).
                        success(function(data, status, headers, config) {
                            console.log(data);
                        }).
                        error(function(data, status, headers, config) {
                            console.log('error');
                        });

                    // return $scope.panes.length;
                };

            }],
            link: function(scope, element, attrs, controller) {

                element.on('change', function(event) {
                    controller.onChange(element, attrs);
                });

            }
        };
    })
    ;

    angular.element(document).ready(function() {
        // console.log('angular ready!');
        // angular.bootstrap(document, ['myApp']);
        // var $injector = angular.injector();
        // console.log($injector.get());
        // expect($injector.get('$injector')).toBe($injector);
        // expect($injector.invoke(function($injector) {
        //   return $injector;
        // })).toBe($injector);
    });
