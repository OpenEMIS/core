angular
    .module('map.ctrl', ['utils.svc', , 'map.svc'])
    .controller('MapCtrl', MapController);

MapController.$inject = ['$scope', '$q', 'UtilsSvc', 'MapSvc'];

function MapController($scope, $q, UtilsSvc, MapSvc) {
    $scope.mapReady = false;

    // Functions
    $scope.initMap = initMap;

    // Initialisation
    angular.element(document).ready(function() {
        MapSvc.init(angular.baseUrl);

        UtilsSvc.isAppendSpinner(true, 'map-group-cluster');
        MapSvc.getMapConfig()
        .then(function(response) {
            $scope.initMap(response);
            $scope.mapReady = true;

            return MapSvc.getMapData();
        }, function(error) {
            // No Map Config
            console.log(error);
        })
        .then(function(response) {
            $scope.mapData = response;
        }, function(error) {
            // No Map Data
            console.log(error);
        })
        .finally(function() {
            UtilsSvc.isAppendSpinner(false, 'map-group-cluster');
        });
    });

    function initMap(response) {
        var zoomValue = response[0];
        var centerLongitude = response[1];
        var centerLatitude = response[2];

        $scope.mapConfig = {
            zoom: {
                value: zoomValue,
                isZoomButton: true,
                isScrollZoom: true,
                isTouchZoom: true,
            },
            attribution: 'OpenEMIS',
            type: 'group-cluster',
            legend: {
                title: {
                    text: 'Institution Types'
                }
            }
        };

        $scope.mapPosition = {
            lng: centerLongitude,
            lat: centerLatitude
        };

        $scope.mapData = {};
    }
}
