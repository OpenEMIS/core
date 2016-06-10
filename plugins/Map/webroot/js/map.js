
$(document).ready(function() {
    Map.init();
});

var Map = {
    gmap: '',
    iconStyles: [],
    markersByType: [],
    visibleMarkerTypes: [],
    init: function() {

        var config = {
            lat: $('#config .default .lat').data('value'),
            lng: $('#config .default .lng').data('value'),
            zoom: $('#config .default .zoom').data('value')
        };

        Map.gmap = new GMaps({
            el: '#map',
            lat: config.lat,
            lng: config.lng,
            zoom: config.zoom,
            streetViewControl: false,
            mapTypeControl: false
        });
        
        Map.markersByType = institutionsData;
        Map.setupIcons();
        Map.setupMarkers();
        $(".institution-type :checkbox").on("ifToggled", Map.redrawMarkers);
    },
    setupIcons: function () {

        $('#institution-types .institution-type').each(function(index, value) {

            var iconSettings = $(this).find('span i').data();

            if ($(this).data('typeCode') == 'default') {
                var fillColor = $(this).find('span i').css('color');
            } else {
                var fillColor = iconSettings.iconStyleFillColor;
            }
            // all markers will use the same icon but different color
            Map.iconStyles[$(this).data('typeCode')] = {
                path: fontawesome.markers.MAP_MARKER,
                scale: iconSettings.iconStyleScale,
                strokeWeight: iconSettings.iconStyleStrokeWeight,
                strokeColor: iconSettings.iconStyleStrokeColor,
                strokeOpacity: iconSettings.iconStyleStrokeOpacity,
                fillColor: fillColor,
                fillOpacity: iconSettings.iconStyleFillOpacity,
            }

        });

    },
    setupMarkers: function () {
        for (type in Map.markersByType) {
            Map.visibleMarkerTypes[type]=[];
            Map.addMarkersByType(type);            
        }
    },
    drawMarker: function (institution) {
        // if (institution.latitude != '' && institution.longitude != '') {

            var type = institution.institution_type_id;
            if (typeof Map.iconStyles[type] === 'undefined') {
                var icon = Map.iconStyles['default'];
            } else {
                var icon = Map.iconStyles[type];
            }
        
            var infoContent = $('#config .marker-body').clone();
            infoContent.find('.name').html(institution.name);
            infoContent.find('.code').html(institution.code);
            infoContent.find('a').prop( 'href', infoContent.find('a').prop('href') + '/' + institution.id );
            var marker = Map.gmap.addMarker({
                id: institution.code,
                lat: institution.latitude,
                lng: institution.longitude,
                name: institution.name,
                title: institution.name + ' (' +institution.code+ ')',
                animation: google.maps.Animation.DROP,
                infoWindow: {
                    content: infoContent.html()
                },
                icon: icon
            });

            return marker;
        // } else {
        //     return null;
        // }
    },
    redrawMarkers: function () {
        console.log('redrawing markers');

        var type = $(this).val();
        var checked = $(this).prop('checked');
        if (checked) {
            Map.toggleMarkersByType(type, true);
        } else {
            Map.toggleMarkersByType(type, false);
        }

    },
    toggleMarkersByType: function (type, show) {
        console.log('toggleMarkersByType: '+show);

        $.each(Map.visibleMarkerTypes[type], function(index, obj) {
            // obj.setVisible(show);

            // unable to use below method since
            if (show) {
                var map = Map.gmap.map;
            } else {
                var map = null;
            }
            obj.setMap(map);
        });
    },
    addMarkersByType: function (type) {
        console.log('addMarkersByType');

        for (record in Map.markersByType[type]) {
            
            var institution = Map.markersByType[type][record];
            if (institution.latitude != null && institution.longitude != null && institution.latitude !== false && institution.longitude !== false ) {

                var marker = Map.drawMarker(institution);
                if (marker != null) {
                    Map.visibleMarkerTypes[type].push(marker);
                }

            } else {
                // console.log(institution);
                // needs google console account to use this feature without daily limit
                // GMaps.geocode({
                //     address: institution.address + ' ' + institution.postal_code,
                //     callback: function(results, status) {
                //         console.log(status);
                //         if (status == 'OK') {
                //             var latlng = results[0].geometry.location;
                //             institution.latitude = latlng.lat();
                //             institution.longitude = latlng.lng();
                //             Map.drawMarker(institution);
                //         } else {
                            console.log(institution.name +' does not have the necessary data: '+institution.latitude);
                //         }
                //     }
                // });
            }

        }
    }

}

        
