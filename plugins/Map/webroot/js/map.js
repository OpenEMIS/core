
$(document).ready(function() {
    Map.init();
});

var Map = {
    gmap: '',
    iconStyles: [],
    markers: [],
    init: function() {

        var config = {
            lat: $('#config .default .lat').data('value'),
            lng: $('#config .default .lng').data('value'),
            zoom: $('#config .default .zoom').data('value')
        };

        this.gmap = new GMaps({
            el: '#map',
            lat: config.lat,
            lng: config.lng,
            zoom: config.zoom,
            streetViewControl: false,
            mapTypeControl: false
        });
        
        Map.markers = markers;
        // console.log(Map.markers);
        Map.setupIcons();
        Map.addMarkers();
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
                path: fontawesome.markers.UNIVERSITY,
                scale: iconSettings.iconStyleScale,
                strokeWeight: iconSettings.iconStyleStrokeWeight,
                strokeColor: iconSettings.iconStyleStrokeColor,
                strokeOpacity: iconSettings.iconStyleStrokeOpacity,
                fillColor: fillColor,
                fillOpacity: iconSettings.iconStyleFillOpacity,
            }

        });

    },
    addMarkers: function () {
        for (type in Map.markers) {
            // console.log(type);
            var typeSelected = $(':checkbox[value="'+ type +'"]').prop('checked');

            if (typeSelected) {
                for (record in Map.markers[type]) {
                    
                    var institution = Map.markers[type][record];
                    if (institution.latitude != null && institution.longitude != null && institution.latitude !== false && institution.longitude !== false ) {
                        Map.drawMarker(institution);
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
                // return false;
            }
        }
    },
    drawMarker: function (institution) {
        if (institution.latitude != '' && institution.longitude != '') {

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
            Map.gmap.addMarker({
                id: institution.code,
                lat: institution.latitude,
                lng: institution.longitude,
                title: institution.name,
                animation: google.maps.Animation.DROP,
                infoWindow: {
                    content: infoContent.html()
                },
                icon: icon
            });

        }
    },
    // addMarkers: function () {

    //     if (Map.markers.length == 0) {
    //         console.log();console.log($('#config .plugin-url').prop('href'));return false;
    //         $.ajax({
    //             type: 'GET',
    //             dataType: 'json',
    //             url: $('#config .plugin-url').prop('href') + '/getMarkersData',
    //             success: function(data, textStatus, jqXHR) {
    //                 console.log(data);

    //             },
    //             error: function(jqXHR, textStatus, errorThrown) {
                    
    //             }
    //         });

    //     } else {
    //         console.log('markers not empty');
    //         console.log(Map.markers);
    //         console.log(Map.markers.length);
    //         return false;
    //         // Map.drawMarkers();
    //     }

    // },
    oldAddMarkers: function () {
        
       $('#markers .marker').each(function(index, value) {

            var type = $(this).find('.type').html();
            var typeSelected = $(':checkbox[value="'+ type +'"]').prop('checked');

            if (typeSelected) {
                var lat = $(this).find('.latitude').html();
                var lng = $(this).find('.longitude').html();

                if (lat == '' || lng == '') {
                    GMaps.geocode({
                        address: $(this).find('.address').html() + ' ' + $(this).find('.postal_code').html(),
                        callback: function(results, status) {
                            if (status == 'OK') {
                                var latlng = results[0].geometry.location;
                                lat = latlng.lat();
                                lng = latlng.lng();
                            }
                        }
                    });
                }

                if (typeof Map.iconStyles[type] === 'undefined') {
                    var icon = Map.iconStyles['default'];
                } else {
                    var icon = Map.iconStyles[type];
                }

                var infoContent = $('#config .marker-body').clone();
                infoContent.find('.name').html($(this).find('.name').html());
                infoContent.find('.code').html($(this).find('.code').html());
                infoContent.find('a').prop( 'href', infoContent.find('a').prop('href') + '/' + $(this).data('id') );
                this.gmap.addMarker({
                    id: $(this).find('.code').html(),
                    lat: lat,
                    lng: lng,
                    title: $(this).find('.name').html(),
                    animation: google.maps.Animation.DROP,
                    infoWindow: {
                        content: infoContent.html()
                    },
                    icon: icon
                });
            }
            
        });

    },
    redrawMarkers: function () {
        console.log('redrawing markers');
        Map.gmap.removeMarkers();
        Map.addMarkers();
    }

}

