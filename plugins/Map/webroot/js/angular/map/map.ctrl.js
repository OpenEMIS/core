angular
    .module('map.ctrl', ['utils.svc', , 'map.svc'])
    .controller('MapCtrl', MapController);

MapController.$inject = ['$scope', '$q', 'UtilsSvc', 'MapSvc'];

function MapController($scope, $q, UtilsSvc, MapSvc) {
    var vm = this;

    // Variables
    vm.mapConfig = {
        zoom: {
            value: 14,
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
    vm.mapPosition = {};
    vm.mapData = {};

    // Functions
    vm.initMap = initMap;

    // Initialisation
    angular.element(document).ready(function() {
        MapSvc.init(angular.baseUrl);

        vm.initMap();
    });

    function initMap() {
    }
}
