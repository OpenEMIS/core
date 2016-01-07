$(document).ready(function() {
    Map.init();
});

var Map = {
    gmap: '',
    init: function() {
        var config = {
            lat: 1.3589855,
            lng: 103.8317706,
            zoom: 19
        };
        var lat = $($('#markers .marker')[0]).find('.latitude').html();
        var lng = $($('#markers .marker')[0]).find('.longitude').html();
        if (typeof(lat) !== 'undefined' && typeof(lng) !== 'undefined') {
            config.lat = lat;
            config.lng = lng;
            config.zoom = 12;
        }

        Map.gmap = new GMaps({
            el: '#map',
            lat: config.lat,
            lng: config.lng,
            zoom: config.zoom,
            streetViewControl: false,
            mapTypeControl: false
        });
        Map.addMarkers();
        // Map.addMarker();
    },
    addMarkers: function () {

        $('#markers .marker').each(function(index, value) {

            var lat = $(this).find('.latitude').html();
            var lng = $(this).find('.longitude').html();

            if (lat != '' && lng != '') {
                Map.gmap.addMarker({
                    lat: $(this).find('.latitude').html(),
                    lng: $(this).find('.longitude').html(),
                    title: $(this).find('.name').html(),
                    animation: google.maps.Animation.DROP,
                    infoWindow: {
                        content: '<p>'+ $(this).find('.name').html() +' ('+ $(this).find('.code').html() +')</p><br/><a href="/Institutions/view/'+ $(this).attr('id') +'">View Details</a>'
                    },
                    icon: {
                        path: fontawesome.markers.UNIVERSITY,
                        scale: 0.5,
                        strokeWeight: 0.2,
                        strokeColor: 'black',
                        strokeOpacity: 1,
                        fillColor: '#888888',
                        fillOpacity: 0.8,
                    }
                });
            }

        });

    },
    addMarker: function (obj) {
        Map.gmap.addMarker({
            lat: -12.043333,
            lng: -77.03,
            title: 'Lima',
            // icon: 'http://<url>/gmap.ico',
            animation: google.maps.Animation.DROP,
            details: {
                database_id: 42,
                author: 'HPNeo'
            },
            click: function(e){
            //     if(console.log)
            //         console.log(e);
            //     alert('You clicked in this marker');
            },
            mouseover: function(e){
                // if(console.log)
                //     console.log(e);
            },
            infoWindow: {
                content: '<p>HTML Content</p>'
            }
        });
    }
}

    // map.addMarker({
    //     lat: -12.042,
    //     lng: -77.028333,
    //     title: 'Marker with InfoWindow',
    //     infoWindow: {
    //         content: '<p>HTML Content</p>'
    //     }
    // });

