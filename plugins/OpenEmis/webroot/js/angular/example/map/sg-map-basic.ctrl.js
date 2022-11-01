//Map - Basic v.1.0.0
(function() {
    'use strict';

    angular.module(APP_CONFIGS.ngApp)
        .controller('SgMapBasicCtrl', ['$scope','$window',  function($scope, $window) {
    
            $scope.mapConfig = {
                errorMessage: 'Both latitude and longitude value have to be set for map to render',
                zoom: {
                    value: 14,
                    isZoomButton: true,
                    isScrollZoom: true,
                    isTouchZoom: true,
                },
                attribution: 'OpenEMIS',
                googleLink: {
                    display: true,
                    position: 'top-left'
                },
                marker: {
                    title: 'My Institution'
                },
                legend: {
                    title: {
                        text: 'Institution\'s Groups'
                    }
                },
                content: 'This is my institution.'
            };

            $scope.mapPosition = {
                lat: 1.2842,
                lng: 103.8511
            };

        }]);
})();
