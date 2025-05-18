angular
    .module('map.ctrl', ['utils.svc' , 'map.svc'])
    .controller('MapCtrl', MapController);

MapController.$inject = ['$scope', '$q', 'UtilsSvc', 'MapSvc'];

function MapController($scope, $q, UtilsSvc, MapSvc) {
    $scope.mapReady = false;

    // Functions
    $scope.textConfig = {
        institutionTypes: 'Institution Types'
    };
    $scope.initMap = initMap;

    // Initialisation
    angular.element(document).ready(function() {
        MapSvc.init(angular.baseUrl);

        UtilsSvc.isAppendSpinner(true, 'map-group-cluster');
        MapSvc.translate($scope.textConfig)
        .then(function(response) {
            $scope.textConfig = response;
            
            return MapSvc.getMapConfig();
        }, function(error) {
            // No translation data
            console.log(error);
        })
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
        console.log("initMap");
        console.log(response);
        var zoomValue = response[0].toString();
        var centerLongitude = response[1].toString();
        var centerLatitude = response[2].toString();

        var legendTitle = $scope.textConfig;

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
                    text: legendTitle
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
