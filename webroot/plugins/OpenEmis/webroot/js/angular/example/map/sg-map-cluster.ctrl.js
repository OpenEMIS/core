//Map - Cluster v.1.0.0
(function() {
    'use strict';

    angular.module(APP_CONFIGS.ngApp)
        .controller('SgMapClusterCtrl', ['$scope', '$window', '$timeout', function($scope, $window, $timeout) {
            console.log('loading map data');

            
            $scope.mapConfig = {
                zoom: {
                    value: 14,
                    isZoomButton: true,
                    isScrollZoom: true,
                    isTouchZoom: true,
                },
                attribution: 'OpenEMIS',
                type: 'cluster',
                legend: {
                    title: {
                        text: 'Institution\'s Groups'
                    }
                }
            };

            $scope.mapConfig2 = {
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
                        text: 'Institution\'s Groups'
                    }
                }
            };
            $scope.mapData = {};
            $scope.mapPosition = {
                lat: 1.2842,
                lng: 103.8511
            };

            $timeout( getDataFromServer, 13000 );

            function getDataFromServer() {
                console.log('loading data from server');
                $scope.mapData = {
                    group_1: {
                        data: [{
                                id: 'eyJpZCI6IjEiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.OTcyMjE5YTAyNWJiZGRiOGI4ZWJiNzJkYjQxNGI5N2Q5M2MzNzZiNDJmYzgyODZjNGViNTBiODMzODBhZGQ3OQ',
                                lat: 1.42182547,
                                lng: 103.8134033,
                                content: 'Abacus Basic School<br/>ABS66538<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.MGFlNGVlMmJkMzFmZGRmZmE4YzEwNDdkOWUzYmZkZjI4ZTk4ZmU2MDIwODg0NTlkNWFiZTUxZTk5YjdlZmRiNw',
                                lat: 1.23738734,
                                lng: 103.9376209,
                                content: 'Acorn School<br/>AS49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjkiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.YTZhNjU2YTlhY2M1OTc5YTUxNTgzZjk5Mjc4MTZkYTI5YjZiNzM1Yjc2YTJhMjU3Y2I3ZWYxZjU3ZWE5MjRiYQ',
                                lat: 1.40298797,
                                lng: 103.7962893,
                                content: 'Arabic Open School<br/>AOS62756<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NDRlMzI4MTRjMzE5MDVjNzg2MTliMzYxMDY3YjE5ODRiMjJkMzM2MTdmYTI3MWUzNWEwNzQxZGY1NmZkNTIxNw',
                                lat: 1.23389462,
                                lng: 103.8525403,
                                content: 'Atemkit Pre-School<br/>AP51009<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MmE5NjczMzc5ZjUzMjBhNjVlOTAyMGRmOGYzYjVkZjRjMjg1ZTExNDA2ZGFmMDJiNjU0NTVkZWEyZmQ2YjU3Yg',
                                lat: 1.22464106,
                                lng: 103.7652679,
                                content: 'Auberdeen School<br/>AS11822<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZmQ0Y2IwMGRmNjNkYTBmYjg5YjcxZDEzNGE4YTkwYjZlOWQ2ODdlMjEzNmZiNTA3MWY4N2I2YTQzMDc4MTJiZA',
                                lat: 1.50036237,
                                lng: 103.7325883,
                                content: 'Bennington School<br/>BS43419<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzEyNDMwMzhiYjA3Y2FhOTA0YmJhYjA5Mjk4Y2U5NWRkZjZlNjg2MDM2MGMxYjRmNmYyMjFhMGFkYzE3ZGI0Nw',
                                lat: 1.43482585,
                                lng: 103.727212,
                                content: 'Blacksmith School<br/>BS55111<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzMwMGU0OTM2N2NjMDViOWE1YjE5ZDNmNDI0MTJhYmZlZWU2MDE3NDJmMDEzZDE5NjMyN2RmMjBhOGEwMzA5OQ',
                                lat: 1.34482402,
                                lng: 103.6745789,
                                content: 'Bridgewood School<br/>BS31722<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODRjYzQwNjY5ODQ4YzZlMzU0MTI3ODdjYmY2NDhhMjUzZjU3NWNhMGM0MDVhNGFiMmI2NTQwYTRkMDY1Nzc0NA',
                                lat: 1.38164692,
                                lng: 103.8849473,
                                content: 'California Graduate School of Theology<br/>CGS49884<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YWQxZDNhZWYxNmUwYTUwYzM0MjBhNmI0N2Q3YTc1MjFlMjNkMGEzYmVmNjE1ZTg4NTU3MjUzMmRkZWVjMGVmYw',
                                lat: 1.49382685,
                                lng: 103.7212323,
                                content: 'Canterbury School<br/>CS49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OGE5ZjBjNDM4NWZjODI3MjQ5MTk4YzQ1ODA3MjY3NGEyNmNlNDg5Njg2NTZkMGJlZjNkMGY1YTE0YWMxYzIyNA',
                                lat: 1.46582297,
                                lng: 103.8079474,
                                content: 'Charis School of Divinity<br/>CSo58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjcxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OTRlNDFkNjgxZjY5NGM4OGI3YjNmMDYyYzAyYTZjNDhhZDVjYTQyYTNkNmJkNmE2ZDRiMzU5MWFlZjE4YzQ1Yg',
                                lat: 1.47647575,
                                lng: 103.8863824,
                                content: 'Clayton School<br/>CS63323<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjgxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzkwZmE1MzUyZmFhYjU4NzhiZmMyN2NkNTQ4MDE1ZjFhMjQ4ZGZhODM1MzU2NDFjOTNiN2RhZDg1ZGNkYWE3ZA',
                                lat: 1.24188638,
                                lng: 103.8688654,
                                content: 'Colton School<br/>CS21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MzU5Zjc5YjYzNDcwMzkzNWMwOWEzMjAzMDhjZWU4YzE1Zjc0NTAyZGEzNWFlNWVjMjc4NzRlYTkxM2M4NjE5OQ',
                                lat: 1.43909167,
                                lng: 103.9327044,
                                content: 'Corlins School<br/>CS37808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NmJkNWNiNzBlODc2NGU4MmRlNmQ5ZWFiZmU3ODAzZWUwZjlhMWQzMjE3NmIyYTc0YjFjOTZiNWNjOWM0OGE2ZA',
                                lat: 1.28639867,
                                lng: 103.8045779,
                                content: 'Dorcas School<br/>DS51141<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTIxNDVmNzM1ZDBiYmM3ZjliYmY4YTRmYjRmZWUwZDU3Y2E3NDRlZTBiMzgxZmYyNjU4ZDgwZjZjMTUxNjRiNA',
                                lat: 1.22864034,
                                lng: 103.8233186,
                                content: 'East Point School<br/>EPS55910<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmQ2MjRkYzQ3MGQ1M2I4NWVjNjA5M2RhYmQwNmMyNjE4OTBlMjU5NTJlOTEwMzdmMmE5NGZlMDEwMjNhMTk4NA',
                                lat: 1.30954537,
                                lng: 103.7491721,
                                content: 'Ellington School<br/>ES24<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTM5Njk2NmY0MzQxMGEyNzE4MWE2MzE4NjVjYjQwZWRmNWM3OTZiYzYwMDI0OGU5NGI5YTUwNzI3ZWY3YTVmYQ',
                                lat: 1.41731756,
                                lng: 103.6902261,
                                content: 'European Open School<br/>EOS68788<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDRhZDA4MmVlYWE5NWVlNDQ5MzliNzU4Y2JmNTU3NmEwNjRjZGRkMTU2MTJiODBhOWFhYjViMzY1M2U4MzJiOQ',
                                lat: 1.38080837,
                                lng: 103.6776773,
                                content: 'Farington School<br/>FS54<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzFhYjQxZGRmZjI1MjMwMWQwNTk3NTUzYTA4OGZmMmRjM2VlMGI0OWEwZGYwN2JkMDlkZTZhMzFkZDAzNGY5Ng',
                                lat: 1.36695309,
                                lng: 103.9150906,
                                content: 'Geo-Metaphysical Institute<br/>GI42546<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2ZhNDRiYzMxY2QyYzcyMGRhZjQwYjY3YzQxMjhhM2VlNjJjODE1MmFiYWNlYWUwNmI0YjQ2NDYyMTQ4YTFjNw',
                                lat: 1.26791957,
                                lng: 103.9435083,
                                content: 'Golden State High School<br/>GSH173<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODhmMmEzZDU4MjlmYmM2YTNmMDZlOTM2MGEwOTczNTAxMTA0ZTcxMDg0ZWQ5MjJjNTY0YTUyNjQ4M2JhN2M4Nw',
                                lat: 1.42588794,
                                lng: 103.8302264,
                                content: 'Guriaso Pre-School<br/>GP24898<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzMxYjEzYWZkNTAwOGRhMzhjNDE2M2YwYTI3ZjU2YzcyNDhiODNiMDY5OWQyYmQyZDgyOTEzZmE4ZmQwZDhiMA',
                                lat: 1.41738859,
                                lng: 103.7527237,
                                content: 'Hartford School<br/>HS36800<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGY2NTAzODAwMDhjMTU4ODEwYmVmYTcxZjhmMzg0MWQyOTNmOWJkNjk2MTE5MjQ2MmFkZjQwZTljY2U2NTZjZg',
                                lat: 1.49387151,
                                lng: 103.7917268,
                                content: 'Hill School<br/>HS406<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDI5NDRjZTJhNjFhNGQyMDMyOTRjOTU5MzVlOTA4ODAxZmY0YjJlZWU4ZWIyN2QyY2MxZWUyN2UxMGE2ZDg0YQ',
                                lat: 1.42960201,
                                lng: 103.8341378,
                                content: 'Indian Institute of Planning and Management<br/>IIo21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTBmNGNiOGU1ODQzMzZmMzQzMjZiYzIyNGU5YWViNDhjYzNjNWViMTgwZGYzNGQ1ZDM0MzA0ZGMzNjRjMWNmMA',
                                lat: 1.32045083,
                                lng: 103.9167008,
                                content: 'International High School<br/>IHS23635<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTQ1Nzk0MDJjOWVhMWU1YWU0Y2Y2NGQ4ZTQ4MDMzYzY3Y2FjMTIxY2UxNzFlZmVhODI0ZDZhYTc3OWMzYzM4Zg',
                                lat: 1.44652214,
                                lng: 103.8631938,
                                content: 'Issi Pre-School<br/>IP41250<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTFkZTQ3YzJhMmJkMmY3Y2RmYjQ2YWMwZTgyY2QyODE4NjM5MWM1MDY1NWMxMjIxODdkYTQ1YTUwYjNmZmZmMA',
                                lat: 1.39116307,
                                lng: 103.9153646,
                                content: 'Jacksonville Theological Seminary<br/>JTS42587<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDFhODcwMmRjODlkNWUxZmRlMmMxMTJjMDUxMzM0ZWM5NmMxNzcyMTI3MmVkMjJiMzU3NGNiMjgyMGU5YmQyOA',
                                lat: 1.52290962,
                                lng: 103.8345386,
                                content: 'Kalem Pre-School<br/>KP54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTkxYTA4ZGEzNjNkNzNkNzY0YmM5NzA2ZjdiMWZkZTY1ZmRlODk3OGE4ZDAyMDY1MTIyZGYwZDQwYzZiMjVlZg',
                                lat: 1.28388495,
                                lng: 103.9101765,
                                content: 'Kingston School<br/>KS55143<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzUyMjMxYWM1YmYyMzc3ZGJlYThjMDcwMDJjMTU4Y2U0MzAyYzRkODhmNDU0NDVmNGFlZmY2ZDM1NDY1ODRmNQ',
                                lat: 1.44052704,
                                lng: 103.7006293,
                                content: 'Klelbuf Pre-School<br/>KP60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDI2ZDYxNTg2YmM3ZGY2M2U1YmRlNjhkOGU5ODEyNTIyMTFmZDlmZDJhOGFkNWIzOTEwYjJhYmYzZGIzNGU4Zg',
                                lat: 1.47251492,
                                lng: 103.7905664,
                                content: 'Lake Campbell Pre-School<br/>LCP68084<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODgzMzQ0MWNiODNmYWY0ZTc5MjM2NTY0NjVlYzEyZDdhYzlmZGRlMGNlYjUyMWY2OTEyNzI3NjNlNWNhMGRkYQ',
                                lat: 1.36310945,
                                lng: 103.9249874,
                                content: 'LaSalle School<br/>LS50458<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjFlMDM3NGYyNTQ2ZTlkNjUxMGZmMmJmMzFjZTI2NjBkMTMyYmMzYzVlYjM3ZWJhNWQwZjc0YWRjNDc2NzYxNw',
                                lat: 1.26334225,
                                lng: 103.7544448,
                                content: 'Lobi Business School<br/>LBS49738<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGFjZjg1OTNkMTBjNWI1MmFhMWRmODFkNzdhM2FmZDlkMzEzYTljYjZlODdlYWU2ZmM4M2QyZjMxMDM4ZTAyOA',
                                lat: 1.31388146,
                                lng: 103.7424539,
                                content: 'Lumi High School<br/>LHS50114<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmJkM2FkOTNmZWI4YTA1MWViYTVmMDI4YzIxYjJkZjU3ZTMwYmI5NmJmNDMyMzA4ODg5NWMzMDY0ZmIyMDYwZA',
                                lat: 1.41273577,
                                lng: 103.6988783,
                                content: 'Management Institute of Canada<br/>MIo173<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmVkMDA1NjJhOTQ3MTA2MjNmNmYyYjM0OGFkMjE0N2M0Mjk0ZGUyNTBmM2Q0OWE3MTdlMmNkOTdjNDliYjRhYg',
                                lat: 1.18706577,
                                lng: 103.8710875,
                                content: 'McFord School<br/>MS49035<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTlkNGIxNzVkYzJmOWNlY2YxMWFmNWJlZGI3YjlkZjc3NzU0YmJlNDkwMGVhNzY3NmZjMTE0OGZlY2Q2OGQ3OQ',
                                lat: 1.35584493,
                                lng: 103.8564382,
                                content: 'Mimbite Pre-School<br/>MP10096<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2I5MDZmOTkwMTlhODBjZTUzNWM3N2M0OGY4NjI0NWM4NTJmZDRhNWY5ZTM5MmRkMWU1NDk0ZjVkMjBiODM5ZA',
                                lat: 1.43956181,
                                lng: 103.7568037,
                                content: 'Monticello School<br/>MS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjVmYjE3MDUxYTNmZTQ2MDkyOTljZjU1NjY2YmY2YmE4YTI1MmJhZDliZjU2MDU4MGI4ZWU2NWU3NzM5MTY1MQ',
                                lat: 1.39807675,
                                lng: 103.7708564,
                                content: 'Nobel School<br/>NS37000<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjRiM2EwMWNkMWYxOGM1MjgxMmZjMGNkM2IyYTNkNTI0YzY5YTNhMjY1ODc5OWZkYjY5OGI0OTJmN2RhYmEwYQ',
                                lat: 1.4012277,
                                lng: 103.9575215,
                                content: 'Northfield School<br/>NS13668<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODhlZDcxZWQyYjgzZGUwN2M5YTEzODkzMjQ0ODAyNWYxYmIxYzI0Njk0NTE4NzIzM2VlYzA1NjdiZTI1YjQyOA',
                                lat: 1.46205574,
                                lng: 103.8952297,
                                content: 'Panworld School<br/>PS68180<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTc0NGU5ZTc3N2ExMDZkMWVkNTUyYmQ3NjAzZTBhMzYyZjhhMjM0MzM5ZWM3NTk0MDE5OTFkOTY2YzczOTNlNw',
                                lat: 1.26888643,
                                lng: 103.8328277,
                                content: 'Pindiu Agro Technical High School<br/>PAT42192<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmYwM2U4Yjk5NGEwYmM4NjZmMzMxMmU1ZTBkYzU4NjgxN2RlMzc0OGQ4MDBjYmZkNGMzZWNiZjdiNWI1ZjhhYQ',
                                lat: 1.46451583,
                                lng: 103.9222773,
                                content: 'Preston School<br/>PS169<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmJjM2FmYTM5NWVkNTdiMTllYTViYzVkZjk1Yjk0NDU0ZDU0OGVmODU0MmRkNThhNDFmYzlhMmQzZTY2YTdlMQ',
                                lat: 1.39252795,
                                lng: 103.8299952,
                                content: 'Raja Arabic School<br/>RAS63654<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2FiY2U0NDhjYWEzMjAyZjI5YWU4NWU3YzExYTUzZGI4YWMyNzJjYmMxOTc3ZWY4MjRjY2I5ODJmZWZmM2YxNA',
                                lat: 1.23228889,
                                lng: 103.7031088,
                                content: 'Richmond Open High School<br/>ROH51060<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2M1MmMyZmVhNjE5MGFmM2QyZDI5Zjk2MjM4ZWE3MWEzMjBmYzIyMWRiNTVjYzAxOTJmZDVhYTgwYzJmYzUxMA',
                                lat: 1.26339198,
                                lng: 103.937476,
                                content: 'Royal School Izhia<br/>RSI63304<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmFkMWNkY2RjZTBiMzYwZDhiMjY2ZjkxNThjZThjNzEyODAzNTU0MzdjMTQ3YzAxNGNiMDVkMWNlYzllMzE5Yw',
                                lat: 1.40383658,
                                lng: 103.6907499,
                                content: 'Sancta Sophia Seminary<br/>SSS703<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjcyNTcwZjdjZTUxZDUxNDFkNWRjY2Q2ZWIxNzZlMzYxZDBmZTQ5NzYzMTFkYTY5Zjg5MWFkMzhlYzAwYzBjYQ',
                                lat: 1.40954302,
                                lng: 103.8404579,
                                content: 'School of Devon<br/>SoD59054<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzJiN2M5MTBkNWU0ZDRmYzA4Y2JmNzUyMTQ5ZWM3NDhmNDRhMzQ2YjgxMDU3YTU4NDI1NDgzZmU0ZjBlODFmYQ',
                                lat: 1.3440413,
                                lng: 103.8175596,
                                content: 'School of Redwood<br/>SoR41595<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTNkZDU5OTA2NTIzNzljMmI5YTIyYmUzN2U5Y2QwYTgzOGQzYmVmMjhjMGRlMjA3MTFiNDUxYTJlMmEwMGE4Zg',
                                lat: 1.38937769,
                                lng: 103.9381867,
                                content: 'Sequoia School<br/>SS781<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzZiM2RiNTQ3NzliZDdkZWI3ZTc0MDYzMTZjNDAyMTU4ZTc0MDhjMjQyNzFiMmM5N2M2YjhkOWJkMjUyZjNkMQ',
                                lat: 1.38753564,
                                lng: 103.975429,
                                content: 'Standford School<br/>SS49288<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjRmN2U0YTVjOWNjOGQ5YzQ5MWI0NWE1OTQ2Y2ZkZGVkOWVjMDU1NmE1MmQ0OGI3M2QxYTdjNTEwN2NjMTYwZQ',
                                lat: 1.44791116,
                                lng: 103.7076136,
                                content: 'Suffield School<br/>SS15408<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmMxNTQ2NjI2YTgzZDA5YjEzNzhmNjY1YzdiNWU3N2EyYjY0MWJmNTMyMjBiMjllOWMzM2M0N2I5YTI5MTEzMw',
                                lat: 1.3129816,
                                lng: 103.8024484,
                                content: 'Tecana School<br/>TS55118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2U0MDQ0NTVhMDdlOTA4ODI2MTgxMzkyNjI4OTJhMTYxNDdkNTYyYzQ4ZWM2YjAyYmIxOWIzZjNmMmRhM2UwOQ',
                                lat: 1.28945255,
                                lng: 103.7192741,
                                content: 'Therapon School<br/>TS54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2ZiODY5ZmQ2NGYyNWI5OTExOTQwNjg3ZDRlZmQ0ZjQ1MmVlMWQ1OWZkOTJjZDcwY2Y2M2ZiNTY3MDAwY2U4Yw',
                                lat: 1.34820246,
                                lng: 103.8634615,
                                content: 'Thornhill School<br/>TS67457<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTBiOWQ3MmUwNjMyMmI1YTRmMzNjODQ0MmQyYmI5ZDdkMmVkNWY3OTI1MjIxNTQwZjE2NzYyNGQ0YzdjNmI5ZA',
                                lat: 1.4472101,
                                lng: 103.7356502,
                                content: 'Tyndale Theological Seminary<br/>TTS45737<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YThlNmRkYjZmNjMxZjAwOGMwNTQwMjIyOGJlNWJkY2Y4ZDA5M2QyMmIzOTU2Yzc0NDc4YzE1NGU5ZGJhOTcxNg',
                                lat: 1.27921963,
                                lng: 103.9814483,
                                content: 'Universit Europenne Jean Monnet a.i.s.b.l<br/>UEJ60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmU4NWMxMDM5N2RkZWU1MTFhZWQzYjE1ZTdiYTk3ZWY1ZTNiOGQ3NjE5MjBjOTExOTRiZWZjMjQwZWYzMzlkZQ',
                                lat: 1.41507349,
                                lng: 103.8489936,
                                content: 'Valley School<br/>VS64193<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzBkNTdlMWQyZGRmMWMwMGY1OGYyZWU2MmVlZGEzOWZjZjlmMWM5ZTk3ODc4ZGMwM2UwZTAzNDc4NzVmZTZmYw',
                                lat: 1.40636165,
                                lng: 103.8852783,
                                content: 'Wareho Pre-School<br/>WP36800<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjQ2MWEyMTY1YzdlMmEwMjNhM2U1ZjE3MDA1NmUzMGY1N2M3ZDE2Y2JiMjdlNDMyOTljMGI4MDBkMTYyNzg0OA',
                                lat: 1.44071271,
                                lng: 103.7787897,
                                content: 'Wassisi Pre-School<br/>WP58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzkzNjU2MTU5N2E5NjNkMDY5NWQ1OGVmYjgxNjkyZDNiY2YzYmUxZjg3NzE3NmJlZGYyYmNiM2UxYWE0YmJlYw',
                                lat: 1.47379608,
                                lng: 103.8350386,
                                content: 'Wasu High School<br/>WHS362<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWU4NDFmYjhhZDNhNDliYmFiMTM3MTgyYmRlZTQ0OTk2NzVhMDZmMWNiM2Y5NWNjZDZjMjBmMzBlM2VjYjMwYQ',
                                lat: 1.42031634,
                                lng: 103.8243481,
                                content: 'Williamsburg School<br/>WS68220<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGI1MzVmYmRiMjM0ZjI0ZjIxMWVjZDA0NzI0NjRlOTkyZDFlZjAxYzg3YjQzYTMxNzhmMTlkZjI1MDNhMWVjOQ',
                                lat: 1.30755012,
                                lng: 103.765069,
                                content: 'Worchester School<br/>WS43269<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGM3N2M5OTY0NWRiZmI5NzdhODI1MTQyMGJhN2JjZDQ1YTVkOTEyODA1ZDdjNjAzYjE0NmRmNzZiYjU2MDViMw',
                                lat: 1.37486487,
                                lng: 103.6646983,
                                content: 'Zenith School<br/>ZS55289<br/><a href="http://www.google.com" target="_new">link</a>'
                            }
                        ],
                        marker: {
                            icon: 'university',
                            markerColor: 'darkred',
                            prefix: 'fa',
                            iconColor: 'white',
                            title: 'Group 1',
                            id: "group_1"
                        }
                    },
                    group_2: {
                        data: [{
                                id: 'eyJpZCI6IjIiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.ZmQ4NzBhOGUyMTc5YTU2YzFjNmZmOGFmZjQwY2VjYWRjM2FhZTcwNzUxMzZkN2EwYmZkNWIxMzhhYzE1MmZkMA',
                                lat: 1.28553388,
                                lng: 103.8374022,
                                content: 'Aben Park Primary<br/>APIU85<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.ZTU0ZDFhMGYyOWRjMjgxMTM0Yjc4MjE0MmFiNmYwMzg5OTBmNWUzNTQxNTNkNmY4MTIwMDMwYTRlZDAyODc5NQ',
                                lat: 1.38079318,
                                lng: 103.6688132,
                                content: 'Ambai School<br/>AS67457<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MzlhNDc4MDI2MWExMzdjZWVkOTIyNmFlY2Q4NjRiZGNjZDllOTNiZmE1NGY3ZTQzMjdhZDEzYjdjMWQ3ZWZhZQ',
                                lat: 1.49158863,
                                lng: 103.88464,
                                content: 'Ashford School<br/>AS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MTRmZmIwMGZmMjRjYTU4NzkxYmViYWVhYzI2NWRmYjk3NDFjNjU3YmUxZDhlMDI5ODM1NDU1NTVmYzhlMmE1Nw',
                                lat: 1.39678997,
                                lng: 103.8692069,
                                content: 'Atlanta School<br/>AS68327<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODk0ZjNhNWRmNDY4N2E1ZTU3MDIyNWMwMDllZjFjMGJiNWJkMjMwOGI1MWJmOWY3YmEzZGUzMGY4ODMyYTYyOA',
                                lat: 1.3358045,
                                lng: 103.8171425,
                                content: 'Baibari Primary School<br/>BPS53407<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OTBkM2QyODUyZmIzOTQwYTJjNDQ3MjdhYTAwZGYwN2E2Mzk5MjcxMThkMmRkZTkyNTNiMmI2NjBjYzMwYmM4Mg',
                                lat: 1.36450073,
                                lng: 103.6718337,
                                content: 'Beloved Community Seminary<br/>BCS51346<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.N2FjYWIwMjM0MzA3N2M3ZjBhMmJlNzEyYjcwOTczNGQ3YTY1MGQxNTdjMzQwMjQyMmZmZGM2Mzg1YTkzMDY0Yg',
                                lat: 1.4064549,
                                lng: 103.8355661,
                                content: 'Blackpool School<br/>BS11673<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzhmZThmN2YxNTc2OWI5ODA4Y2Y5MGE2MzBmMWFkODEwZjVjNmJiM2RjNDliZjYwNzZmYzJjNjljZWY5NWIyYg',
                                lat: 1.3753534,
                                lng: 103.6775655,
                                content: 'Bridgewater School<br/>BS55289<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NWIyMzYwYjAzOGYyODVlZGU5MjA4ZTIwNDYyODEwZGY2ZTg2MWNjZGE5NzhmMGNlYjc5NzZiMGRiNjUyYWY0Nw',
                                lat: 1.47751947,
                                lng: 103.8297877,
                                content: 'Cal Southern School<br/>CSS63323<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NmQyMWUwYjg4MjM5NTNlOTk4MWRiYTZjMDU3MWIxOWI2ZTU0MTgyOGExODM4MTIyNzVhNzdkMjE1YzEyZDRkNw',
                                lat: 1.28212703,
                                lng: 103.8687069,
                                content: 'Canbourne School<br/>CS41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZDJkZTAzNzc2NWQ0OWZkYWU3ZGU5NGNlOWExMzgzYmYzMzJjZmI4YzM1ZWQyMDA1NDNiMTIwYmE2MmRmMmJkZQ',
                                lat: 1.39223208,
                                lng: 103.860856,
                                content: 'Chadwick School<br/>CS59630<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OGYwYTE4NjJjOWMzMzc4ODA4MWE0ZjM0ZjdjMWFlOTllNTc2Zjc1Nzk5Yjc0YmViNGMwNGEwZThhZGMwN2UwNg',
                                lat: 1.29766779,
                                lng: 103.718883,
                                content: 'Clarksville School of Theology<br/>CSo68382<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjgwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MWVjNDEzZDBhOWMxMDNhMDVjOTEyOWI3Y2M1MTYzN2MxZTI1Y2ZjNDIzMjMwNTA2MWE1ODAyMTZkN2FhOTI1ZA',
                                lat: 1.4947353,
                                lng: 103.8166131,
                                content: 'Colonial Academy<br/>CA50436<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZjhiNjliYTUwNTEzNjk1ODkzZjZhNWY2NzE3NmEwZjA3MjY4NzNjYzcwNTIzMzJkYzFlNWEzNGZkMTZmY2ViMw',
                                lat: 1.31305392,
                                lng: 103.8627227,
                                content: 'Concordia Theologica Institute For Biblical Studies<br/>CTI66758<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NmE3MWUyODgxZTI3MjQxYjRjOGM0NmVkNDc5ZDIxZGY5Y2ZiMjhlMTBiZjgxMzVmZjUyMGFhYzVlZDA5ODVjMw',
                                lat: 1.49553381,
                                lng: 103.7593363,
                                content: 'Derrylatinee Primary School<br/>DPS63404<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzI2OTk5NDA3MGE2ODAxODcyM2ZiMGNjM2M1ZmI4ZDgzMWNiZWI1MzY2MDYxNTk4ZTU3M2FkNTcxNjViMWIxYQ',
                                lat: 1.22907621,
                                lng: 103.8456063,
                                content: 'Desertmartin Primary School<br/>DPS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YjdmZGY1NGU4YjdiMjZjYzg3MzU3Mzg5ZTljODkwOGM1ZjcyYzU4MjNiNDlkYTIwNzgxNDg5Yjc4NzYwMzQ3YQ',
                                lat: 1.39846913,
                                lng: 103.6794146,
                                content: 'Donsbach School<br/>DS37205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWRlMzJhZDQyNDc4MWE5NGZkYzhmOTRjMzY5ZmJkMzE0ZjFhYzZlODQxNGM4Y2ZhZDE4Y2Y2MjdjZjAwMjRmNQ',
                                lat: 1.22701733,
                                lng: 103.8669647,
                                content: 'Earlview Primary School<br/>EPS58748<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Yjk4NDBmOTA2ZTcwNGU0MDk2OWE4YTM1NGZlMjBkMTFjOTRiMzE5ODQyN2Q2ZjkzNjczM2QyYTY3ZjY2ZDRkMQ',
                                lat: 1.45358705,
                                lng: 103.8125816,
                                content: 'Earthnet Institute<br/>EI55910<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzZkMGZjMTRmNDcxZTllYWVhZWIwMzRkMmYwYTRjZTU1MDM3MzJlNmUwYjRjY2I0NTI0Zjg5NDJjY2UxNGFiYg',
                                lat: 1.31468125,
                                lng: 103.9784383,
                                content: 'Ebrington Primary School<br/>EPS51668<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODZjNTFiMjQ5NjVmNjRlZmI2ZDQ5NjliNjUwNmFiOGFiYzE5MjJjMzAwNzEwNzIzZmQ1Y2U3NzEzNjQ3N2QwMQ',
                                lat: 1.23806883,
                                lng: 103.9455734,
                                content: 'Eden Primary School<br/>EPS36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWYyMDU0ZGE1OWE1MWQ4Mjc2ODhhOWY1Nzg1ZTJlN2I1ODU5ZGRhZDFhMDRmMzlmYmY3MGRjNjI5M2M2ZDUxNg',
                                lat: 1.34746374,
                                lng: 103.8029682,
                                content: 'Eden Primary School<br/>EPS555<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzU5ZTk3NjBhZWY4NTNlNTJjNmU3ZTljMGRkYjVlMmQyNGY1NzdlNzEwNzFkYmJlOGJlMTRkYzNhNjhkZjNlMQ',
                                lat: 1.39649437,
                                lng: 103.9040781,
                                content: 'Edenderry Primary School<br/>EPS363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDczNzFkYjk0OWUxODlkYmU1ZmE4MDUyNjk4NjhlZjI4NmIzMjg2ZjQwYTM4Zjg3YTk2OWM4MGUzMTVlYjRmMw',
                                lat: 1.44690741,
                                lng: 103.9384257,
                                content: 'Edenderry Primary School<br/>EPS67<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzdmNmZiZDhmYTc4YzlkMTMzMjUyYjI1ZTRiOTg5NmQ0OTUxNTkxYTA4OTg2M2QzZmQyMDg5YzEzNmY5YTBlMQ',
                                lat: 1.322256,
                                lng: 103.7796064,
                                content: 'Edendork Primary School<br/>EPS49230<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTU4YWJkYTY1OTc4MDYwZTA0MGM3NGQ1NjhkOGE4MDg4MWMxZjZmYmVjZDU0ZGY5M2QwYWZjY2Y2Yzg2YjdhZQ',
                                lat: 1.40966698,
                                lng: 103.912761,
                                content: 'Edison School<br/>ES93<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzA4MWNjYWMwMTYyNDBlMjI5OWYxNDcxZTU1Yjg2Yjg1MjI0NWRiMGZkMjA3MzljYzc5NGQ4NjFjODRjOTllOQ',
                                lat: 1.27173922,
                                lng: 103.78613,
                                content: 'Eglinton Primary School<br/>EPS37000<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWY0MDVhMjg1ZGNhMzEyMTcwZWQ2ZWYwZmMxNTQyYjUzZGNlZjU0OTE0OGMxMjM0Yzk5Yzk0NzU1NzczNDlmNg',
                                lat: 1.36834971,
                                lng: 103.9280454,
                                content: 'Eglish Primary School<br/>EPS60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YThhNGMxNjk0MTVlOGZlNGYzMGE3ZDg5MTdiYjdiMTEwZGU4ZTM0OTQzNDUxZTk0ZTQyZTg2NDY1MDU3NmNhYg',
                                lat: 1.36264706,
                                lng: 103.7065041,
                                content: 'European Institute of Technology<br/>EIo67234<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjkzODRkODMxODIxMzAwZWU2MmJiMTc1N2RhMGI4NjA2OWU3OTIyMDQ3ZGRkNDlkNTJhZTBhZmVlYmY3NTVjZQ',
                                lat: 1.36925911,
                                lng: 103.994418,
                                content: 'Fair Hill Primary School<br/>FHP49999<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjE1OTM2ZjkwNWM5NTMzYjJlNGU4MDkzMjRkZWZjZTk2YWMyZGE3ZjlkNzFlNTcwMDAwODljYjJiNzZjOTZiMQ',
                                lat: 1.51092287,
                                lng: 103.8063793,
                                content: 'Fairview Primary School<br/>FPS37808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmY1ZDA4YWJmZGU0NDc3YmM3ODNmMTkzYzMzOTYyNjhkMzQ5MDA2NDQwM2I5NTZjMGY2OWM0NGNhYmJhYmQ4Zg',
                                lat: 1.46715975,
                                lng: 103.7667907,
                                content: 'Faith Seminary - Salem<br/>FS-37205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OThiMzEwYzI3NjJhZDIzYmI3MzMxNmVkYzBmNzIwNGMxZmZlYWZlNmE2Nzk1OTg5MTZkYjVmNDY5ZTc2NDZmMg',
                                lat: 1.27386893,
                                lng: 103.7141903,
                                content: 'Finaghy Primary School<br/>FPS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzhmMGI1NWQ1M2MwZGJkNzAyNzNjZWE3Nzk2NjQ0ZTA3MDRiNmNlMjE5NzRiZTFkMDk0MzQwMzY4NmUxNDJjMA',
                                lat: 1.24952807,
                                lng: 103.8967647,
                                content: 'Fivemiletown Primary School<br/>FPS43353<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTIxYWM0MzcyY2FmMjA3NDJiYjg1MmZmOTdkOTRkYzM3NzhjZWI0NWY4OWVjZGM5N2E3Zjc0MjFkMWM3MGI3NQ',
                                lat: 1.42958684,
                                lng: 103.8290971,
                                content: 'Foley Primary School<br/>FPS53807<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmI0M2Q1YjcxYWE0NDNiZmE3YTVhMTUzOTUxZTg2NmVjYTliMzBhMDcxZWE0YTE3YmU2OThlMWM3NDZjMGYyZA',
                                lat: 1.42241663,
                                lng: 103.9291888,
                                content: 'Garryduff Primary School<br/>GPS74<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzAzZjcyYmVlZTg3ZmY1YmMxYTdhN2FjY2YxNDdlMDc2MzgwZTM2ODk1NWM4NzJhNGZjZDI5MGJlZjBlNTk2OQ',
                                lat: 1.39255054,
                                lng: 103.7252735,
                                content: 'Garvagh Primary School<br/>GPS50580<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzBjODdkOGIwZDFmMWY5MjE1ZTI4MTZhNjAzMTA0YTcxYjNkMjg4ZTM3NWMyYjViOTZkYWFhMDIzNTMxZTlhYw',
                                lat: 1.40789748,
                                lng: 103.8246762,
                                content: 'Generale School<br/>GS57881<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzNlOGI0NzM2OWU5MzZlZGU1OGRiYzEyNzc3YzIxYjg5ZTUyOTUwZTgzMDlhMTZkZjkwZjY0NzA1NzY3ZWJjZA',
                                lat: 1.30186091,
                                lng: 103.8176886,
                                content: 'Glenann Primary School<br/>GPS60258<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGQwNzBjOTczYjJhMDE3OTAyNjY1N2EwZjcxNjUyMGQ4YTkxODE4YzM4YjUxMmRhM2JlZmI1ZWQwMTM1YjQyNQ',
                                lat: 1.37933954,
                                lng: 103.6872982,
                                content: 'Glenarm Primary School<br/>GPS61767<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzc1NWM2MjZlZDNlZmZkOWI5NGUxMTViNTJkZjQ3NzcwYWE3ZWRiMGVhM2VmNjIyYmJjMjE4ZjRmM2VhMWU3MA',
                                lat: 1.29249232,
                                lng: 103.731168,
                                content: 'Glendale School<br/>GS591<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzk3YmFkNTY4NjkwNjAyYTJhZjk1MzgxZWM5OTFkYWVjMjQyODM4NWNlZmM3ODc2N2MzMmYxYTYyNDU0MmMyNQ',
                                lat: 1.3508961,
                                lng: 103.7933413,
                                content: 'Glengormley Primary School<br/>GPS615<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDc0OWFhMTQxOWExYmQzNzk4YjVhY2Y4MGRlMWYxNmEwNmJhOGM3NDIzYTFiMDRiYzcyMzEwNDI4NzljNjE2ZA',
                                lat: 1.30924962,
                                lng: 103.8600349,
                                content: 'Glenravel Primary School<br/>GPS68788<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2VlMTQ1NmU2MDNjZjNjMWE3NDZiZGNjZjE5MjI5Y2M3OGYxM2Q3MWE5ODY4MTFlM2JhNWI1YzljM2U5ZWZiMA',
                                lat: 1.19394399,
                                lng: 103.7992437,
                                content: 'Glynn Primary School<br/>GPS59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDRjMTU2NTI3ZDc4OWU2NmRkMjFlNGM5MTM2ZjhkMGQ0MDZlYmI1MWRjZWE0ZThkNjY1NDk2MDU5YTFlY2FkZA',
                                lat: 1.47560567,
                                lng: 103.9239402,
                                content: 'Good Shepheard Primary School<br/>GSP24571<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzRjZjVhNzY1MGVlODVmZmQ0ZjJmMTIyZmM2N2ZkOGJkY2NhZDBlZGEzMWQwMjAyYTU0MDYxZGNjNzljYjI4Mg',
                                lat: 1.45878361,
                                lng: 103.9538302,
                                content: 'Gorran Primary School<br/>GPS50268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzVmMDQ4NmU0MzQ1MmVjNTU1MjFhOGUwMDk0MDQ4YjU0YmRjNGMxNWMzMzZiY2RmNDlmNzBiMjQ3ZWU0YTUyNw',
                                lat: 1.38214141,
                                lng: 103.6634828,
                                content: 'Gracehill Primary School<br/>GPS67877<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTc4OTJjOTY0YjJlYmFkZDIzOGJlNTI4ZGM1NmIzMTY4MmEwMjQ5MjM1ODkyZTQ0MzI0NGNlMDQ0ZmM1MjAyNg',
                                lat: 1.18967608,
                                lng: 103.8079075,
                                content: 'Grange Primary School<br/>GPS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjY0N2M3NTk5ZDkzMjg5MzhkZjlhNzdlZjQ3ZTY2OTQ0NzE0YmY3NzMxODdjMjFhNTFhYzM2YzUwMzA3NzYzZA',
                                lat: 1.33482556,
                                lng: 103.7695372,
                                content: 'Granville Primary School<br/>GPS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjRhYTI3ZjI4N2NiMzY4ZDQ4ODY3MzMzNDVhZjc4MjllZjNjZTQ0MDEyMzhmZmRlODFlZmIzMTQzMGU2YTE2Mw',
                                lat: 1.23436116,
                                lng: 103.7597889,
                                content: 'Greenisland Primary School<br/>GPS53807<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjExMGQwNjY5N2EyM2Q0NzE0M2RkNjgwOGFiYWRmMjBiYzVkN2UwNjRjMWU5YjZiZjY1MzQ0MmYwNmI4ODM4ZA',
                                lat: 1.26776768,
                                lng: 103.8185748,
                                content: 'Greyabbey Primary School<br/>GPS45802<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzc1Y2RhMmEyY2MyMzNmNjEwNzM5ZjM5ZmMwMGRhNzBkZTM3ZGM1MWI3YzAxZjdjZTUxODU5ODVlZThlNmU4Yg',
                                lat: 1.28212816,
                                lng: 103.8392083,
                                content: 'Greystone Primary School<br/>GPS57664<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWMxMWEwNTZlYTNiNzczZjU0OWRiYzZlNTVhNDUyNTRlMDQ4NTQ4YjczMDdhYzViZDY3N2Y5YWQwNzRkNDZmYw',
                                lat: 1.345498,
                                lng: 103.8546583,
                                content: 'Groggan Primary School<br/>GPS16590<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGVmOTgyMDYyMDM2YjIxNzNkMWI4NmExZDVmYjY1ZTU0Y2VjYTIyYWI4MWE2ZTQ2ZTVmZWI4MjljNmVmM2QxZQ',
                                lat: 1.47447932,
                                lng: 103.8773062,
                                content: 'Hamilton School<br/>HS47682<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2FmY2U3YmFjMzc2ZDI0MTA2Mzc4Mzc5NjAxMTdmZDE1OWU1NzQxNmE5MTg2MDBmNzEyMTYxNzdmM2RjYTU0YQ',
                                lat: 1.31439192,
                                lng: 103.7923528,
                                content: 'High School of Esoterica<br/>HSo43353<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTE1NmM0MDA3ZTU3YTU1ZTdmYjNkM2E4NDY0YTdjYTY0NzJlYjc3NmJiM2MyMGU2M2JmNmI1MzBiODhlYzNhYQ',
                                lat: 1.39755311,
                                lng: 103.7269776,
                                content: 'Illinois Theological Seminary Online<br/>ITS41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDZjMmUzY2JjODBlMjVhMDRmYmJlNDAxN2MwOTNlYjdmN2YwNDE1NzRiYWJjMTg5ZDQ4N2FlZjgzZjllZDAxNw',
                                lat: 1.29337995,
                                lng: 103.8698986,
                                content: 'Interfaith Seminary<br/>IS54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWE2OTFkNTQ5MGE3YmM0YTZmYmI2NmQ0NDg4MTlkN2Q5ZWI5MWE4MTU5ZjU3MmUyOGVkZjI3YTkwODhhN2FkOQ',
                                lat: 1.46822992,
                                lng: 103.9410055,
                                content: 'Ivory Carlson High School<br/>ICH52685<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmNhZTNjMzQwZWExYjUwYWM0MWY5MzBhZDk2ZTRmM2M5Yzk1MWQ2YjBkZmE0M2NkYTVhNzJmNDE2OTc4ZGY3MQ',
                                lat: 1.32688686,
                                lng: 103.7560722,
                                content: 'Kepler Space School<br/>KSS52959<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Yjk1NWYwYjcyNmY1NWExZDg2MjYyNzM1OThhNTM4Y2EwMTk5NzRkMjg3OGFlMGZlMDA3Njc3ZjNlZjUzMzNmOA',
                                lat: 1.32217906,
                                lng: 103.9265729,
                                content: 'Killowen Primary School<br/>KPS68233<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzRiYTk2OTZlZDEwZTI0NjYyZTgxMTJlYzdlZDA3ZGQwM2U3OTcxNDBkN2I4ZWEzOWE5NmYxZDg0NzEyYjZkMA',
                                lat: 1.21511811,
                                lng: 103.8518018,
                                content: 'Killylea Primary School<br/>KPS57520<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGNiMDBjOTFmMTI5N2Q2YWViZjczNzJkNWJjNWYyNGJlMDU5MGVmYjljMmExMGUyMWE0MmU2NzAxNDFmY2IwZQ',
                                lat: 1.4395775,
                                lng: 103.9511298,
                                content: 'Killyman Primary School<br/>KPS51531<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTc1YzlhOTlhZTIzZWFkZmJhMGU5YmUzMDk4OWM2MjJmMjMyOWRhZTU3YmM5MGJhZmRlZTAxNmM1OWE5MjZiYg',
                                lat: 1.19238025,
                                lng: 103.8651194,
                                content: 'Laghey Primary School<br/>LPS16714<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjAxNTI5MzdjNDViMmNjOWVhNmQ3MjE5OWM0NDkzYTU1MzE4YzQ0MTAxYmNiOTg2MDlhZTg5MWYzNTVhOTczOQ',
                                lat: 1.306984,
                                lng: 103.8310679,
                                content: 'Landhead Primary School<br/>LPS395<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjMzM2U3ZjRhYmFiN2IyMjVhOGNkZGUyZGQ5NjJiYTllOThlZTc5OWIxMDkxNmE4ZTdlZDM1Yzk5MTRjYjAzMw',
                                lat: 1.30511729,
                                lng: 103.6960493,
                                content: 'Lansbridge School<br/>LS68400<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGQ5NWM2YmFlNjNjZTViZGRmNDJkODcwN2FmYjA1MTE1NGYzMTIwNTdlMDdlOGExYmEyYWMzYTE5MTIzMmQ3Zg',
                                lat: 1.21533846,
                                lng: 103.791346,
                                content: 'Larne and Inver Primary School<br/>LaI52118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjU1Zjg3ODdhZGY1YTg2ZjhjOTkzZjY2MWQ1OTE0MTEzNjYxNmU2YzY3MjQyZGNjNDQ4Y2E5ZmIxM2M0ODg3ZA',
                                lat: 1.19679692,
                                lng: 103.771077,
                                content: 'Leadhill Primary School<br/>LPS57627<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjU0MGU5NDZiMGNkZjljOWE4MGU0NGMzY2U3ZDQwM2RmZDY5OTA2ZGRmNzQ2MzlhMDQzYmU3ZWYyYzZjYjgwYQ',
                                lat: 1.33769514,
                                lng: 103.9503984,
                                content: 'Leaney Primary School<br/>LPS63323<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzFhOTUxMTdhNzljZmMzNDIxOGM4ODBhYmZjMjE1MjczMmZlYmVkOWM2M2ZlMmEwZWQ1NTY4M2JhM2VhZWE2Zg',
                                lat: 1.23947275,
                                lng: 103.9159249,
                                content: 'Libera Universit<br/>LU50988<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWJlMWE1MDI3ZmQ2YTY3NTJkYmU4MTRjYjQ3MTExYjlkZGRhZDY2ZmZhM2M0NTFmOWM1YmVhNWVjYmI5ZjA3NQ',
                                lat: 1.49218926,
                                lng: 103.752599,
                                content: 'Linn Primary School<br/>LPS41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTE5Zjg4OTFjOWU4ZGFlNTk0NjBkODhkOGY3OGY0OTE5N2YwMDZhZGE4MWRhYzI4NDMzZDhjYzRhYzk1YTA1Mg',
                                lat: 1.37113836,
                                lng: 103.6523899,
                                content: 'Lisbellaw Primary School<br/>LPS57344<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2EwMDY1YjUzMjY5YzI3OWIyYjM1MmQ4ZDkzZGNiNjMwNTQ1ZjI0ZTk1OWMyYmNiOTRkNzAyZGVmOTVhZjE1ZA',
                                lat: 1.52028161,
                                lng: 103.8252384,
                                content: 'Lisfearty Primary School<br/>LPS50383<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGU2NzM1YTQ0OTc4MjhjZTVhODY2ODgxNjViMTU3NWYxYzc4NWE3MTgxOTc2NTFhNTk2MWU5Y2MwNDk0NGM5Ng',
                                lat: 1.28206001,
                                lng: 103.9620385,
                                content: 'Lislagan Primary School<br/>LPS66993<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzU4NzNlZWVkNzQwMWMzNzgwNDE1MTliMDYxYTlhNGMzNDYxMjkyYmI1YWE2ZjE2YmNkNzM1ZTI2ZDU1MjE2ZQ',
                                lat: 1.43081635,
                                lng: 103.861049,
                                content: 'Lisnadill Primary School<br/>LPS75936<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDMwOGRjZTY4NjdmOGE1N2U3NDRmNDgyZDA1YjIxZDM3MjIwOThiYWE1NzUxYzA2NGJjZDEyYWFiYmY3NmUzOQ',
                                lat: 1.33347241,
                                lng: 103.666958,
                                content: 'Lisnagelvin Primary School<br/>LPS40032<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTY2MDdlMWU4OGYxZWJhN2VmYjQ5MmE5MmYyZTAwMDUyNWQ1YWIyNjhkOGZhM2NkOThhZTVhOTE2YzdhYTBmMQ',
                                lat: 1.32316274,
                                lng: 103.9141387,
                                content: 'Lisnamurrican Primary School<br/>LPS46<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGU5ZTllMDdjMjFkZDE5ZjE2NWNhZjU2ZGU4ZGJiZmM5N2RiZTgwMWM4MGM1MThlZjc5MjQ0OWQ5OTM1MWE0Ng',
                                lat: 1.50831692,
                                lng: 103.7403437,
                                content: 'Lisnasharragh Primary School<br/>LPS33261<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTI2NTkzNzU1ZmM3Yjg3ZmI3YjkwZDM4ZDNmZjQzMGZmMzczZDczODUzOTE5MjZlZTAzMjY2NDQ4ODU1NTZlMg',
                                lat: 1.41111348,
                                lng: 103.7606318,
                                content: 'Lissan Primary School<br/>LPS10596<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzhiZGUyYTI4ZmQ3MzEzODdiZGRjNGNlMjkxYTlkMDc4NTM1YjAwM2I0ZmY5ZjJlNzVlYmM1ZWM2MTg5NmIwNA',
                                lat: 1.26183474,
                                lng: 103.7797852,
                                content: 'Loanends Primary School<br/>LPS259<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjY3YjQxMjY2NTY5ZTc1ZDA1NDdkNDhiMmRjOTJlNTk0MGEyNWY2MThhMDI2ZDRhYTQzNDg4ZWMwYjNjZDQyOA',
                                lat: 1.22211947,
                                lng: 103.7929614,
                                content: 'Longstone Primary School<br/>LPS832<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTBiYTM5NTAzZTc1NGJjODQ4YjBiMWI5ODk5M2JlZTc5OGU4Mjg4MjY2MTgyNGVjNzE0MjI1MGQxZTUwNzdiMA',
                                lat: 1.24217967,
                                lng: 103.8644357,
                                content: 'Los Angeles School<br/>LAS21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjU3YWJhN2ViMDQ2MjliYzhiMzcyYTkzNGE4MDkzYmQyNTY3Mjc1ZTdkMjE3MGVhYmQ3ZDZlY2VhZDAwMTc2Mg',
                                lat: 1.38494275,
                                lng: 103.6453459,
                                content: 'Lourdes Primary School<br/>LPS63629<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDdhZGJmNzRmMmRlNTBjN2U3MGYxODlhOWJjN2Q5OGVkMDkxMjFiOTYzZjYxMDg2ZTQ1ZmMwMDY3ZWY3ZTc0YQ',
                                lat: 1.425624,
                                lng: 103.91661,
                                content: 'Lurgan Model Primary School<br/>LMP67345<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzgwNGMwMTMxNWYxOWEzZDMwY2YzYmU1OTMwODE0MWE2NWQ1OTZkMzVlN2VkNWIxMWQwYTJhYjk2NTNmNGEzMQ',
                                lat: 1.2721061,
                                lng: 103.693741,
                                content: 'Mahila Gram Vidyapith<br/>MGV59054<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzI4ZTIzYTVlYTdlZDQ4Njk1Y2U1ZDg5MzUzNGY5ZjkxMTMzZDg5YjQ2ZjUwNmJiNTk5YjIwMzAwOTQwZTU1Yg',
                                lat: 1.36854992,
                                lng: 103.9460468,
                                content: 'Maralin Village Primary School<br/>MVP55891<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjFjY2U5YThmMjllZGYyODI1Y2FlYTk0YWQ1MGI5NjEzYjE0Nzg0ZDc0YzQ3ZmU0YzZmYmU3ODQ2ZGQ3NzAxOA',
                                lat: 1.48525893,
                                lng: 103.816172,
                                content: 'Markethill Primary School<br/>MPS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDQxZWQzN2E4YzcyMjA2Y2Q3NjZiNmYzM2UzNTQwYjBjOTZlNDMzYWY1NGI3ZWVhZGQwZTVjYzIwMTg4ODIyZg',
                                lat: 1.21129804,
                                lng: 103.7301896,
                                content: 'Master\'s International School of Divinity<br/>MIS10889<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmI3NWYzNjBlYmVhYTcwODJiM2I5Y2Y2NWQyNWJkMTBlZWY5MmViMzk2ZWM2NjRlZDUwNWYyMmMxZGM2ZGM4OA',
                                lat: 1.23728357,
                                lng: 103.9480469,
                                content: 'Midtown School<br/>MS50346<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTdlMDM1OGIzNDYyYzg0Y2FjYjIxOTIwNjUzMWZhMTkxZDhmZWIyYWI2OGZjMzUzYjAxZDIyZDNjMDU5NjgyNg',
                                lat: 1.28200938,
                                lng: 103.756086,
                                content: 'Millburn Primary School<br/>MPS143<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGVjZmM5MGEwY2EzMTQ1Y2U1NWU4YTYzMmZkNDdjM2Y0ZDVlNjk4Zjk4NWI2Mjk2ZTI4MzA4ZGYxNTQzZTA3ZQ',
                                lat: 1.4151866,
                                lng: 103.9532402,
                                content: 'Millington Primary School<br/>MPS42192<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTkwMTlhNjVkNTY1ZjQ5NWU2OTA0ZmExMzM1MGY2OTgyNjEzYjYwZDU0ZjkyODZlNzI1NzRjYTkxY2VmMjllZQ',
                                lat: 1.41981418,
                                lng: 103.8179017,
                                content: 'Milltown Primary School<br/>MPS268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTUxYzRlODA3MjAzZTVmMjk1NjY4MzkyMWQyOGMzNDUxZTg1M2ZmN2RlMDE2OWRkMzM0OTc0MWI1ZWFlZTNmMQ',
                                lat: 1.30379109,
                                lng: 103.8487139,
                                content: 'Minterburn Primary School<br/>MPS43406<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDlmMzA4NzBhNjY2N2Q2Y2MwNTViNjkxYWI1NDI4Mjk3Yzg0OTVmZjRlYTZhMTNjMjdmN2MwNDJmZjEwZTBjNA',
                                lat: 1.44377365,
                                lng: 103.7867279,
                                content: 'Model Primary School<br/>MPS76329<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmQzZjA1NjgwYzBkOWMwYTY0MmQ5MGU5MzRkNGYzM2FmZGFjNWFjNTk5NDY1NTQ5YTVkZmI5ZDkwMWU3MmVhZQ',
                                lat: 1.2707382,
                                lng: 103.7772827,
                                content: 'Moneydarragh Primary School<br/>MPS37205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTRkYmU5ZDI4MzU2M2ViNGUwMzc2NzZhNDk5NDljZWIyNWJhYjcyNTczYzkwODNiYTQyODMyNWQ4YjE1YTIyMw',
                                lat: 1.39614375,
                                lng: 103.8916022,
                                content: 'Moneymore Primary School<br/>MPS43857<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWM1ZTkxZDBkYzVlYjRkMmU0M2NmZmY2MmM0NWEyOGM5ZTBhMzBjNTZhYWE0MTZiOGU2ZTA1ZjlmZGI3YmQ4OA',
                                lat: 1.21187779,
                                lng: 103.8280464,
                                content: 'Moorfields Primary School<br/>MPS60505<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDI2NDA2Yjc5NTNjZmMwNjQwZmMwNzY0NjgyZmRjOWM4ZmYyNWJjZjlmNzYwZmUyMWE0NGM5ZjMxMmI0M2FlOA',
                                lat: 1.37164469,
                                lng: 103.9472352,
                                content: 'Mossgrove Primary School<br/>MPS54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjFlOTYxZDIxNTVhMmRhODk1NDYwOGRiYTM5MTg2MjM3NjVjOGQzNGQzNmFjZTc2ZmZjNjFmYTdkZTgwMjE5ZA',
                                lat: 1.51359094,
                                lng: 103.7458599,
                                content: 'Mount St Catherine\'s Primary School<br/>MSC53329<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWExOGMxMmM4MWFhNTYyOTVlMjFlZWVkOTU4Y2Y1NDAwN2UxZmE1MmNkNmI3NjM0Mzk4ZDhmZDYxYWY4NzJhNw',
                                lat: 1.24141457,
                                lng: 103.9083071,
                                content: 'Mountnorris Primary School<br/>MPS2410999<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmZjMTllZGVmNTZiYjM2MGZiMDhhNmRkYTExNmZmMmZhODM5NWU3NDRjMDEzNTdkNTRiNzYwYjZlZmM2NmJlZg',
                                lat: 1.4807877,
                                lng: 103.8226898,
                                content: 'Moyallon Primary School<br/>MPS58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTI2Mzc5MWJkOTgyMDQyODdmZWExMDhjZjQ0NjQ2YzJjOTYzNGJiNGYxODY1OGNkN2Y3MjIwZWI2NzFjMWZhNg',
                                lat: 1.21679891,
                                lng: 103.8542235,
                                content: 'Moyle Primary School<br/>MPS41250<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGZmZWRkOWVkZmRkNjA5NDhlMTQyMWMxNGI5NDk3ZDQyNGM3MTc2YmUyOGMyODJmOTdkODY2ZWIxY2Q1NDcxNA',
                                lat: 1.3499796,
                                lng: 103.8875847,
                                content: 'Mullaglass Primary School<br/>MPS88<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmQxMjMyNTg1NzFmMDI4NTE2MWQ4ZGUzYzcwYTVhODlmYjZmOWRmZjY4NmZlNWVhMDRlNDQ5YzY2Zjc4YzM5Ng',
                                lat: 1.25225044,
                                lng: 103.7279586,
                                content: 'Mullavilly Primary School<br/>MPS58232<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTc3YzdjYTJjMzE1OTk3NzkzOGJlYmY1ODkxMzI2MWI5Nzc2YjYzMjI1ZTZkMTk4MWVlMmZhMDhkMzg5NmU0Zg',
                                lat: 1.43548058,
                                lng: 103.7885229,
                                content: 'Nightingale School<br/>NS49852<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWQ0MWQxNGRiODU1YTViYjVjYmZkZWM4YWJkNWM2NmExMTQ2NTBjMWEzYjQ3MGVjNzQxZGQ1ZTVhZmU1ZWM2MA',
                                lat: 1.3586179,
                                lng: 103.7739935,
                                content: 'Ninigil Primary School<br/>NPS69152<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWIyMjA2Y2VmYzkxMGU0YWZlNDIxN2Y3YmIzZTllMTFhOWE0MmVhODNjNjlhY2JmYzdhZjgyYmJkZDhhOGFlZg',
                                lat: 1.26769416,
                                lng: 103.7113053,
                                content: 'North Reformed Seminary<br/>NRS52471<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWM4ZWNiMjg4N2QyM2I1YWY3ZTNlYmM3NmVkNjUwZjMzNzNmMWU0OTM2YjlkZTkyYTc3N2E3YTllNGExMzY2Ng',
                                lat: 1.36982653,
                                lng: 103.9119451,
                                content: 'Paka High School<br/>PHS53808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjlkZWU4NDdjYmQxYjJmODkyOWY4Zjc1NjcyMTRkZDBhOGQxNDRmOWI4ZjU2N2ZlMTcxNDU3ZTcxOWUyMzI3Mg',
                                lat: 1.52987016,
                                lng: 103.8416841,
                                content: 'Pebbles School<br/>PS55118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjZmZDliYjRiZWQ4ZTljZWM1MTYxMzRjNGU5YzllODAxNWM4NGFlMjI3ZjlkMWI2MzhkNzYzZDI1OWViYTZjNw',
                                lat: 1.24091646,
                                lng: 103.6797304,
                                content: 'Pino Primary School<br/>PPS51824<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmUxMDc4YmFiNThiOTM2YzNmZTlhYmI0MjQ0Mzk2Y2NmYzRiZTA3OGY1ZmQ0NjlhYjAwODk2OGYzNzkyNDVmYQ',
                                lat: 1.42160314,
                                lng: 103.7271586,
                                content: 'Portadown Integrated Primary School<br/>PIP49288<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTc2MTBmYTc2MmNkYTI0ZWZmYmE2MmUwZmQ5ODFlMjM1ZTRiYzBjOWQ2YjdjMzU4ZWY5OTY3NzQwYmJjYzI0Zg',
                                lat: 1.52279049,
                                lng: 103.818929,
                                content: 'Portadown Primary School<br/>PPS42221<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDUwMDc4ZDgyMmUwZTUwMDliNzQ4Mzc5ZGEyMzhmYjJmY2MzODcyZDg2ZThhZmJkZjc3YWM0NzRlY2RhYTc3MQ',
                                lat: 1.43420171,
                                lng: 103.8130072,
                                content: 'Portavogie Primary School<br/>PPS268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTcyZGVlNDg1ZGEwYjQ5NTJjYzUyNzNhZTY2MjMyNTQ5MjliNmZiYzVmMDY3NjEzNmYyOGU3YmIyOGYyMWI4Zg',
                                lat: 1.3161307,
                                lng: 103.7269953,
                                content: 'Portglenone primary School<br/>PpS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NThkYmExYzUyZWFjNTdiNzA5YTU0MGM1NmY4NDBmYTZmNDU1ZTFlYmUzMDY4ODY2YjVmYmFlZmJjZWU0YjAwNQ',
                                lat: 1.33950437,
                                lng: 103.8729962,
                                content: 'Portrush Primary School<br/>PPS42783<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODlhMmYwZmVlMTZiMDM3ODAyNmE0MWFjYjI5NjM3ZmNjMjEyOTA1NTlkMTIyYWQ4Njc3MGFjMTJlNTllZmNmNQ',
                                lat: 1.43503803,
                                lng: 103.7769822,
                                content: 'Portstewart Primary School<br/>PPS50825<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODk5NzA4YzkzMzUxZDQzNzA0OTdlZDRkZGNmMmFhZTY0MDkzMDgyNWZjM2RkZDg0MDIxYzUwZjdiYTk3YzcxZg',
                                lat: 1.43862696,
                                lng: 103.8299869,
                                content: 'Poyntzpass Primary School<br/>PPS24854<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDZlMzYzYmMyOWZmMGM1MmVmNzE5MjA2YjYzOTIwNzIxMGVhY2UzY2RjNzA1MzE1ZTNlZjJmNDI5NDFjZjViZQ',
                                lat: 1.2905969,
                                lng: 103.8268478,
                                content: 'Presentation Primary School<br/>PPS57955<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTM0MTliMzg1YWQ1OWQ1ODY1YzhkYjViOWE4NzRiNjIyZTQ2ODcwYzkwMjk4ZjllYmUzNGUyZGEyZDczYTE2Nw',
                                lat: 1.48103035,
                                lng: 103.8734683,
                                content: 'Primate Dixon Primary School<br/>PDP37808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTUyMzQyNWRmZTU1MDQ1MjkyMmJkMzcyYmRkN2RhYTUzYThmNmJkZWIxNWYxYWZiNzc1ZTc3MTE4YTgzNzc3Mw',
                                lat: 1.23507761,
                                lng: 103.7743218,
                                content: 'Punda Primary School<br/>PPS10237<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2EwM2Q4ZjJiM2I2ZDdiZGFhZWJmOTc4ZGQxZTJkNzUyMDczNmQxOTA0OGM1NGQ4ZGIxYmYxYWZhOWEzYTEwNA',
                                lat: 1.22962447,
                                lng: 103.9514313,
                                content: 'Queenston School<br/>QS65044<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjgyZDJjZmFlOWQzZjBjMDlhMDcwZTExNDhmMzk2NDZhZDQwZThiNzM4ZjAxYjQzMTI4MTNmZDY2NWQ1ZjhkOA',
                                lat: 1.20965484,
                                lng: 103.8451851,
                                content: 'Revans School<br/>RS48951<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWI5MjkyYTZmYzU2NjRlMzVhYzVhNDA5Nzk5ODkxNDE0ZTQ5ZmM4YjkxZmVkMGM4OTk2MDRiNDA2OGNhMDBlYw',
                                lat: 1.44926582,
                                lng: 103.7220192,
                                content: 'Rocklands School<br/>RS43063<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTExMjEwODkyNGQ4MTMzZDY1NDExMmE0MTFmYTU3NjIzMjRhZjAxMDEwMDNmY2M2NWJlYWM0YWU0YzJlM2UxOA',
                                lat: 1.19232649,
                                lng: 103.7875787,
                                content: 'Sacred Heart Primary School<br/>SHP64205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzc3N2RhMDI5Y2QzOGMzODlmODEwM2YzMTU1NTRlZjQ0YzFiYzAxNTY4ZjlmNTkzMmMwOTNkMmE4NjI4MDk3OQ',
                                lat: 1.42840825,
                                lng: 103.8245976,
                                content: 'Samuel Ahmadu High School<br/>SAH36800<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWI4ZjEzYjQ4NzM4NzJkNDBiNThiOTZmMzhkZWU5N2FmZDVhODg4MjM1NmI1Y2JkZmNkNDUxZmJlMzY5ZjEzMQ',
                                lat: 1.18630721,
                                lng: 103.8591925,
                                content: 'Scarva Primary School<br/>SPS68645<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjlmMzhmODExNmZhM2UzMWUyOTExNTdlODc1YjYxMjdlM2QxMDIzZjY2ZmIxODM5OGQ5ODM3OTIzODFmNWQxOA',
                                lat: 1.34230125,
                                lng: 103.8489586,
                                content: 'School of Berkley<br/>SoB54340<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzc2ODk5YWMxMjhiMGQ4YTU3MDJkZTQwN2NlNGJiOTU4NGMyMzNmYmQyMDIzY2Q3ZjdmOTViMGMyNTVhZmY4Zg',
                                lat: 1.27256614,
                                lng: 103.6747408,
                                content: 'School of NorthWest<br/>SoN24275<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzJkYWIyNDI4ZGY2YjJmZjdjNWNiNjgyM2E3YjVjZmE1ZDVmOGM4NzMzMmU3YTNhMDFmZDUzNzhkODViOWI0YQ',
                                lat: 1.33399064,
                                lng: 103.9515513,
                                content: 'Scoil na Fuiseoige<br/>SnF30142<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGFlNjMxY2U1YWIyMDYwNTE4NzNhNTk3MWU1MTNjNzhhNGNjMmU3MWUxNDU2MjQwMzliNTU5Y2Y0ZjQ0OTMyMg',
                                lat: 1.19000657,
                                lng: 103.7482934,
                                content: 'Seagoe Primary School<br/>SPS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTA4MzUwYTg3YWM1MWQ1N2NkMjM2ZDQ5N2VmMGZjMDAzMmM3ODMzNWJmODc3OGExMWI5Y2ExYTU0ZDgwYTc0Yw',
                                lat: 1.45555134,
                                lng: 103.7836726,
                                content: 'Seaview Primary School<br/>SPS20276<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGM5YTkxNjQ4MDMzNGE5N2MxMDM2MWFjNDQwMzhmOGY5M2VjYTU0YjFlMTQ0ZjkyMWVjOTEzZTExMjQzNDg4Zg',
                                lat: 1.32725856,
                                lng: 103.8232251,
                                content: 'Sogae Primary School<br/>SPS11704<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWFjNmRiNzM4YzA1YzIxMzc2YWY5MmM1ZmIzYWY0YjY4NjU2ZDNiNDcyNTUzYTY1YTRmNTEzZWJlNGZkNmFiYg',
                                lat: 1.49207001,
                                lng: 103.8780484,
                                content: 'South Pacific High School<br/>SPH16252<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTU4ZmI5MjliZWE5OGRiZTBiMjRlOWZiYmMwNjZlM2NhOGQxNzRlOWVmZWNhMzUzZWU1OWIxZDc4Mzk3YzgwNA',
                                lat: 1.21185722,
                                lng: 103.8295348,
                                content: 'Stewartstown Primary School<br/>SPS13668<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGMyMDQ0MTQ4NzhmNjc4YTQ3NDE3MzM0OGEzMGU0Y2MzNGVhOTZmOWYyMzE2ZmNhZjg1NGNhY2I0NTc0YTRiZg',
                                lat: 1.37600056,
                                lng: 103.9225109,
                                content: 'Straidhavern Primary School<br/>SPS10565<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjZhMTNlODE1NDRmYTRiMmRhMGZhMDJhNzc4ZTJhNmY4NmM3NzI1ODk3NDAxN2E3NTFmZDY1MTA4NGNlNGU3OA',
                                lat: 1.27448264,
                                lng: 103.9711751,
                                content: 'Strandtown Primary School<br/>SPS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTdhMTMwYTYwN2ZmZTgyZDVkMTc0ZTQzYzEyY2EzOGViNTYxMTBlYTcyNWQwYWU5Y2EyOGFjOGFkMjFlYmVjYQ',
                                lat: 1.23114222,
                                lng: 103.7698624,
                                content: 'Stranmillis Primary School<br/>SPS378<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWY0ZDdlMDkzMWIxOWM1MjNlNzkxMDk0ZWViYzA1NmM4NDFmYmFiN2FmM2FhMDJlNDNlYjRlZGUwODY4ZmUzMA',
                                lat: 1.21959589,
                                lng: 103.7248911,
                                content: 'Success Seminary<br/>SS48951<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjQ1MTYyYmI3YWRiZWY3MTlmOGY0ZDM3NGQ2YTE3ZDFmZDg4NzZhNTRiNGQzZTgyN2VjNmZhOTI0YmVjMjYxYQ',
                                lat: 1.38546929,
                                lng: 103.8919659,
                                content: 'Sunnylands Primary School<br/>SPS363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTNhMTk2NWZkODIxMTRhNDJkYmJlZjk5MmY0MzU1MzVkYWM5MmIyZDljOWNjY2Q1MzZmMjcyZWMxMTllMjBiYQ',
                                lat: 1.37901286,
                                lng: 103.7381991,
                                content: 'Sutherland School<br/>SS781<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjI2ZmYzOGIzMzE4MzJjNjM2ZjM2ODJhMWIyZTc5NTNiNzU0ZDU1YjZiYjU3NDYyNTE2NmFmNjUxZTBlZDgxMw',
                                lat: 1.32412885,
                                lng: 103.7890878,
                                content: 'Talpipi Primary School<br/>TPS60681<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTIyNjliODk5ZTUyYjY4ZWE1Y2UzM2Y4YTg5MDRiYTY4OTFiNzRmMGZlOWEyZWI4MjczYjE4MzQ0ZGM2N2UxNw',
                                lat: 1.51200194,
                                lng: 103.8835311,
                                content: 'Tamnamore Primary School<br/>TPS51393<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2ZjOTE3MWM3ZmYyN2EzMTExZjc0NmE0N2YwMTBkNDQ3NGViNTVhZGFmZjlmZGE4MDkxY2ViOGUyYTI3YjFiNw',
                                lat: 1.17408235,
                                lng: 103.7972917,
                                content: 'Tandragee Primary School<br/>TPS591<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWYxMGEwNDdiMGQwOGZjY2VkOWFhYWQ0OTlhMzc2NmIwODlkMWI5ZmYxMTEzODg1NjA2NzVjYmFhZDZiZTFjNw',
                                lat: 1.4151799,
                                lng: 103.6612328,
                                content: 'Tannaghmore Primary School<br/>TPS57337<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzQ2NWM3Yzg5ZDU5YjVlNDhlYTQ5Y2YzMGU4ZTg4ZDAzYzhmOWZkMWQ4NzZmNjhkMGJhMTdiYjI0ODM2ZWUzZA',
                                lat: 1.26471116,
                                lng: 103.7848711,
                                content: 'The Thornwood High School<br/>TTH54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzIzZmY0ODY2ZWUxOGRjZjMzYTk0NzBjOTE3NTBjZTNhNGJkODgxMzE0OGE4ZGE4MTc0ODk0NjdmYzg1OWEyZA',
                                lat: 1.2748271,
                                lng: 103.7202078,
                                content: 'Thornewood School<br/>TS68084<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmFkY2MyMjE0NDFmYzJjZTY1NTJmYjg3OWFjODA2M2EyNjViNTk1ZGJlZGVlZDNiNjMwMDMyODMxMTEyMWFmOQ',
                                lat: 1.46202771,
                                lng: 103.8225731,
                                content: 'Tildarg Primary School<br/>TPS10812<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTFhYWJiOWJmZjNlZjBkMzA3MTA1MmFiYjU1ODU3MmY5YjU4YmFkOThkZDZhYzBiMGZkN2Q1NGNjY2MzMTJiOQ',
                                lat: 1.38909134,
                                lng: 103.7681177,
                                content: 'Tonagh Primary School<br/>TPS66213<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjJiODkyMzczYWRlN2MxOWM5MTc4NGI4MWZmMzI5MGQ5OGVkZGEyMWY2ZDgwNWRmNDhkMWJkODM0YWJjZDA4Ng',
                                lat: 1.30773212,
                                lng: 103.6584859,
                                content: 'Toreagh Primary School<br/>TPS40046<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDRmODVjMGM2MmRkNzI1YTJiYTYzY2MxMDAwOTBjYTU0MzYyY2ZmNzkzZTViNjhlNDdkNTc5N2EwOGVkYjBmNw',
                                lat: 1.20862384,
                                lng: 103.898919,
                                content: 'Towerview Primary School<br/>TPS51393<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Mjk3YjFhOTI1MTE4ZDM1MTU0ZWNiN2FhMzc1YWM1NjVkNDMxNTlhZjAzNzc0ZjkyMzgwN2YxZDA1M2QzYTI2Nw',
                                lat: 1.34444742,
                                lng: 103.7288272,
                                content: 'Tullycarnet Primary School<br/>TPS62011<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTc2MTQwZDhhNzc3MTcyMzRjYzU5NzQ3ZTM5ODE2MzkzMGU2MjY2N2Y4MjFkNGMwZWFkMTU4Mjk4ZWMyNzBkNA',
                                lat: 1.28282891,
                                lng: 103.974427,
                                content: 'Tullygally Primary School<br/>TPS48951<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzVkZTA5ZmQzMjZlNGE2NzRmYWU3NmU0OWQwNDYwNmQxYTJkYjc1ZGIyM2UwMWM5NWQ0MzZiOGQ4NDFkNWIwZg',
                                lat: 1.21336057,
                                lng: 103.8890579,
                                content: 'Tullymacarette Primary School<br/>TPS781<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzBhZGE3ZTg3MWYxM2Y1NGUzMWIyMDhjNTBmM2IyNzc5ZDRmNjhlMWViODY0YmNmMTVlMGZkZjViMGNhZWMzZg',
                                lat: 1.34546798,
                                lng: 103.7593875,
                                content: 'Tullyroan Primary School<br/>TPS10819<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTI5Njk2NTU1Yzk4NjExYmM5Y2E1NjcwZTAzMTFjODA5ZjQ5NjMxNDBiODU1ZTAxZGNjODM5NTc0ZTk0Zjc0Nw',
                                lat: 1.39497575,
                                lng: 103.9264075,
                                content: 'Tyndale Theological Seminary<br/>TTS59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjE5Mjc2YTNjMGE2MzdjZDc3MzAwMmNkODcxZTEzOGYzNDczMjM2ODk0MjNmZWJkNWY4OGNjM2Q2YzVlNGZlNg',
                                lat: 1.38477036,
                                lng: 103.8665211,
                                content: 'Universit de Wallis<br/>UdW21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGIyYjFlYzNkOTQ4ZTQ2OWY2NmY1ZTA3MDFkYmE3NmMzNzZkYmYwYzc3NzU0NTVmNmI0NjcwYTY3NTAwMDg5Mg',
                                lat: 1.20251021,
                                lng: 103.8491665,
                                content: 'Uttar Pradesh Vishwavidyalaya<br/>UPV68180<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODRmMDFhNDBmZjBmZjQ5NjU0NTdjZGUwNWE1Mjg1YmUzOTRkNGZiYjViZjc1MGU5YjM1NzM1YWJhNWYyMGY1Mg',
                                lat: 1.27505957,
                                lng: 103.8166933,
                                content: 'Washington School of Theology<br/>WSo43063<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODVjN2FjMWJlOTI2N2QzNzFmMzQzMTZiMjBlNzg0ODY5M2U2M2ZkYjE5NTRiZDJkNWJhZGRmZGY1OTk5YWY5Mg',
                                lat: 1.47479939,
                                lng: 103.8630072,
                                content: 'Wawoi Primary School<br/>WPS59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjVkOGJhNWI0ZDIxZDJkMWM4MTg5Y2MyZWNkNTZhNzJmMThhZTk0NjdjYmFjZGQ1OWFlNTY4Y2RhZjIyNTcwNg',
                                lat: 1.37071556,
                                lng: 103.7160475,
                                content: 'Weikint Primary School<br/>WPS61822<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzIxYmViNjg4MzgyMTNlZmEzYzlhYWFiYzZlYmRlZWRmM2ZlNGI1ODUxYmM4N2JjNWMzYTJhMDViMmVhYjllOQ',
                                lat: 1.32255879,
                                lng: 103.9114055,
                                content: 'Whitton School<br/>WS10812<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzhiZDI4NDU4MDM3YTBiMTcxMTJlNjhjYmFlOTJhNGE1YzdiNWFhMGVmZDJjOWQxOTljZTBjODViZDUyZDBjYw',
                                lat: 1.48359389,
                                lng: 103.822813,
                                content: 'Woolston-Steen Theological Seminary<br/>WTS59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2E3NDBkODQ0NDFlNmM1YTQ5OTQ3MjNhNzAyNTJmMWE1NzdlOGYzMTEwMzNlY2FkYzEzNzFjMWMxNjBlZTRlZg',
                                lat: 1.48368934,
                                lng: 103.9208425,
                                content: 'YUIN/High School<br/>YS36800<br/><a href="http://www.google.com" target="_new">link</a>'
                            }
                        ],
                        marker: {
                            icon: 'university',
                            markerColor: 'purple',
                            prefix: 'fa',
                            iconColor: 'white',
                            title: 'Group 2',
                            id: "group_2"
                        }
                    },
                    group_3: {
                        data: [{
                                id: 'eyJpZCI6IjMiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.MWJkNDM0ZTljZTAwZmRkOTIzZWMwYmZhMmMxYzA4MDkwZGIxMDJiYzc4YWFhMzAwOTVlNzUzNGMwZWQyNDk2OA',
                                lat: 1.38265185,
                                lng: 103.6741035,
                                content: 'Acan Secondary School<br/>ASS50268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjYiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.ZWI0NWIwMDVjMzk2NTQwZmE4MDkwMDg0NjZhMDM1Mzk1NjhkZGE3NmUyMTE4MTE3NzBjMzc4OGIyYmM4MTc0Zg',
                                lat: 1.32414935,
                                lng: 103.6423005,
                                content: 'Andover High School<br/>AHS57955<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjciLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.MmVhYzQyY2Q4YjdmMzUwYWQxNWQxMGI3MDk1ODZmZGE0ZmNlMjdkODg2MGM2NWM0NmFkNjllZTBmZmE3NDU2NA',
                                lat: 1.22503517,
                                lng: 103.7973782,
                                content: 'Androgogy Junior College<br/>AJC36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YjE4Y2M3ODFmNzE1MzAwOTk2NTM5MGFkNjBjOWViZWFjMjZkYmZiYjc4MDFkNDIzNDc4ZWMyYjk1MzY4NTcyNQ',
                                lat: 1.34925518,
                                lng: 103.9679315,
                                content: 'Ashington School<br/>AS21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODU3YTY1YTNmNTg3ZGM0ZTNmZDY2NDI4ODQ0ZjFiZGRhZGEwM2UwMGE0ZjBhYjM5NmZhYjg5ZWI5ZjUwM2JlZg',
                                lat: 1.20224244,
                                lng: 103.7206578,
                                content: 'Aston School<br/>AS41520<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MmI5MjhhZTQyMDhmMjg0OTg2Y2FlNWYxY2I2MDAyNGJjZmRiOGFmMzg3YjY0YzZjZjM5MzhlYjlmZDIxMjhjYQ',
                                lat: 1.23295775,
                                lng: 103.8989968,
                                content: 'Atlantic Pacific Junior College<br/>APJ781<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZjgwNmViZjkxYTdiYTJkMjIxNmNjYTQxY2UzZTUzMDU1OTY0ZjcwYjBkNGNhMzc0OTVmMTgzYjI4YzA5YzNlOA',
                                lat: 1.30403586,
                                lng: 103.8015825,
                                content: 'Badihagwa Secondary School<br/>BSS52124<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZTFjOWIxNmM0MmVkNmNiMjZiNmFlYjBmNTRlMjg4OTRlMDRhNWFkOTkwMjFkMDExODkwOWUxYTVlNDYzMTE1OQ',
                                lat: 1.42547094,
                                lng: 103.8940942,
                                content: 'Barber-Scotia College<br/>BC41190<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NjdkNTE5NjEyMWE4YzMyM2IxMWJlMWI4MmIzNWNiYzA5MjA3M2JjOTVjMmE0YjRmMGE0ZjZhOWJhYmE5ZTgyMw',
                                lat: 1.39436712,
                                lng: 103.8257474,
                                content: 'Belford School<br/>BS54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MWNkNzU3MWJhMzMzYThmZTQyZTE4NmJiZDU3NTQyMTcwMTFlOWJjYmFiNjhjNjhiNGZjMzBmZTgyOWU5ODg0Mg',
                                lat: 1.30521107,
                                lng: 103.9351515,
                                content: 'Bernelli School<br/>BS43353<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.N2JiYjYxZGY3OTkzNjM0NDQ2NDI3ZGJkNWQ1NTllYmUyYmQzNTJmYmM0ZmVjMDBiYmUzZDJiNzA3ZWJlMGZjMw',
                                lat: 1.22142553,
                                lng: 103.8826835,
                                content: 'Bienville Junior College Woodville<br/>BJC60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODk0OTI3MzBhMjA1ZGY0OGFjMTlmZGMzMjQ5NDg2ZmI0MTE3ZTliZTNkZGU5ODkzNjFlNTAyNjM4N2QzN2ZjYg',
                                lat: 1.39610034,
                                lng: 103.6802911,
                                content: 'Brazilian Law International College<br/>BLI43063<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MWFiZGE5NmI2OWViNTJlNmMxOWQ0OGQzYmQ4NmU0NGUyYmMxOWE1Zjc3ZTc0ZWMyYTY1M2E5NWJhMmRjZWRjYg',
                                lat: 1.5035677,
                                lng: 103.8394271,
                                content: 'Breyer State School<br/>BSS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NjMyMzczZmE3ODlkNmY0ODk5OTIzNzdlNDllYzBhNWM3MjJmN2IyYWNhYTM2ZmI3NmQ5YzEzMWU0ZDIyOTdhNw',
                                lat: 1.36538547,
                                lng: 103.8618404,
                                content: 'Britain College of Management and Science<br/>BCo60505<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODRhNzFiMmI4N2QxZmIyMzQxNTJhZTEwMzQ5ZGM0Y2Y1ODY2YTc1ZDVmM2FhOWRiZWViZjQxOTM1NDIxYTkwNg',
                                lat: 1.47065142,
                                lng: 103.7614475,
                                content: 'British West Indies Medical College<br/>BWI45802<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzBjMmIzYzliZDVjYTA5ZTEwMGY3YTlhNGU2NWVkMWI4ZmYxOTg1NWIwNTMzMGFmYmY0OGRlYTQ4N2M5ODFjMQ',
                                lat: 1.3541568,
                                lng: 103.780784,
                                content: 'Buxton School<br/>BS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OTk4ZjRjMTNjYWNmYjQzNDlhOTVkMmY5ZjhhMDYzNWJmNGIyNGIzMjYwZjg2YzE2YWFkYWQ5YWU3Yzc4NjgxNA',
                                lat: 1.4027355,
                                lng: 103.683878,
                                content: 'California Takshila Junior College<br/>CTJ18860<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZmUwODM2YWVlMjUwYzU0OTJiMGNkMjk0OTJjZDRmMzQwN2U3N2RjOWE5MzczZWY1NzlkZTg2ZWM5MTA4MzEyMg',
                                lat: 1.28600206,
                                lng: 103.7255049,
                                content: 'Cambridge Graduate Junior College<br/>CGJ53808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MTViMjk3OTEwN2I4NzhlYjgxMWFjOGZjMjJiYmJmOWNiNzI1NDQ3MTRhODkzZWI4NmY1MjA4YTA4NzZkOTBkZQ',
                                lat: 1.41607152,
                                lng: 103.7936036,
                                content: 'Cambridge State Junior College<br/>CSJ34362<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YWU1MWU3ZjAzYjI0NDY3OWZhNzZlYmVlZGY4YmMwOGNhZGEyYTIwMDAzYmU3NWYzYzYxOTY3NGM3ZTUxNmE1OQ',
                                lat: 1.42795115,
                                lng: 103.6789997,
                                content: 'Canadian School of Management<br/>CSo55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NWJjZjA4MmFhMzE3MzA1NmRkMjM1NDNjNmVjOWRkY2QzMTJlMjJlNTFjN2E3NjRhOTlkODFlOTU1M2IxZjk1Mw',
                                lat: 1.29888966,
                                lng: 103.8624044,
                                content: 'Capital Junior College<br/>CJC36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NmJiMmVhMjEwZDljMzk4YmRlOTY3YzEyNDQyNTJmZGNhM2EyYTA2ZGRmOWI3NDk3Nzk2Njc5MDEyZTY0MDI5Mg',
                                lat: 1.48727055,
                                lng: 103.7202839,
                                content: 'Carlingford School<br/>CS49999<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjYyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YjZjYzljMGVlMmE5NDEwN2FkZjIzNDA3ZmZmNGM3YmEzMGJmOWM2ZGU3ZTkxMGM3ODJhZDJkZjk3YmMyMWI1Yg',
                                lat: 1.4280263,
                                lng: 103.8699619,
                                content: 'Central Junior College<br/>CJC51079<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjYzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MjMyMWJkZDllZDIzZGI0NDVhMzk1YWIzZmZiN2RhNWM5Yjc2ZTNhZTRiMThjZTE1YzAzYzgzYTFmOTkzZDJjYw',
                                lat: 1.24648078,
                                lng: 103.9224086,
                                content: 'Century Junior College<br/>CJC597<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZTBmMzgyYWE5ODEwYjVhNjlkMWU4ODdlZGFhZDE4ODRlYTNmNThlNDBkOTc1MTkzYjdjNjQ4MWJiOTNiMWNkMA',
                                lat: 1.32560603,
                                lng: 103.6773912,
                                content: 'Chesapeake Baptist College in Severn<br/>CBC60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MmEyOTNlNTI5NmUyMjg1NmEyYjg0MjdiM2YyMzQxOGZlNjkxNTk2N2NhNWI1MDMxOWNlOWM0NjUzOGIwNjBlMg',
                                lat: 1.26817494,
                                lng: 103.9248648,
                                content: 'City High School<br/>CHS42233<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjcwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.M2JiODBmODk5ODAxMWJmNDE4OGY3MjBhNjFkYWI0MjNiMGI1YTNlMzE0MTRiYjMwYWJlNjdiODQxZGE0ZDAxNA',
                                lat: 1.47755267,
                                lng: 103.8119911,
                                content: 'Clayton College of Natural Health<br/>CCo55711<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjczIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzUwODljNzI3YzI0MTJmODY1MTkwNDhiZGFlYzNmZjVhNjA3NzY3ZTQwYzIwZDZjODIyM2E1MGQxZDk0ZGFmOA',
                                lat: 1.47484724,
                                lng: 103.9025787,
                                content: 'ClosedCollegeDiploma.com<br/>C60709<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OTc5NGRmYWJiNTQzMTI0MDRhOTA1OWZkNjU1NGZjOGNmYTU3YTcwOWI5ZGU2MzdmZjY2MzYwNmNkNzlmM2I4NA',
                                lat: 1.27494352,
                                lng: 103.7482819,
                                content: 'Coastline Junior College<br/>CJC48951<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YmVhNTA4NzNlODcxMzU0ZWY0ZDdlN2Y0OWExNTUzM2M4OWFjZDY0NThkZjhiZTRjMWU5MmU4NzdjZjVjYjg2Zg',
                                lat: 1.30060199,
                                lng: 103.9738115,
                                content: 'College of Brazilian Studies<br/>CoB43742<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzY2YTdhZTMwNDBiZDBkMzFhZDI0ZTEzNjIzODEwNzhhNjVhNGYzMjE1Y2U4YWE0ZTc5NDAzZTUzNDFiY2RjZA',
                                lat: 1.19414052,
                                lng: 103.814419,
                                content: 'College of Commerce and Technology<br/>CoC69106<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NTRmOTk2MGY1OWE0ZTExNTVjOGNiYWIzZTk1M2YzNTFjYTRmODdkMDdiMmFlYTNkYWU3NWNiZjliZDJkMDc2Yw',
                                lat: 1.38052324,
                                lng: 103.8944678,
                                content: 'College of Metaphysical Theology<br/>CoM64679<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MmU0ZDAwOTVlMGI2MmNmZjU4ZGIzNTQ0YmI4NzU1MWIyNmZiYjZiMTJiYTljZjM4ZjUzMGU4ODM1N2M3ZDliZg',
                                lat: 1.27266133,
                                lng: 103.6701262,
                                content: 'College of Naturopathic Medicine<br/>CoN59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijc5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODUyOWRjYjFmMWY1Yjk5NDQxYWE4MjNkZDVkMmY1ZWEwOGQ0YTNkYzgzZTRkZTE3NGEwN2VhNzY2MDYxODY5ZA',
                                lat: 1.28379013,
                                lng: 103.8636102,
                                content: 'Collumbus School<br/>CS52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjgyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.N2Q2ODgyOTk3NDRkN2UwMmJmOWIzZjBjYTM2YjYxOWVjNjg5YjliMDM4N2M2NDJjOTQ2ODY4MjM0YWUxN2E1Ng',
                                lat: 1.27795166,
                                lng: 103.8957919,
                                content: 'Columbia Pacific Junior College<br/>CPJ8<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg0IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OGM1MGUyM2U4ODhhMTVhNmUwNDEwMWQ5NzlmOGI1MDE1MzRmNmQzMmE0Y2E0OGFlMTg1NDUzMzYzMjUxNDhmNg',
                                lat: 1.21666371,
                                lng: 103.7419538,
                                content: 'Columbus Junior College<br/>CJC363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YTkyOTBhMWY3YTdmZTk5NDU3MGJmYTMxNGNkMjc2ODI0NjhmY2Y3ZmViNmNkNmZjMjJjNWIzZGYwYzdjNzJmZA',
                                lat: 1.19709052,
                                lng: 103.8993668,
                                content: 'Columbus School<br/>CS42403<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.Nzg1YjY0OTVhYTA5MDNkNDJjZjcwMjUzM2ZmYTlmZWMxY2QzNzQ0MjMyNTk0NTg2MWJkMzU0YTJhYzMxMGFiMw',
                                lat: 1.26691236,
                                lng: 103.8218109,
                                content: 'Commonwealth Open Junior College<br/>COJ68180<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjkxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MmRmNjY2ZDAzZGE4OTMwYzQzMWFmNmIxZWQwNzNiNmI4NzhhYTM5Mjg0YzMxNjk1ZmQ1YjQ2ZTVkMWYzNjU5ZQ',
                                lat: 1.21837921,
                                lng: 103.9298936,
                                content: 'Cumbres de Chile Junior College<br/>CdC33261<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjkyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OGE1OTgyZWQ2YzEzYTk3MzQ1ZDQ0MzhkMzgxMWJkM2YwNDBmOTA5N2VmOGM5YTZhYTAzY2ZlMTJhOTE4Y2JjYw',
                                lat: 1.4885187,
                                lng: 103.8629808,
                                content: 'De La Salle Secondary School<br/>DLS64725<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YWIwZWI1ZjYwZTNjNWYwZDY0NTU0MzAwZTE5Mzg4YmRlNmYxYjFkMjk4NjFhMjVmMWYyNzkwYmUxMGUwYTg1MA',
                                lat: 1.49646457,
                                lng: 103.7585964,
                                content: 'Dispensational Theological Seminary Forest Grove<br/>DTS62670<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijk5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.Nzk0ZmY2ZjFjNDQ2OGFlOTM2MzQwMGNjNzE3ZjExMmVlMGU1Zjc2OTA1MmEzNDM5NjViMDNlMjU5OTAyNzM5Ng',
                                lat: 1.33883801,
                                lng: 103.8955446,
                                content: 'Dublin Metropolitan Junior College<br/>DMJ11822<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWI2NWVjODhmNzY5NzAyZTVhNDdkZjk1ZTIxMTU2NDkxZDhmZmZhYmFmNjYzNTI4NTAxNzE4OTlmYjZkMzQzYw',
                                lat: 1.51781726,
                                lng: 103.8888224,
                                content: 'Earlstown School<br/>ES65342<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzRlYmU4NjFlZThmNjM4OTA4M2MwNjk3YzdiNzAzY2JlZjEzZmRlMzhiN2U4YmU0ZWI0YTdiNmFlNmJkN2E4NA',
                                lat: 1.28811123,
                                lng: 103.9536161,
                                content: 'Eastern Caribbean Junior College<br/>ECJ63777<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTYxMmM2NzRlYzUxNDVkZTNjNTYwNjI0MDczNTVjNjI1MTc0OTVlN2RlMzgyYzM5MDNkM2RlZTI3MWExYTIxMw',
                                lat: 1.48005565,
                                lng: 103.8931069,
                                content: 'Edenvale School<br/>ES62007<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDk3NGRjYjBkYWY3ZjY0MDU2MTYxZWJmMGZkZmM0MTU4MzE0ZTkxZDY0ODQwNzA3YjQ3MjlmOGI5NDAyOGU4ZA',
                                lat: 1.24356402,
                                lng: 103.9393197,
                                content: 'Erave Secondary School<br/>ESS11410<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjExOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGQzYmI3NjBkNmI0YTNiOTE3ZWJkMmUxNzAyYmJjODc2MWEwYWMxZmMyMDVjYjdjNmU0ZmQwYWY1NDIzMDcyYw',
                                lat: 1.28327101,
                                lng: 103.9756711,
                                content: 'Eurasia Community College<br/>ECC36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjM5MjQ2YTExY2VlNjEzYmRkY2ZhNTE2ZjU4OTNjMThlMDJkNGYyNjNjMGM1Yzc3OWZkYWU5YzdhMjVlZDQzZA',
                                lat: 1.41663646,
                                lng: 103.8071554,
                                content: 'European Graduate School<br/>EGS10544<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGUyMDY3NmE1ZTYxMDkwNzdhZmIyZjlmOWJmM2EwNzZmYjcyMTM1OGMwZTA4NTkwZTkwOWYxMmQxNWI5OTZmNw',
                                lat: 1.29867847,
                                lng: 103.9436966,
                                content: 'European Junior College<br/>EJC42747<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWE0MDJlZjY1ZGVjNWI4ZDZlOTgwYzJjZTI4OWM2NzMxODI2MDdjNGRlNmYxYzZiODZmMDNhMjFjMTk4OTU4Zg',
                                lat: 1.19167515,
                                lng: 103.837846,
                                content: 'European Junior College of Ireland<br/>EJC145<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWEyNTJlMmU3NDQ4MDgxODc1OWY0ZWI5MDg2OTkyN2I3NGUwMTdjNzQ1ZmI4MzY5Zjg3ZThkNjEzZGUwZjI5Zg',
                                lat: 1.19745274,
                                lng: 103.8672472,
                                content: 'Fairfax School<br/>FS52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzdjZjc1OTlkYzI3YjVhYTI0NmJjYTY5NDE5ODM0ODI4ZDJiZTkxZTgzYmE1NDNkNWJmOTNmNmVhMzQwMzE0Yg',
                                lat: 1.18929086,
                                lng: 103.800047,
                                content: 'Frederick Taylor Junior College<br/>FTJ54973<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDI2ZTM1MjZlZGY4OTkyNTJhNmEyY2JiNzc0ODllNWUyNGQ4ZjUwYTNlOGQ3MzExNzQ4NzU0MjEwYTNmOWZkYQ',
                                lat: 1.23268182,
                                lng: 103.8988846,
                                content: 'Gem School<br/>GS55714<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWNkM2M4NzBhNmMwZThlMDA4OWM3MzFjZjcyOTM1MTgzODUxYzUyZDI1ZWNjYTEwY2JhNGM3M2Y3NTVhYjlmYQ',
                                lat: 1.34524917,
                                lng: 103.9006512,
                                content: 'Gerehu Secondary School<br/>GSS536<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjFiZTJhMWQzYjFhOTZmNjdlOTAwMjg1MDI5MjE2NTQzMDgxODhhNGFmY2JjOWUzNWRkODNkNDIwMzYxNDA3Yw',
                                lat: 1.40177538,
                                lng: 103.6674664,
                                content: 'Glencullen School<br/>GS74<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjI5ZTA3MzZiNzU5ZTIwYjM1YjY2NmYyOTQxY2NiNThhNTMzNGE3YWNkZDhlNDBhNGJmNjkwNjdiZDgxYjBmYQ',
                                lat: 1.38184797,
                                lng: 103.9424933,
                                content: 'Global Junior College<br/>GJC55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGVlNTYyODI1NjNlMGM0MjZiZjNiZTUxZWI4ZjdhNDcxMmE1YmVjNmE2NGM0N2ZlYzgzZjIxOWJjNWNlNTdjZA',
                                lat: 1.29295825,
                                lng: 103.9737389,
                                content: 'Golden State Baptist College<br/>GSB50462<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmY1M2Q3MWEzZjU2MDQ3ZGI4N2U2NWI3NmZlMDU4YjczZDgxMTQxMjJjZjhmMzQ2OTFkMTQwYTdkM2M4YzAxNA',
                                lat: 1.25285193,
                                lng: 103.8950644,
                                content: 'Gordon Secondary School<br/>GSS57520<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTgwZTBmMGQwYmI4NzkxNDc4OTViYzhlZmQyYzZkMjAwYTJjZTVlNjRmZTllOTYyM2IxMWQ5MTEyZjJjMDFkOA',
                                lat: 1.21041874,
                                lng: 103.7775721,
                                content: 'Greenwich School<br/>GS941<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjViOWQwZWNkODlmNmE2YTljZjJjYWM3YzU3MjliOWNlZDFkY2Q4NzIzODIwNGMyNTE1YzA5MjQyNTYyZDNmMA',
                                lat: 1.34583404,
                                lng: 103.8357372,
                                content: 'High School of Antarctica<br/>HSo13537<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NmUxNjc0OGIwNzFhOWU1MjYyNjQ0ZDY3M2ZkNzVkNDM2MzkzYjMzNzI5ZTk5ZGIxMDFmMTViM2EyZWMyZDk2NA',
                                lat: 1.50256944,
                                lng: 103.8757297,
                                content: 'Honolulu School<br/>HS60793<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGE3M2VlZDhkYWIyN2Y5OTFkYTRkMTc2ZWE3MDEyN2YyMWY2YzdhMTBlMGYzMDEyYzU1NWJhMWIwMGYwMGI5Nw',
                                lat: 1.28292877,
                                lng: 103.9670801,
                                content: 'Hubbard College of Administration International<br/>HCo37205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjA1Mzk4ZjhhMjAwMTM5OTg3ZWE2YjI0ZTBlY2Q3YmYwODQ4YWE4YzFiOTE3YTY1ZGM3YTcwMjRiZTJlMDc0Mg',
                                lat: 1.52869744,
                                lng: 103.7978673,
                                content: 'Huntington Pacific Junior College<br/>HPJ10812<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWQxYWExZjI0MTk0ZDk5NWUyMGZmMDk1ZjRhMzIwOTFmYjhlYTk5MDg4YjI4Mzg4ZjhlOTA4YmEyYmM1YmM4Yw',
                                lat: 1.45238305,
                                lng: 103.819989,
                                content: 'Hyles-Anderson College and Hyles-Anderson College Seminary<br/>HCa53329<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjhjYzg2MzI3NjhlMGYyY2EzOTZlZjU5YzkwZGE2NmY3ZjYyMzRlNGE3NmUxMzliOWMzYzk2ZTA3ODI3MGU1NA',
                                lat: 1.34865143,
                                lng: 103.6626844,
                                content: 'Instituto Latinoamericano de Psicobiofisica<br/>ILd904<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGFkYzZiNDdmMzllOWIwOWM1ZjAyZDI2OGE4ZTYwYWI1YzQ5M2MyNWFhZDhlNTI3ZTAxZjQ2YzE0Mzk4Yjk4NQ',
                                lat: 1.22046386,
                                lng: 103.810532,
                                content: 'Intercontinental Junior College<br/>IJC75857<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzg5OGM5ZjEzYjczY2U4MjViZTQwMWQzMjE4ZTA5ZWMzMzI4ZmE4YWUxYzgyODRlZmIzNWFjMDI1YmUyZmEzOA',
                                lat: 1.43683779,
                                lng: 103.7290178,
                                content: 'International Open Junior College<br/>IOJ51655<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGI3ZDI4NzBiZjUxMDBjNWZlNDZlNjFmMzhkZDI5MzUxZmU5ODgzYzc2Y2IyODMzZDgwNzUwNGNhM2JkMmY4Zg',
                                lat: 1.377676,
                                lng: 103.669507,
                                content: 'International Seminary<br/>IS615<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjkzYmNhYWM5NWZkZDVhMTEyNDZlNjhjYjM5NTlkNWRmYWZhZGM4ODJkNjA0Y2JkN2ZiMGRmNjQ1NTM1ZjE3OQ',
                                lat: 1.37613438,
                                lng: 103.6540235,
                                content: 'Irish International Junior College<br/>IIJ64203<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTdiM2E3MDhjZDI0M2RmMWFiNWI2MTdlYmYxZWYyNWEwYTdmODAyNjI4ZTcwZDA5NDQwMzk3MTQ4YTk2M2E4OQ',
                                lat: 1.39705638,
                                lng: 103.740116,
                                content: 'Isles International Junior College<br/>IIJ51293<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDlmNDcyMTYzNDkyOWQ2ZTMyNDEyZDA3ZjAyZTI0N2FmOWI2MjI5YjU1YTM0YzZkNTQyMjE4NTVlYWYzMGJlMQ',
                                lat: 1.26117349,
                                lng: 103.9464433,
                                content: 'Junior College in London<br/>JCi10096<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGMyZGU4OWU0MzczOTZmMjg4OGUyN2I5OTM0NDRiMDIwMzU4Nzc2YjIxMzk5MDNlMmI0OTE2MmI5MWM5YWRiMQ',
                                lat: 1.47282379,
                                lng: 103.7142177,
                                content: 'Junior College of Action Learning<br/>JCo187<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjA5ZTA0NzEyNDkyZTU1YzlhZjBjZDg2Zjk2ODliYTViMDhmYmM1OWEyN2JhOGVlNDg5NWExZDI2MjhlMzM4YQ',
                                lat: 1.2078026,
                                lng: 103.860423,
                                content: 'Junior College of Beverly Hills<br/>JCo49967<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2U2NjkzOTQ5MjFlMWJkOTk4ZWNlYTNjZTc4YWI1ZGE5NGQyZjZmODhmODE5OTczM2MzYmNlMzJhNmVlNmY2MQ',
                                lat: 1.39982875,
                                lng: 103.7424129,
                                content: 'Junior College of Bums on Seats<br/>JCo70253<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDk2ZGRlYTdhNmU2MDIyZmE0MTZhNWMxZGUwYmRhNDk5ZWMxZjU0ZDIxYzY0YmYxNjU2M2UzOTliZmVmNGVmMw',
                                lat: 1.25949352,
                                lng: 103.9457483,
                                content: 'Junior College of Central Europe<br/>JCo615<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NThhMzI0ZGJkZjk1MDc1Mjc1NTA3Mzc0YzVmY2M0ZTI0NjE0NWE1NTFmM2U2NDJhMjIzZTBhMzdmMGVmYzQ4Yg',
                                lat: 1.39649106,
                                lng: 103.7881323,
                                content: 'Junior College of Humberside<br/>JCo268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODhlMWExMTJkNzM1YjFlM2IyOGU0Zjc4MDk5YzhhYTY1OTY5MTc2NzQ0YmJhMzdjNGZiOTVlNTg0MTk0ODA4Yw',
                                lat: 1.37625878,
                                lng: 103.8022454,
                                content: 'Junior College of Lamberhurst<br/>JCo36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmMwOWViZmVlMGFmOTkwMzE0MDEzZjNkY2ViMTI0M2QyYTNlZjIyY2MyYzcyZDg0MzY3OTg1ZWMzNWU2ZTU5ZQ',
                                lat: 1.47211274,
                                lng: 103.8026776,
                                content: 'Junior College of London<br/>JCo57520<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmI4MmUyYTIxMTQxYTkzNTcyMGI3OTI5NzY1NGEwY2IzNjdhYTYzZmRmYTM2MzIwZjY5YmM4YjMyYmVlOGE0Yg',
                                lat: 1.40696298,
                                lng: 103.7196976,
                                content: 'Junior College of Metaphysics<br/>JCo6<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzA0ZjkwY2I5NTRlMzNmZjBlNDBmNWEzZTZhZTUwODAyOTJkNWMxYzcyNTIzN2Q3M2MyYWEyNDMzMDkyZDQ1ZA',
                                lat: 1.45564025,
                                lng: 103.7274766,
                                content: 'Junior College of Natural Health<br/>JCo51346<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2I3MTAyYjAzMjMwYTc4N2VmMGIzOTZmYjBhODJlZTZiZDEzNzIwNDM1ODkyYWM5OWE1MzMwMTA4NzU4ZTI4Mw',
                                lat: 1.28833765,
                                lng: 103.7892519,
                                content: 'Junior College of Natural Medicine<br/>JCo53329<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTU2OWQ0MWM3ZTdhZWE0ZDQxYmQ1ZGRlZDMzMWU4ZDVlM2I2OGQ3MTM1ZTNiNmZlM2QzMmMzOGUxYzJlNGE3Mg',
                                lat: 1.46511608,
                                lng: 103.8701595,
                                content: 'Junior College of North America<br/>JCo50558<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTlhNTg0MzhiNGU3MjI0NzAxNWYyNTk4MTQyZGMzYTlkZDJhYjIyZThmZTJlZGRlYzhlYWYzZmQzOTM1MzE3OA',
                                lat: 1.32779534,
                                lng: 103.8380769,
                                content: 'Junior College of Northern Virginia<br/>JCo63187<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTliNGYwY2E3N2U0YmIzNDM4OGFmMzkyM2M2OTQ0Y2ZmYWI1MmI3YWE3OTVkNWVjYTQ2MjhmYjQxMzQ4MWU5Nw',
                                lat: 1.21101278,
                                lng: 103.7682961,
                                content: 'Junior College of Santa Monica<br/>JCo52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjE5NjgxZGE0ZGYzMWQxZjU3NzE3NDM5NWRkNTM0MzZjYWE5MDRiZTIwYjZmMDcxY2JhMjFlN2I3NDViNDkxNw',
                                lat: 1.19099608,
                                lng: 103.8325703,
                                content: 'Junior College of the Nations<br/>JCo41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTdmNjY5ZWE2Zjg3YWYxZTE3Zjc2ODZiODc4MmM3NDgzMzA2OGExMTljZWJlZGEwMjk4NzA3Mzk4NGVkMTI2Mg',
                                lat: 1.28588917,
                                lng: 103.757117,
                                content: 'Kennedy-Western Junior College<br/>KJC66403<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIyOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWRmODU1Yjc0OTMzM2MwNjQyNzA3NDY2NWJiOTE4MGFkMzNkYTBkYzBkY2Q3NzhkNTk2MTIzN2FiYjNmZTRjMg',
                                lat: 1.38296436,
                                lng: 103.9050074,
                                content: 'Kensington School<br/>KS310<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Yjc4Y2M1Y2M2MGY1OTc1ZjZjNGNhODZkYjc2MDM2NmZjZWFmZjcwZjMzMmVlNGFhMjA1MjAzNGQ5NTZiODhlYQ',
                                lat: 1.33060322,
                                lng: 103.8034605,
                                content: 'Kerowagi Secondary School<br/>KSS49288<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTExMDJiMjQwODdkYTNlZjUyZDEyZWYxNDQ2YzQ4OGM2YjZhNTNkODk4ZGY3M2I4OTFlZWNmM2VjMTk5MWE3YQ',
                                lat: 1.26550152,
                                lng: 103.9441579,
                                content: 'Kila Kila Secondary School<br/>KKS179<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTNlYjBjNzdmM2Y2NzY1ZTBjYjllOGY5ZDFmYTM3YzIyZmFiYjQ2MmUxYTY0MTIxMGIzNjcwZDUzYTkzOGI3Yg',
                                lat: 1.39243068,
                                lng: 103.655634,
                                content: 'Kingston College<br/>KC395<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWQ4MmM2NzNjMDQ0ZGFjMDU1MzlhMjNlY2UzZTRmNWY2ZWZlMjdkYWVjZTU0NDY4ZGY0NmI0OTc0ODFlNzU0Yw',
                                lat: 1.36822733,
                                lng: 103.78879,
                                content: 'Knoxville College<br/>KC51861<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTljYzkxMGM2NGI2MTliZTRlYjBjNDkzNDQxYjViNjBhMDZiMWU3YzRmNDRiMjBkYjNlMzZiZWU1MDI3NmE3Yg',
                                lat: 1.37272397,
                                lng: 103.7441405,
                                content: 'Lambuth Junior College in Jackson<br/>LJC66715<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWU1NTk0YmM2MWIxOWNhMTJkZTdjM2U2ODQ5OTBjZTc1OTk5MGU5NGFiMDgyODE5ZWQ1YjMwNmQyZDljZjZhOA',
                                lat: 1.34235714,
                                lng: 103.695535,
                                content: 'Landford School<br/>LS21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGNkNjhlOTAzMjBhMGRkMWE0YWI5ZjJkMDUxMmY5NjBkMTgxZTI4OWFiZTAwMzFmNmNjODhkZmVmMTg4OThlYQ',
                                lat: 1.39851503,
                                lng: 103.7125119,
                                content: 'Leibniz Campus<br/>LC63868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI1MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWNkZGNkOWQ1ZmNhY2FkNjFlNDc2MGVhNzdhYWIxNmI4NjExYjBhY2MyODFjMjI5MjExOTkyZDdkMWMzNThlYw',
                                lat: 1.3742475,
                                lng: 103.8550857,
                                content: 'Liberty Junior College<br/>LJC62007<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjgzYjQzOWE3MGFjYWE3NTU4NDFhNGIxZjhlNGRmZTM3ODNmOTg4MDAxYjZiM2M4ODhmNzQzMGNhY2ZhZmNkYQ',
                                lat: 1.21029494,
                                lng: 103.7683709,
                                content: 'London College of Technology and Business<br/>LCo53808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjA4MTcyNTg5NjNjNWIxNDljZTg4NmY1Y2QwZjg4ZmEyZTFiMjM5MmY0ZDBlNjU5ODg4OTMzOTQwYzU0Zjg0ZQ',
                                lat: 1.4008195,
                                lng: 103.8178821,
                                content: 'Lorenz School<br/>LS58232<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2Q5YzNhMzQ5YzQ1NjE3MDZiZjU5NGZkMGFlOGFlOTg4YjFiMGI1NmZkNWM0ZjRjMDFkZTczMmUxZmY4OTMwMQ',
                                lat: 1.39941205,
                                lng: 103.6491426,
                                content: 'Louisiana Baptist Junior College<br/>LBJ942<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTk0NDI2MjBjMWUxM2YxMmNiYjk1MzNkNDkyZTQyNTRjZWJmZjFiZjhkYTZkM2M5N2M1NmExNDFhMDk5ZTdjOQ',
                                lat: 1.35362441,
                                lng: 103.8137322,
                                content: 'Maharana Patap Shiksha Niketan Vishwavidyalaya<br/>MPS54473<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjVjM2JmZGZlNjgxNzgxOTUwMGYxY2Y3Zjc0YWI2MTE4Y2JmMTAxZjRlMmFmNzU1MWMwZjk0NmMxODIzNzQxYQ',
                                lat: 1.27825169,
                                lng: 103.8916678,
                                content: 'Marianville Secondary School<br/>MSS574<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDg5OWQ3MGU3YTI4YmZkZTQwYTkzNzQ0YjllN2YxY2NiNDAwMzhjM2NhZmVjYTE0Y2MyZDU5Yjg3MmM0MmI1Mw',
                                lat: 1.50776419,
                                lng: 103.9022056,
                                content: 'Marquis Open School<br/>MOS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTljYzg3NDYzMzMxNTJkZGJjZDdkZjkxYjJkZDFlMWQ0NDk0NGZhNmE3MGNjMDQ4NWQwMWRiYTA3Mjc4YjFkZQ',
                                lat: 1.45341268,
                                lng: 103.6909897,
                                content: 'Mercy Secondary School<br/>MSS49738<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGY5MGE0ZGI5ZTljYTA5Y2EwNjZkOGMwM2U2NDVlMTQwZjYyMGQyNDY2YzYwZjFhNzRhYWJjNTlmMGY5Y2JhOA',
                                lat: 1.34784943,
                                lng: 103.6912076,
                                content: 'Metropolitan Collegiate Institute<br/>MCI68828<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2FhYjg5ZmE0NThhNjUyYjFhZDE3M2JhYzliZWMzMzZhMTM4M2RlYzYyOGZlMTAyMDFhODYyYjE1ZGJjYzM1Mg',
                                lat: 1.36965718,
                                lng: 103.8094879,
                                content: 'Morris Brown College<br/>MBC60505<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzM2ZDc2MmZmNmM4ZDZhMzQ3NTQ2MWE0NjFlZTJlOGRiNmNhODQyNWZiMjYxYjBhYTQxMDViMDNhM2JlNzkzMA',
                                lat: 1.38607532,
                                lng: 103.6741433,
                                content: 'Mountain States Baptist College<br/>MSB42291<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODM3YzBjMmEyNGFlZGQ3N2E0NTdiNTU3NmM4MDAzODU0NDRjNTA3NmZhYjNlMjVhNTlmNWM0NzgwNDlmNTkzNg',
                                lat: 1.45087872,
                                lng: 103.8127679,
                                content: 'Mt Hagen Secondary<br/>MHS37000<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDkxODA1ODhkZjk2Njc2NDEwNGYyMGMyOWM1ZmRjNTdkNzFlZDMzYjhlOTc1Y2M5ZTE1YTRlNjg0NjMyZDUyNA',
                                lat: 1.26161638,
                                lng: 103.7608162,
                                content: 'National Junior College<br/>NJC10237<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzAyNzkzZTg0ZmFhM2NkOGM4ZTM2YmUwNDg0NDQ2ZGVjM2M3Y2JhNDRlMjFhYjIzZmFkZjMwMDMxOTlkOWQ0Zg',
                                lat: 1.32843651,
                                lng: 103.914725,
                                content: 'National Junior College of Nigeria<br/>NJC57691<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzZmZjUxMjAxNTA2MDExZjc1Y2U5NTI2MzIzOTFjYzliYWJlNzg0MDI0MDY4OWQ2YjYzNjU2ZDkxYjliMGI4Yg',
                                lat: 1.37788515,
                                lng: 103.7459057,
                                content: 'New England State Junior College<br/>NES51401<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWQwNjg1ZDA5MTdiYmU5MGViZGI4OTMwMjMyZjJjZjI3MGFlYWY4ZmY2ZjBjNjVkMWE5OWJjM2M5Mjk5MjQwYg',
                                lat: 1.34455835,
                                lng: 103.8950622,
                                content: 'Newton School<br/>NS36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODQ1YmEyNzcxMDc2MjQ0ODA0MzZiNTQ3ZDkzN2UxMTY5NWMzMTJiYTY1MjYzZTg0Nzg5MzQ2ZjVmNWZjYmQ1MQ',
                                lat: 1.33846036,
                                lng: 103.951109,
                                content: 'North Lexington Junior College<br/>NLJ904<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzA4MmJkZmVjMzI1MmYwYzVlNDBiMzllOTM4MzJhYjQ4MjE1OGZlNThlYTc3ODc5OTQ0MTI0Y2YzMTg3MzdkMQ',
                                lat: 1.22878943,
                                lng: 103.7117895,
                                content: 'North Norway School<br/>NNS43498<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWNmM2I1NjMyYjNiNTEwNTcwODA2ZTJmY2U0ZmU4N2EwZjk1NjQ4Mjk3ZmUxNmEzMzYzYjExMTdkOTdhY2Q5NQ',
                                lat: 1.37349122,
                                lng: 103.6417745,
                                content: 'Notre Dame Secondary<br/>NDS363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzA2NGY1ZjkxYThmMzg4ZWI1Y2M0NDBhZjBkYzU3NTkyNWFhYzM0YjhjYjY0MzliYWU3NWM2ZWMxZjg0MDRjMw',
                                lat: 1.3383379,
                                lng: 103.8789929,
                                content: 'Oikos School<br/>OS641<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjI4MWE0NTMyNzZiMmY1MDczYzljZTIzZmZiYTIzODJiN2Q1NDI0Nzc4NGYxOGJjMTE1NDhiZTFkNzY0OTZiZA',
                                lat: 1.35923902,
                                lng: 103.702397,
                                content: 'Open International Junior College<br/>OIJ24385<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTJkZDI0NjQ1YTgxZmQ4YTE5OTdmYTFhYTdkYTA3NzIyM2ZhODI3ZWY3M2Y2NTE5NmJiZDI5NGI2MGM0YTEzZg',
                                lat: 1.37865057,
                                lng: 103.8955892,
                                content: 'Oregon College of Ministry<br/>OCo52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmY3NmRkN2YxZTQ5Y2RlN2QxZWJhMGM3ZWJmMTk4OGI3MjBkMTI2MDY4NTQ1YmJmMDI5OThmYWViYmZkNmVlNg',
                                lat: 1.28005227,
                                lng: 103.834639,
                                content: 'Pacific Junior College<br/>PJC58668<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MWVhYzg2OGUzZDAyMmI4ZjA4MzY2YzFmYWZiYTk4MjRmYmI0YjgxMDE1NmU3M2M3ZmE4ZmY1MjdjYmFlMjRhOA',
                                lat: 1.44158149,
                                lng: 103.7607181,
                                content: 'Pacific Southern Junior College<br/>PSJ50436<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjYzMTYwZDY0MDRmNjg0ZjI0YzI5Y2UzZmJmNGE4MWFlMzM5ZjhiOWQ5ODgzZjhkNjdlMjBjOWM3YjA1NTQxOA',
                                lat: 1.39195058,
                                lng: 103.6952689,
                                content: 'Pebble Hills School<br/>PHS49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODdmZjU1ODE5MjlhMjQ1NWQ2MTE1ODEyNzUwZGU0NzM0ZjJlZDdiNjVjMjhmNjFmOTkyNjAyOTgyNTlhOWU3MA',
                                lat: 1.30712538,
                                lng: 103.7713326,
                                content: 'Politecnico di Studi Aziendali ISSEA SA Switzerland<br/>PdS51224<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM1OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmJhMWY1MWY1YTU3ZTI2MGIyMDZkNjRhZTBjM2YxNjg3ZDk3OWI3NDk2ZTgzMWI0OTE3NWZmZjFiZmZhNWY0ZA',
                                lat: 1.42662776,
                                lng: 103.7104177,
                                content: 'Preston Junior College Pakistan<br/>PJC68877<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODkwZWQwYzIyMDc0NjU0YTU5OTk5N2U5OGQ1MWFiNjkwZTcxNDVmMTllODI3YWM4YTkwMDk5NmQ2MjQ2MTczOA',
                                lat: 1.39437417,
                                lng: 103.9204781,
                                content: 'Redding School<br/>RS58356<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWFjNDAxYjMxZDBiYTdmMTNmOTIwY2FmMjU3MDE0ZTYyNGY5NmY2ZjhkN2NmYTgzNGZmM2I0M2YyYjU0MjkwMA',
                                lat: 1.2677717,
                                lng: 103.90361,
                                content: 'Rochville School<br/>RS42291<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NmM3NWRjOWMyOTQyMTM5YWM0Nzk3MzFjN2NkNmJlNTFiNjhhNGQ1YmZhYzNhYmZmOGRlYjAyNTY4ODZhMDA3MQ',
                                lat: 1.4336449,
                                lng: 103.73303,
                                content: 'Rosary Secondary School<br/>RSS54340<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTg3N2QzNmZjYWY4OTA2MWZiNjNiMGMzMjM0NjQ4ZWEwMjZjMzE0MWE0ZTU3OWVmZWIzYjI5Y2UwNzYwZGRiYw',
                                lat: 1.33824359,
                                lng: 103.9361263,
                                content: 'Rutherford School<br/>RS60683<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTg2M2QyMTljYTMxY2U3MDYyMmYxOGMxZGYwZGQ0YTNhN2VjZThkOThkZTBjODM5ZjU2NDVkYmFhYTkyNWNiYg',
                                lat: 1.29698865,
                                lng: 103.9109835,
                                content: 'School of Bedford<br/>SoB268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2E1NjliYWNiZWYwZWI0M2U3OTgzYTAxZDY3MjQ2NGRmNzM2ZjMxMjZlYTBiZjIwZTQ2ZjEwMjMwYjhjZjIzNA',
                                lat: 1.39689302,
                                lng: 103.669375,
                                content: 'School of Metaphysics<br/>SoM378<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTY3MTBjMTI4MDhlM2ZmOGZiMWZjZDdiZjdiNjY1ODlmNjQyNWQ0OGY5MmFhNDQzNTFjMmYxOTI1NTYyMGMxNQ',
                                lat: 1.30953296,
                                lng: 103.6559147,
                                content: 'Scoil an Droichid<br/>SaD363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmMwMjlmYTQyN2RjZjU2YTc1ODE5YWI2Mzg2YTY0ZTA5MWRlYjhjZTk1ZDAwMGZkNTQ4NmU4OWRlYWNiNmUzMw',
                                lat: 1.32802663,
                                lng: 103.9508197,
                                content: 'Somerset School<br/>SS51079<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODZkZDYxYjg3NGUxMjMzM2IzNzliOTVkMWQ1YTBmNDYzM2ZiNzk0YjkxZDcxOWNkNmU2ZTE0Yzc5NzFlOGNiMg',
                                lat: 1.35570733,
                                lng: 103.8457221,
                                content: 'Southern Pacific Junior College<br/>SPJ603<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2I3MzI0MGM4NzcxMjg0YmMzZWVmOTZjZDhhZmNhOTJhODEzZDQxYTI4N2E2MWJkOTM3NTNjZGQ0MjJiYTc5NA',
                                lat: 1.38181163,
                                lng: 103.854559,
                                content: 'State Junior College<br/>SJC50474<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzQwNjQ4YmViOWQyNzYwN2Y2ODE5MGIxZGZjMGE5NjQ2NzQzY2E5Njk0ZGU2OGRhNjFiMGNmNWI2MTQzZWYzNQ',
                                lat: 1.35174466,
                                lng: 103.9837834,
                                content: 'Strategic Business School<br/>SBS603<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGQ3N2Q5NjQ4NDExZTdiOTc2OTYxZTQyOWMwNjg3MmY4MWRjMmRiNDEwMzYwNGE1N2M4ZjJlYmE5ODY1MDNkOQ',
                                lat: 1.24836597,
                                lng: 103.9254701,
                                content: 'Summit Theological Seminary in Peru<br/>STS33261<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODBiY2UxYTBmYWQ4ZDZlNzAyZGQxYjNmNWFlY2UwZjZlNGI3N2MzMmE0N2NjYmE3YjA4NjIwYjcyNDFjMDY3NA',
                                lat: 1.32094608,
                                lng: 103.6862888,
                                content: 'Sunday Adokpela Junior College<br/>SAJ268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2JkNGZkMmU1Njc4ZTNiZWJkYzkzMmFmZDc3MDBmOGU5ZjVjY2VjNzlkZjhiNzliNjZiNmEzMTE0YWUzMDZiNA',
                                lat: 1.2349185,
                                lng: 103.9227101,
                                content: 'Tabernacle Baptist College in Greenville<br/>TBC60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDdlNjBiM2QzNDc3YWFlYjIzYTJiNmQ0YzE2NTU2MWM2YTYxOTE1N2Y0OGFjMmY0M2M5MDUyODlkYjM5NTA0ZA',
                                lat: 1.37672087,
                                lng: 103.9587853,
                                content: 'Taiken Wilmington Junior College<br/>TWJ65923<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmYxZjZlY2MzMzhlMDM0MTUxZmJjZGIzODJlYTYzOWI4YzE3YjIzYmI0NDRlMjhmNjdjYWUwNjZmM2Q3NTI4Mw',
                                lat: 1.4627509,
                                lng: 103.887484,
                                content: 'Texas Theological Junior College<br/>TTJ58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjZhYWZjZGM2MTZkYWUyNzNlYzY2YjdkM2M1YTViYzk2MmU2Y2VmZjE2ZDIwMjE2M2M1ZmRhZGFiNDBlOTBmMg',
                                lat: 1.34348309,
                                lng: 103.8247021,
                                content: 'The High School of America<br/>THS42248<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODQ2MDRiZjQ0ZDg1MjZhNzU1ZWQ4ZTg0NTFjYzJjOTM4MTUxMzY4MDc5OGQ4YzVkZDNlMmIzOTNjMGM4OTZkNQ',
                                lat: 1.23746754,
                                lng: 103.7125647,
                                content: 'The International Junior College<br/>TIJ54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODExYWM2Mzg1M2RmMTAwZGE5NDlhZmQ1YjEyYzNmODhiZThhYzU1NTI0YWQwNjg4ZDgzNjEyZjc1ZTkwNThlZA',
                                lat: 1.23062621,
                                lng: 103.7828524,
                                content: 'The International Junior College<br/>TIJ36800<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGE1MGRjN2I0YTJlMmRlNTVkZjI1YjE2Nzk1MTlkNmQyNGU3MjcxNTc2NTI1MmM1MWMyYTc1NzM5MDNiYTMzYw',
                                lat: 1.20650742,
                                lng: 103.7144231,
                                content: 'Thompson School<br/>TS904<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MWFhMTNmZTc2Mjg4YmE0MzQ1NDVjYTNiN2RhZDViOGQ3NmQ2Yzk1N2FlNzFhY2Y5NjU4M2JlMWYzYmRhYTgzZg',
                                lat: 1.52336776,
                                lng: 103.7939066,
                                content: 'Thunderwood College<br/>TC36000<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTEwMDFkMDlhMGY2MWFlOGRlNTE1NzM5ZWRjOTI1Yzk4N2Y2YWYxYWRjYjk5MDNiMzk1NTk5YmM2YjI0NDQzZA',
                                lat: 1.23012181,
                                lng: 103.7456028,
                                content: 'Tiu International Junior College<br/>TIJ43353<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YWFmODkyMGNhNWRlMjVjZjYxOTY1NWUwM2VmZDIyZmVkNjlmMjhhZmIzYjczN2E3MDUxZjI0ZTZkNmVmNGJjNQ',
                                lat: 1.34014633,
                                lng: 103.9283426,
                                content: 'Tri-Valley School<br/>TS57687<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2YwZTYxMWIxMmVlOWExNjhkNTk1YjNiYmM3YzE0ZmJiY2FkMGZkNDM0YzFmNTZlNjQwNGZhYTY0NmUwOGNkNw',
                                lat: 1.32327563,
                                lng: 103.877227,
                                content: 'Universal School<br/>US68135<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDNlMzVkZmM1YjQ3YTU3OWNiMGZmNDViNzllZmI4OTcxMGM3YmNhMjVhNTI4OTFmMTI3YzY1Yzg4Zjk4ZDAzNg',
                                lat: 1.33538371,
                                lng: 103.8315379,
                                content: 'Universitas Sancti Cyrilli<br/>USC17089<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTE0ZTFmMjRlMjg0NDJmMTFlMjhkNmZhYjhmYjY0OWZmNWFlNDkyZTQ1YjIzZDcwY2RhOGY4NThiMzQ5NTA2ZA',
                                lat: 1.25625846,
                                lng: 103.7388559,
                                content: 'Vancouver Junior College Worldwide<br/>VJC904<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDE1YzUxMTdlZTJjMWY5ZjQ3YTVjZTg5ZTg3MDRlNGVmYTMxMDczNjIxYWQ0OGQxYmExOTE0NjJhMmVlNDg0MQ',
                                lat: 1.50914064,
                                lng: 103.8618654,
                                content: 'Warnborough College Ireland<br/>WCI10157<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDcxOTViNzc0NTg2ZDNhYmM4ZTA1ZDBjMGI4M2E4OWE2ZDg2MmIwZjhjOTYwNDZlMDYzOGZhODFjNzY1ZDcyOA',
                                lat: 1.38714459,
                                lng: 103.8876195,
                                content: 'Warren National Junior College<br/>WNJ17478<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTMxNGI3NmNhZjI4NTA5MjJhMjcwOTM0MDA2Mzc0YWMwZmFmZTBjZjZiZTQxODIwNTY4NGQ5OTljNmM4MGQ5MQ',
                                lat: 1.47322698,
                                lng: 103.7672672,
                                content: 'Washington School<br/>WS41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGE0YWY4N2Q1OGI5ZWZjMjliOTE3MzU5ZWNmZTZkM2ZkZDYwZmRkYWEzYzUwNmIwMGU2YWUyOGE3ZDI2MDdkZg',
                                lat: 1.2915529,
                                lng: 103.7620525,
                                content: 'West Coast Baptist College<br/>WCB10889<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDUzY2FiOWFjMzNmNGJjMmFlMjg4NGVmMThjYjZhOGViZjFiYTc1NDE4ZjM5MDk4ZWI1MjJmN2ExOWJmNmMwMw',
                                lat: 1.2940237,
                                lng: 103.9840964,
                                content: 'West Coast Junior College  Panama<br/>WCJ54973<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTUwNDBhNDg5M2JlMDczMjQ2ODE1ZWNiOTE2N2FhNmE4OGY5MGFlZWFkZDEwNGRlMDgyZmIyZTA5MzFmMGE4Mg',
                                lat: 1.49180499,
                                lng: 103.8709629,
                                content: 'Western States Junior College<br/>WSJ36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDQxZWY2OGQ3M2NjMmIwYTUyNDAwMWVkNWVmNWQ1NzU0ZTI1MTk2NWIzMjlmYjczZWMxYTZkODhlZjAwNmI1Zg',
                                lat: 1.4436839,
                                lng: 103.7671412,
                                content: 'Westfield School<br/>WS10819<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWM3MmEzYmJmOGNhMWJhNGNmNDE0OGMyOTY5ZDViNzdmYTJlM2I3MzFjOTZhMjU5ODY2MGY0Zjc4MzdiMWRmNg',
                                lat: 1.34138925,
                                lng: 103.9958776,
                                content: 'Weston Reserve Junior College<br/>WRJ51346<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmNhMTIwNGI5Y2Q0ZWU5MzY4ZjQyM2ZmYWRlMWU1MDUxYTE2MDRmMzY1NmY5ZjRmMmQxM2JkNjhhM2FlODNkYg',
                                lat: 1.25741462,
                                lng: 103.7209173,
                                content: 'Woodfield School<br/>WS17478<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDc1N2Y5NTNjNGZmYzlkN2E1NDdmMmEwZGY1NDQxMWRjMWFmZWM5ZDFmNTM1NzU1MmMwNDVjYTQ3ZGMzYzQ5Zg',
                                lat: 1.27271071,
                                lng: 103.8521573,
                                content: 'Wublakil Secondary School<br/>WSS65862<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2YwMzg3OTJiNTcxNmUyNTQ3NzkzMGQ2NjY0MWE2MjhhNWZiN2MxNDc5OGVhMjE1ZjllNDIyOTgwYmZhNmIxNg',
                                lat: 1.17600973,
                                lng: 103.7965586,
                                content: 'Yabiru Secondary School<br/>YSS47219<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ODFlZTllNzM5ZDgyYzhjN2I3M2FiODM2YzhmNjgyYTViZWU1MzIyNjNkOGFjMjE5M2FhM2M3MTBlMGNkODY1ZQ',
                                lat: 1.36273781,
                                lng: 103.9368935,
                                content: 'Yaggifo Secondary School<br/>YSS58668<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjVkNjgzNWUwNzljMTRmY2NhNDE4ZjY4ZTYwZDk0NjdlZmJjY2Q3ZWI2OThiY2VmZDE5YjUyNTZlMDBmNDQxOA',
                                lat: 1.31109761,
                                lng: 103.6684784,
                                content: 'Yanbi Secondary School<br/>YSS66552<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWQ1NzI0NjQ1NzJkYjNjMTNhNTRkMGNlOWM3NWRjNzYyMTMzZjQ1MDM5YjYwNTY1NDA5MDU4ZGFlMWMwZGQzMA',
                                lat: 1.41699505,
                                lng: 103.7206379,
                                content: 'Yeran Secondary School<br/>YSS49852<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjEwOTQwOGUyMjE1YWMyZWFkMjBkNTY5ZjkzMmVmNTY4OTAyNWYyOTdlYjg5OTZjNWJjNTRkMmRiZTY5ZTRkYg',
                                lat: 1.39420704,
                                lng: 103.9799552,
                                content: 'Youngsfield School<br/>YS143<br/><a href="http://www.google.com" target="_new">link</a>'
                            }
                        ],
                        marker: {
                            icon: 'university',
                            markerColor: 'orange',
                            prefix: 'fa',
                            iconColor: 'white',
                            title: 'Group 3',
                            id: "group_3"
                        }
                    },
                    group_4: {
                        data: [{
                                id: 'eyJpZCI6IjgiLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoidXQ4N3RkMzVjNzdicDhrNzZ2dmdvZXZodXUifQ.ODA5ZjdkNDMxZjZjYjRlZmJmOTk5Nzg5NjllYmU1YmM4MGYyN2NjZjI4NzkzNmViZTc0MjEwY2YyNTZhY2ExNQ',
                                lat: 1.26459352,
                                lng: 103.7268841,
                                content: 'Anglo-High School<br/>AS50563<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODczNzk4ZTY4NzJmZTdlZDlkZmIxOTIxMWViZjA1MDZiYjRlZTg2NjQ2ZDBjMDAzMjQxNzA3OTY4NmYyZTU3Yw',
                                lat: 1.2841666,
                                lng: 103.6871576,
                                content: 'Ashwood School<br/>AS76439<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NjRlMDM0OWU3NmZjNjg0NTE2Mjc5MmM4MDllY2IxMjFlOTY4ZjFhNDIxYWU4YzQ2ZGQ0ODQ0YjY0NjlmNGRjNQ',
                                lat: 1.34419622,
                                lng: 103.6442774,
                                content: 'Atlantic International University<br/>AIU67718<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NTE2N2RiODVmNDhhODhmODk2NGJiYTNlNjJiMmU1YzRkOThhZGQ4MjMwY2I3MTY0ZGFmYzg2NTQ1MWJlOWViZA',
                                lat: 1.48468665,
                                lng: 103.8151784,
                                content: 'Badaganvi Sarkar World Open University Education Society<br/>BSW42571<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzRjNzE2NWYyMDE1YjA2ODk2ZTRkYTZlNmM0MzQ5NTIwMGRiNjljNDAxMDNiYjBlNzFmZWMxOGMwYjAwZGQ0Yw',
                                lat: 1.24358952,
                                lng: 103.852379,
                                content: 'Barrington School<br/>BS60512<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NmIzMTQ4M2RlMDdlMGQ0YzNiZmM2NTVmMjBhMzJlMDRiNzJlNzE2ZWM4MmRjNjFiNTViNzk3ZWQyOTRkODc3Zg',
                                lat: 1.25144312,
                                lng: 103.8618843,
                                content: 'Berne School<br/>BS49884<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzkwN2EyMTY0NTEzMmUxYjZiYmNhYTdkOGU4NjJkY2JiMTYxY2Q4MmI3M2EzNWExOWI0ZDA3MDg4NDhiMGUyYg',
                                lat: 1.41919456,
                                lng: 103.7395981,
                                content: 'Bircham International University<br/>BIU50804<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.Mjc1MWFiNzU2YjhkY2MzNDNhNTU4N2RlMmQwOWZmOWFmYzU5NTFkMDk0NWVlY2M3NDlmZjg2ZmE5M2M3ZTM1Yw',
                                lat: 1.21655856,
                                lng: 103.7884712,
                                content: 'Brainwells School<br/>BS66689<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MDgzNDIxNDc3ODIyY2NjNzFiZTg1YjYzYzBkMjI3ZTI0ODEzM2M2Yjg0NzNkNWM2NWUzNjYyYmFiMTM2NTcyMQ',
                                lat: 1.23217152,
                                lng: 103.7472115,
                                content: 'Brixton School<br/>BS63187<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ODAwNzdiYTJjNWU1OWUwNGMwMjFiMjlkMzJiNDA0NzMyZjQ2NzY0ZTllNTVjOTkxMzcxZGExN2E2NjJhODA2ZQ',
                                lat: 1.30026208,
                                lng: 103.8983255,
                                content: 'Bronte International University<br/>BIU43598<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZmJjZjUwZTNlMzgyZmI2OTU0NmVjMWViMWY1YThhODI1MjIzZjUxNzQ5YmMwNTQ0Y2VmOTEwYjFmZGVlODdlMQ',
                                lat: 1.2293472,
                                lng: 103.9308695,
                                content: 'Burnett International University<br/>BIU21695<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzliYzFjZDI2YmI2NWQ3ZjkxMjA2NmY4ZjM0YjAzZGM4MmEzMDkxNTgzMWE0MmY0MmFmMDdhMTA3OWUzMmExMw',
                                lat: 1.32413061,
                                lng: 103.733839,
                                content: 'Calamus International University<br/>CIU178<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YzFlYTQzYTJkOWVlZWM0ODc0NzhkZTdiMjYyYTZkY2YxYzA4NTUzYjc0YTYyYzRiZjA5ZWY2ZDc3OTFiMjFkNA',
                                lat: 1.27692427,
                                lng: 103.9537983,
                                content: 'California Pacific School of Theology<br/>CPS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NjcyNGFmNWEzYmY5NjI5MTcyOTc5MDc4ZmVmNmM0ODEyYWEwYzBhOWZkN2NjOGQxYjQxZDk0NTcxNGFkMDMyMw',
                                lat: 1.22336484,
                                lng: 103.7350529,
                                content: 'California University of Business and Technology<br/>CUo68084<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YTJlNTg3MjU5MTgxYWE2ZTIwZmVjMDg5N2E0NDBmM2Q4MjhkNjdjYWQ2NTk2ZTY5OTY1YTFiOTkyNzY3YWVlYQ',
                                lat: 1.43318507,
                                lng: 103.8283258,
                                content: 'Calvary University in Virginia<br/>CUi62802<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.MjA3NzMyYmE5NmVmZjk5NTA3NGRmYmI5NDQ1NjY0MGI5YjRiYWYzNTc3YjY4NWRhYmQyNDkxYzJmY2M4YmQwNQ',
                                lat: 1.18815832,
                                lng: 103.8055983,
                                content: 'Cambridge International University<br/>CIU43063<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjU4IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OGViMmFkNTc0ZGE5YTMwZTI0ZTJhYzZlNWI1MzU0NWE3NjlkNzRmODA0YmM5ZTMyOTE5YzVlNmYzNGUyYTI5YQ',
                                lat: 1.22386753,
                                lng: 103.8123255,
                                content: 'Capitol School<br/>CS68001<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjYwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZDE4OTViNjhkZjRiNzVjYjRkZjU5OTQ5NjBjZTdkNTJmYTlhODYxNzljMTQ1OWZkMGM2MGMwMGU4NjRmMGQ4OQ',
                                lat: 1.44044308,
                                lng: 103.8513727,
                                content: 'Carolina International University<br/>CIU59050<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjYxIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZmFmMzhjNDMxZmE3MDY4Njk0MGYzYTM0NjVhZmUyYjU2ODk0MTg4Nzc0OGVkYmU3N2I3MzVlZTk5YTBhM2Q3OQ',
                                lat: 1.50307434,
                                lng: 103.8122359,
                                content: 'Carolina University of Theology<br/>CUo33261<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjY2IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.OWEwYTI3MDM0NWQ5MDg5YjI0NjM4MTNmYjM3NzY3OTg2YTRlNjBmOGE4NTEwMjhmN2NkYTgxNzhhNmI3NzE4MQ',
                                lat: 1.30858644,
                                lng: 103.8051689,
                                content: 'Chase School<br/>CS268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjcyIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NzViNTMyODVmZTY5OTllOTgzN2EzZTA3OTcxNWQ1NGZhMDQ4ZWVlMmVkY2E1YTVjNzEwMjBlMDYwZWIyMDQ0ZQ',
                                lat: 1.23581568,
                                lng: 103.9564021,
                                content: 'Clayton Theological Institute<br/>CTI68828<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjgzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YWVjM2MwMGI4ZWU4NzFlMDFiNDU2NTgzOGViNzJlYzlkY2QxMmM0Y2YyNzlkMWFmMTZhOThhZjgzNWE5ZTIxYg',
                                lat: 1.29915441,
                                lng: 103.7518022,
                                content: 'Columbia State High School<br/>CSH37808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6Ijg3IiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.ZGYxZGYxMmUxYjEwN2I2NDAwY2U2ZTQyMThhYmIyNWE1OTI0ZThlZjZmZDFjNmRiMjFiMzM1MDA2OTFkYzdlNw',
                                lat: 1.46481768,
                                lng: 103.703632,
                                content: 'Concordia College and University Dominican Republic<br/>CCa42291<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjkwIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.NTU0MzIxMDg0NGUzMTE1MDFjYThkOTUxZmZiNDU1OTUxMzVmYWMyNjFmZGU0NjBlMDQzNmZjN2U2YmI1ZjYzMw',
                                lat: 1.37745666,
                                lng: 103.7257031,
                                content: 'Cranmer Theological House<br/>CTH363<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjkzIiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InV0ODd0ZDM1Yzc3YnA4azc2dnZnb2V2aHV1In0.YjQ1YjIwNWIxMjM1YzI1MTdkMjI0MDY1NGEzZTNlNTI5NDBlZjBkYTk4MTk4OWRjYjcyY2Y0NjAwYWNjZDdlYg',
                                lat: 1.40923778,
                                lng: 103.9328821,
                                content: 'Delta International University of New Orleans<br/>DIU52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWFiZDQ3MDFhMzEyMGQ3NmRiNmQwYjBhOWJjNDYxYTU4OTI3YWZiNzA2YmRlMDA5NzEwMGYxZjRjMDhjM2E5Zg',
                                lat: 1.36883326,
                                lng: 103.6613347,
                                content: 'Earlscroft High School UK<br/>EHS61995<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEwNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTg5ZWNkMGY1YmU0OTk4NWMxNjcyYzgyMWRkMjk4M2YxNzg3YzBmNTcxZDc3NTEzYTBkNzYzN2IzY2Q2M2U2Mg',
                                lat: 1.32377542,
                                lng: 103.8689215,
                                content: 'Ecole Suprieure Robert de Sorbon<br/>ESR67345<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDIxN2JlZGNjOTFiOWQ4NjQxY2Q4OGI4NjQ4MmVjYjJmOWMyYmFkYWFjMWEyNGI5NzIwZGU3MzE2NGJhNzBjZg',
                                lat: 1.31538445,
                                lng: 103.6740466,
                                content: 'European Business School Cambridge<br/>EBS57955<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2FkNmZiYmU1OTI0ZmQwM2RhYzJmOWU0MTE3ZjA2ZTM1ZGE3ZjdhM2U5ZWEzMDM5MzgzMmQ3YjEyMDVhZTBlMg',
                                lat: 1.23593574,
                                lng: 103.7967074,
                                content: 'European Carolus Magnus University<br/>ECM52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTE5ZjE0ZjMzYjU0MWI1MjI4ZWI1MjA1ZmM1MzJmODBjYTBkZWQzZmY4MzY0NDE0MzU0YzE5MDk2MmNlYzViNw',
                                lat: 1.40941331,
                                lng: 103.8804904,
                                content: 'European Continental University<br/>ECU378<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjY4MTlmNzZjYTBhOTE1N2VkYzA3MGRjOWIxYTI0MzM2OTZiNWFmZmE4ZjYxYjZmYmJjNTY1OWFjZmYzNDlmOA',
                                lat: 1.31486299,
                                lng: 103.6489659,
                                content: 'European Management University International<br/>EMU58194<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEyOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTk1NjI3YjczN2NmNDVmOWZhOWViNWY3YjZkMTc5YWZiNmVlMDJkNzY3NmFjM2E1Y2MxNGQwOTAzY2IyMmM5NQ',
                                lat: 1.418216,
                                lng: 103.818683,
                                content: 'European Union High School<br/>EUH61328<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjEzOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2NkNTM5NzQ0ZjQ2OGVmMjgzNjA3ZmE0ZTQ3MGE4OGQ4MWFmYzA2Yjg0ODcwYTQzNjkxOWI0MjFiYzM0MDRhNA',
                                lat: 1.36373942,
                                lng: 103.6604932,
                                content: 'Frederick Taylor International University<br/>FTI11673<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzFkNTRhODMyMTIwOTU3YjY3NzQ1NDY3OTdkYWIwYmM4MWE2ZWU2NjVmMzgyMDQ4MmJmZGZjYTAwYzkxZjcwMw',
                                lat: 1.38379482,
                                lng: 103.8267918,
                                content: 'Gandhi Hindi Vidyapith<br/>GHV115<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjkwNWZiNGUxZjg0ODRjNzNjZDQ2YTAzZWQ4NmUyM2UwMjdhY2QyY2JhZjBhNTc2ZTMyYzdhYjgzNDMwOGZmNQ',
                                lat: 1.28350524,
                                lng: 103.9464759,
                                content: 'George Wythe School<br/>GWS55910<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE0NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTlhMTJhMDQ2ZDQzMjMyZTM1OTM0Njc4OGM4M2IxNTAyZGVlYmQ2MDIxMTdlMGZkZjEyNTIyMjg5MjViN2FjNg',
                                lat: 1.25016046,
                                lng: 103.7808333,
                                content: 'Georgian International University<br/>GIU268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzhmZDk3MGM0YjEzYjY2Y2ZlMDkyNGQ1MDFmOTY2NGUzODJlZDU1Yzg1OWQ0YjFiZGFjZWNhZmY5Y2Q4YmM1Yw',
                                lat: 1.20685212,
                                lng: 103.7977627,
                                content: 'Glenford University<br/>GU41415<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDdiNTZkYTAwYTA5ZGNhOThjNzk3NWE0N2QwOTBlYTFkZGQyODY4MWJmMDUyMTM3ZTY2Mzk5NzNiNDY2NTRlZQ',
                                lat: 1.39257715,
                                lng: 103.6822086,
                                content: 'Global International University<br/>GIU43419<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE1OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTY0NWZkNDIwYTIzYWZiNTZkYTkzNjc5M2E2NjE2ZmFmNmVmZWRjNThhYWQ3ZmViZWJkODlhYzM1NGRjOGJhNw',
                                lat: 1.44290759,
                                lng: 103.79481,
                                content: 'Global University School of Medicine<br/>GUS42766<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE2MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YTNiNTljNmYwNzQyMWFlNzU5NmE4ZjM1M2MwZDUwYzcyNjQ5NGQ2M2VkYTliZWY2YmYzNTZkM2ExMzE3MDBiNA',
                                lat: 1.22675122,
                                lng: 103.8160921,
                                content: 'Golden State School of Theology<br/>GSS54<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE3NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWEyYjE3OGE1YmUzNDcxMDViOTJiYTFhZjBmMjZlNDFhYzM1MmIxZTVjM2I0MGZjMzQxODQ0NjdhYzNkNDcyOA',
                                lat: 1.34248301,
                                lng: 103.8905798,
                                content: 'Headway School<br/>HS49288<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTQwNzc4ZWJmZmM1YjAyOWMzYTkzMTc4NzM3NGVmNjJhZjk5MTJmMDkyY2IyNDk5NzZjYzc2YjA0NGZkODhmMg',
                                lat: 1.48963985,
                                lng: 103.7741068,
                                content: 'Hoiebia High School<br/>HHS60760<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjU3OWU5MDQ0N2Q3OWViMmNmNjdmNjQ2ODc2NmMyMDNhNDMyM2Y1ZjY0Yjc4OWM1NTE3Njg0NjdjMThlMTY0ZQ',
                                lat: 1.33640199,
                                lng: 103.8781873,
                                content: 'Houdegbe North University<br/>HNU55316<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE4OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjdkMTZmNjEwMmFlM2IyNDM0NGI5MjVlY2RmNjcxMjllZmY1OWY0ZDZhMzFkNjAwMDQwYTVkZjA1Yjg2ZjlhOQ',
                                lat: 1.47905139,
                                lng: 103.9365884,
                                content: 'Independent International University<br/>IIU46122<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGEyYWY4ZTcxNmI3MzljNjc5MDFjMmZhMTFjMDNjNzUzYTA1M2RkYjE1NTcxNGRmYzE0OWE0MWM2NmY0NTNmNA',
                                lat: 1.40896751,
                                lng: 103.6799077,
                                content: 'Institute for Creation Research Graduate School<br/>IfC57237<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjI2MGVhNTdiOWI0ZDBlNzg0OTAxMmFlZTZiMzgyZDRmZTkwN2Y4MmMyNTQzNWUyOTg0NDk5NWEwNjgxN2RmMA',
                                lat: 1.38333096,
                                lng: 103.9628489,
                                content: 'Intercultural Open University Foundation<br/>IOU59351<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjQ2MjRjNzc3YTM2YzJiZGFhNTI2YTQyNzYyYTYyN2I1Nzc1ODViNmQyMjJkMGUzMjVjOThlNWRlMWVlOTBlMg',
                                lat: 1.27983852,
                                lng: 103.7509155,
                                content: 'International Management Centres Association<br/>IMC58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjE5OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2ExMzFlMzk3NTE4NTlmZGI5M2QwZjM2MzA3MTlhMzJhZGY1YjVkZmEwYTY3MGJiMGY2OTE3ZDE0MjIyOTk0Yw',
                                lat: 1.45802573,
                                lng: 103.9539831,
                                content: 'International Theological University California<br/>ITU66993<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDgyYmEyNDY5MGJkNjU1MzNiNWY2N2Q3MWJiNDU0YTQ5Yjc2Yjc5Mzc1NDU4YmFkYWYyNTAyNmRmMGU3OTg3MQ',
                                lat: 1.47144755,
                                lng: 103.7651524,
                                content: 'International University<br/>IU57955<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2NjNzIyYjBiMWY2ODdlYTcwZjBiMTEwYWIwZTI2YjdlZmM1MTNiZDkyODFiYjIzZGNkZjY0YWFmOTI1MWQ3Yw',
                                lat: 1.49650147,
                                lng: 103.9094001,
                                content: 'International University<br/>IU36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDhjYzhiODM5OGQwODgxMWEzMzZlMjdkNmFiOGFiZDRiMDJiZmZmOWU3OGM0NjZmMDI5N2E2MjQwNDkyODg2ZQ',
                                lat: 1.40251163,
                                lng: 103.9903067,
                                content: 'International University of Management and Technology<br/>IUo16590<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjkyMDE1N2RmNmI2NWZhZGMxOTIzYTFiNzU5MTE3Mjg5NTM0YTEwMjJmMmFkMjk2MmJiYmJkNzBjYTY5NmNkOA',
                                lat: 1.30012392,
                                lng: 103.7888093,
                                content: 'International University of Ministry and Education<br/>IUo11084<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIwOSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjA3ZWZiNDNlOGRjYTE0ODZhOGQ4YTlkOTkzMDZmMTBjZmVmNDU0OTcyYTg1ZmI4NGY4MzQzODRlNWU3ZDcxZQ',
                                lat: 1.27732717,
                                lng: 103.9160552,
                                content: 'James Monroe High School<br/>JMH55301<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIxMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzdhNGYwY2U3NTJjZjkzMTE1YWM5ZmUxOGQyZjQwZGQ3NTk3MDA5NDJhZGRhMGE0ZjExOGFjMzY2NWYzMWY0Mg',
                                lat: 1.19644142,
                                lng: 103.7927713,
                                content: 'Johnson Daves High School<br/>JDH517<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjIzOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2ZiOWMxYTI1YTUxZGRhNzFkODI4MTI5MTdiMmQzMGJmYjQyNTJlNjVlNmRlNmMwMmQ5ZDg4MWM4NDI3YTc0ZQ',
                                lat: 1.45889338,
                                lng: 103.8100966,
                                content: 'Knightsbridge High School<br/>KHS68788<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI0OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGRjZDk2MTRlZDU5MmIyY2QwMTkzYTUwYzU4MDkyYTcyNDZmZTI1NGQwYzM0NTMxNDY1YzI3YzQyOTkyNmJiZg',
                                lat: 1.27020554,
                                lng: 103.9081088,
                                content: 'Leadway School<br/>LS37808<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI2NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzIxNTIxMDdmNjNkMjQ2MTBiNjMxNjM3YjVjOGE1ZDU2YzNkMjJhNzQ5MGRkYWQxMDFmYzczNGY4MTBhNzgzNg',
                                lat: 1.45939093,
                                lng: 103.8685924,
                                content: 'London External Studies<br/>LES58232<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Zjc1ZDkyZDAyZmRmMzdhNDU1YWFiMDhkZmM3M2EzZjk4ODM3NTQxZDE3MGE5N2YyMTdhZDgzMmZjZWQwZGM4MQ',
                                lat: 1.31038173,
                                lng: 103.8959757,
                                content: 'Madison School<br/>MS56062<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGUyNTM1MDRiMjEwOTFlODYxY2M1OTYwZDY5Mjk3OWNhZWEyZTkyOGFiYjEyNjY0NGRkZmViMDA4ZjZhNDVjZQ',
                                lat: 1.38476415,
                                lng: 103.9081834,
                                content: 'Maithili University/Vishwavidyalaya<br/>MU52682<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI3OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjExMjQwNTI5ZDc0NzJhM2M1MzM5OTAyZDVhN2ZiMjdiMzhiOGMyMWE4YzY1NTM2ZGY4NzI0ZDQxYWYyNWMyNw',
                                lat: 1.27555843,
                                lng: 103.8654951,
                                content: 'Maprik High School<br/>MHS49738<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI4NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGIwZjlkNmE0MGY3OGU0Njg5ZmI3NTBjOTNmMjI5NzMwNjNhMzQxNWZiMTljYmQ3OTRkOTdjZTNmZWU3MmRiMA',
                                lat: 1.19185774,
                                lng: 103.783842,
                                content: 'Metro School<br/>MS54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NTNmNjMwNzk1MGZhYzU4MmFiODY1Y2NiNTYxMWQwMWE2NjM0Zjk3NzkyODQ5MDljNDQ3NTEyNjYxYjEyNmQwNg',
                                lat: 1.28611854,
                                lng: 103.9790018,
                                content: 'Miranda International University<br/>MIU52686<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjI5NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjNkODY2MzFmYjlhMTI3MjYxNDY1OGIzZjVhZDIwY2I1MzY1MzVkODk1NDdjYTRmZGIxODM5NTQ4YWMxMDU4ZQ',
                                lat: 1.42309036,
                                lng: 103.7532449,
                                content: 'Mississippi International University<br/>MIU409<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2JhOTBmZWI1YTc0OGU5ZDk2MGE5OTQ0MWI1ZDEzM2E0MTQyMjE3NzIxZTAwMTg1ZjNjOGFkNDI5ZTkxMzRjNg',
                                lat: 1.30780922,
                                lng: 103.8285046,
                                content: 'New Age International University<br/>NAI63941<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMxNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDI2ZTNhOTVmOGQ0YWJiODczOTBkZjU1YzkxYTM0ZjIwYmRmMTM1N2Y0YmI4ZDYxY2FhZTcwNzVmMGNjYzRkNA',
                                lat: 1.51213805,
                                lng: 103.8454128,
                                content: 'New West Seminary Oregon City<br/>NWS41163<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2JlMWRhNDcwYWVjYTRlMGQ4YWQ2MTZhNjQ5MzliYWQ0ZmRhMzE1YjdlNzMxNTkxMWY5NjExODQ2ZDZjNDhhNg',
                                lat: 1.31296293,
                                lng: 103.8502977,
                                content: 'Non Traditional University of USA<br/>NTU59779<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGRjNTM0ZWZiOTZkYjM4ZjYwMWU4YjFlZTBhOGQxODFhMDMzZWFhODA2OGQwYTQzMjVlZmI0MDFkNWUxODQzMg',
                                lat: 1.44601379,
                                lng: 103.6992857,
                                content: 'North Central High School<br/>NCH59746<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyNyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2ZiNWEyZDlkNzdmZThjMTVmMjJlOGEzYjQ2N2M3ZWUyZjY0MWU0MGVjOTRlNzdhZTIwYTMyYjE0MGE1NTU2ZQ',
                                lat: 1.39490748,
                                lng: 103.8807989,
                                content: 'Northwestern International University<br/>NIU49288<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMyOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2VmZTcxMGFmNDQyZDAyZTI2YTI4YTg1MTdkODM1MWRjYzM3YjQ5ZDZjNjJmY2YzMjBiYjFlMDA1YzRiYjRjNA',
                                lat: 1.37474472,
                                lng: 103.8413381,
                                content: 'Norway University or University of Norway<br/>NUo35356<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGY5YzlkY2ZkZGYxZjcxYmQwYTI2Y2Y4OTJhZjU2ZjNkNjZmM2I3YzIyNTMzNzY0NmRkNjAzMWIyZDMxZmQ5NA',
                                lat: 1.50812276,
                                lng: 103.8449435,
                                content: 'Novus University or Novus University International<br/>NUo36987<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzMSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Y2EzNzgzOTQ3MTE1ZjZjNjczZDhmZGMzNjFhMTg1M2ExMWQ4Y2E0NzIxZTRiNmNlMTZkMTM0MzdkY2EyNGJiNA',
                                lat: 1.32883688,
                                lng: 103.9768014,
                                content: 'Novus University School of Law<br/>NUS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTQ0MDg3YmVjYTlkYzA4Nzg2NzRmOTJlNDQzNTg5OGVkMmI5MGY1Nzg1ZjQ4NGQzZTYyOTkwNmVjODVhNjQ1Zg',
                                lat: 1.23698544,
                                lng: 103.6930242,
                                content: 'Oaklands School<br/>OS54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZGFhMjc5Y2JiMWQzMmQwNGUwNDg5NmNlMDJmZWU4NGY5Njc2NTdhMTE1YzcxYTI3OTRlZmU0YTAxMjNmOTM5MA',
                                lat: 1.49286983,
                                lng: 103.7918472,
                                content: 'Pacific International University<br/>PIU37205<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjMzOCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTI0MjYyNDE0YTFkYTJiMjhjYzUwOTZkNWI5MTgxYWUxYzc0NDE2ZjYwNGI3ZTgzYTA0MzY4NzllM2ZmNjgyOA',
                                lat: 1.48163847,
                                lng: 103.7570492,
                                content: 'Pacific National University and Theological Institute<br/>PNU58228<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjgyNDhhMTY3MWNiNDIwMDdjOWE4MDM1YjE5Mjc0ODE5YmU1MzU1MDZkNjdkNDgwZTNmM2JmOWNjYzNhMGRjZA',
                                lat: 1.35750018,
                                lng: 103.8420391,
                                content: 'Pacwest International University<br/>PIU51706<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YzBkMGYwYjBlMzM5MWU1NzgyM2QwYjA2NzAxMmQzNDQ3NmUxNzU2ZWJmNTM5Zjc1NzdmNjFiZTQyMDE0Y2ZiMA',
                                lat: 1.30759395,
                                lng: 103.9102939,
                                content: 'Pebble Hills School<br/>PHS24438<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTcxZTQzZGRkYjM2NTE4OGI1Y2FhNzcyN2IyNTE0ZmM4NGYzNjdhYjU0MWM4YjZjYTI4ZDQ3ZmNjNjc3M2Q0NA',
                                lat: 1.42164149,
                                lng: 103.7506266,
                                content: 'Phoenix International University Europe<br/>PIU60681<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM0OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDdlZDNiN2NlODI0YThkZTA4NmVlNmM1N2NmZjNjZGMyN2E2MDJhNTBmYWU4MjA1YTg5ODRjYWExYzEyNzMxMA',
                                lat: 1.38164801,
                                lng: 103.6653624,
                                content: 'Politecnico degli Studi Internazionali<br/>PdS50844<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OWVlMTNhMmFlODJhNWM1NjI3YmNiOWE5MWE2ZWVjNmZmNDJhYTI0NGQ1ZmQ3OTc4ZmFmOTRlY2Y3NGQ1MWRiYQ',
                                lat: 1.24302417,
                                lng: 103.9007273,
                                content: 'Randford School<br/>RS55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM2NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjgyMDVjNDNhMzhjMzZiNjI4YmE0YWJlYzdlODBlMzJjZTIwOTZjMTE4NzYzN2FiMGIyZjdjY2EwNDZkNTg0MA',
                                lat: 1.3531221,
                                lng: 103.6731349,
                                content: 'Regent International University<br/>RIU52438<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjBmZWMxMWQzOTVkNWRkOWU2ZGYxNDE4OTcwZTM0MzFkMzczZDhhNWZhZmZmOTAxMDM2ZmU0ZGFkNTgyNDBhZQ',
                                lat: 1.24452942,
                                lng: 103.8111902,
                                content: 'Ridgewood School<br/>RS24794<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2I5YjY1YzA0NTFhZTJmYmIzY2JhNjlhYTg5OWRiMTQ1MWMzYzEwZTcxOGNjYTJlNTY4MTlkZGRmY2I3MGJjNA',
                                lat: 1.18218613,
                                lng: 103.8063386,
                                content: 'Rushmore School<br/>RS57237<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM3NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjkxMGI1NTFlYTcyNTdjYjNjNjBiMWVmNTBkNDNmNjU2NjE1NjQ1NTRmY2Q2MWVlOGNjYzgzNDNhYmM3YjJjNw',
                                lat: 1.41813552,
                                lng: 103.7594066,
                                content: 'Sacramento International University<br/>SIU54868<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OGI4NzI3Mzg1MTk3NWIwZTIxNmIwYWE2YzkwZTRkOTFiYjdmZGJmZWIzN2QxNDg5NGM0YTY0ODg3ZDFmOTZhZA',
                                lat: 1.49431077,
                                lng: 103.801871,
                                content: 'Scandinavian University<br/>SU35356<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDljMDQwMDdiNTBjODBjYTY3OTk0MzViMTY5Mzc5NDIyZmU2YTY2MGRjN2JkZDdlYjY3OGJiNjhkMGFjMjg1ZA',
                                lat: 1.40057067,
                                lng: 103.987949,
                                content: 'School Consulting<br/>SC58118<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM4NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzYwMTY5ODZlMGM0ZmM5YjYzOTliOTgyNGEyNTM0YmRmZDQzODg5MjUyMjkwM2ZiY2FhNWI2ZmRkZmMwN2RjOQ',
                                lat: 1.37015403,
                                lng: 103.8053729,
                                content: 'School of Industry<br/>SoI10742<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWU3ZGYyYjNkOTk1MzQwNjE0MjVlOTg2MDk2MTFmNTNiODI4MzdiZmIwMDJiYWI2ZjFmYTk0ODQyMDc5ZGYxYw',
                                lat: 1.3760167,
                                lng: 103.6667745,
                                content: 'School of Sedona<br/>SoS67850<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjM5NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MTlkZjI5ZmQ0ZjZlZDRmYmQ4YzczMTE5ZjRjZmQwNTY5ZTk1YzVhZjhjYTU2N2UwN2E3M2YyOTYxMGZmNTllNA',
                                lat: 1.43096292,
                                lng: 103.8133701,
                                content: 'Si Tanka School<br/>STS55513<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjQ1MmMwYjg4YzVhZjQ2NDVhNmFiN2ZjM2NlNWU3NDkwOWM1ZTJkZjE4NWFlODY0OGY0MGVjNmI2NjQyZmQxYg',
                                lat: 1.19948534,
                                lng: 103.7991434,
                                content: 'South California Polytechnic University<br/>SCP11523<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQwNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NmRiMTY4ZGUyMTc4OTcxMDU3OGFmN2IxZjMxNzRlN2QzNjQzZTUwNGFiZmJlYzM4YWU0NDFiNDIyMjI1MzljYw',
                                lat: 1.32067044,
                                lng: 103.7940056,
                                content: 'Stanton School<br/>SS68258<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQxMyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjNkODM2ODRhM2U0ZDAzZWVjMDI5MjBkMThmZTk0OTg4MTYxNjRiODFjYTc0ZWJmNTkyZDg3MmE1NDYxYWQ4OQ',
                                lat: 1.35284405,
                                lng: 103.7861826,
                                content: 'Summerset School<br/>SS68462<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQyNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDVlMzNkYjNmZTVkNjJhM2M1ZDNiNGRjNTM4YTI5YTdjZjViYTAwOTdjODgyMmVkOTdiZmMyY2MyOTFiYTVkMg',
                                lat: 1.50878387,
                                lng: 103.814451,
                                content: 'Templeton School<br/>TS51531<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQzMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzhlMTI0ZTQ3MzUwNmRmMjk0NjFmMGFmZmM5MWY0MjgwOGI2ZDlkMGRjMWRmNDU5MTVmNWUzZWE4YzQyYjQ0MQ',
                                lat: 1.43933758,
                                lng: 103.7752933,
                                content: 'Thomas Jefferson Education Foundation<br/>TJE63304<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjdlMDRhYWEwZDg4ZGNmZGU3ZmQ4YzdhNjM3OWY5MzUzYWQ2ZjBiNWM1ZjYyMjgwYzljMWQwNjk1ZDgyYzY2MA',
                                lat: 1.49129267,
                                lng: 103.8627484,
                                content: 'Trafalgar Distance Learning Institute<br/>TDL54569<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ0NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.OTBhYWNhMTRmY2ExOWRhNWY5ZWZhYTU4MmQ4MGNkYjBiYmIwZDU4YWFhNjEyMTJmNmFlYjY0YjQzZmU1NzBkYg',
                                lat: 1.32128529,
                                lng: 103.8908914,
                                content: 'Trident University of Technology<br/>TUo55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzhhN2VlYjMzYjIwNDIxMDJkMjMxNjE0MjYxZTQxOTA5MzUyYjVjMGYyNjllMjRmMjI5YjhlNzkyNDAxM2NiZg',
                                lat: 1.48965484,
                                lng: 103.9011742,
                                content: 'United Nations High School<br/>UNH64725<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDUwZDJmYjczZDZlMDk1ZmJlMzM4ODQxZjFhNzljZTQzN2M5NWEwODU1NTE0MjUzZjNkODhkMzljYzlkYjdhOQ',
                                lat: 1.39344009,
                                lng: 103.8833253,
                                content: 'United Nigeria University College<br/>UNU904<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZjlhZGRkZTM4Y2QwOGU2ZTBhOWIxNmE2YTY5OTJmM2ZiM2I2OTAwYjY2YzQ0Y2ZkYmZlMWEzMzZlODNjMTVkYg',
                                lat: 1.50206582,
                                lng: 103.8939047,
                                content: 'Universitas Internationalis Studiorum Superiorum<br/>UIS54111<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MGZkNTk1ZjI0MWRhYTdjOGY4OGI3MGIxMDg0NTk4ZDY5YjlhNDlkYTQ5ZTlkMjVmMjRlZDM4ZmQxMTlmODUyNg',
                                lat: 1.18818582,
                                lng: 103.8308062,
                                content: 'University for Humanities<br/>UfH71<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ1OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2E2N2E1MGM0YWUyNjRhZDIyZjUyMzQwODQzM2UwZTA2NzJiNGVkYWIxZmQ5OGRmNmU3YzViMjRjNGJlYTY4Yw',
                                lat: 1.33043625,
                                lng: 103.7443127,
                                content: 'University of Applied Sciences & Management<br/>UoA48433<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NWRiYTg4Y2I4YmM4MTA4NGRkMmY1NDA0NTA0MDMxNTNlOGZhNzFjY2ZiMzJmN2QxNzY1MzViMjJiNzA4ZGJmMA',
                                lat: 1.47662755,
                                lng: 103.9226642,
                                content: 'University of Ecoforum for Peace<br/>UoE52083<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTg2N2Y1N2E3ZmE5OTgzMWNhMWIwYmMyZDVmNWUwODg3M2VjOGEzMDQ0NDFmNzAyYzg2OGIxYWZhMjhiMWEwNg',
                                lat: 1.34288518,
                                lng: 103.6472442,
                                content: 'University of Hawaii Gulfport<br/>UoH33261<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NThiMWFmY2QzYjc3MWMyZTZmZTdmMDE4NGNiZGQzOGRiOTkyMWM1OGI3NjAxOTZjNGVhMTZjNjZhY2MwNzNiNg',
                                lat: 1.34542453,
                                lng: 103.6965932,
                                content: 'University of Human Services<br/>UoH49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDBmZThkMDRjODI1YjZiMDZjMzEwOTY2ZDMwYjVlMmM0OWFkNzZiMDlhZWI1ZTY5ZDgyNzlkZjEwNTVjNTdlMw',
                                lat: 1.18701135,
                                lng: 103.8814314,
                                content: 'University of Mayonic Science and Technology<br/>UoM49884<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MjFkMzAyZjM1YmMzZGNhZWYyZWJkYTEzMDNjYTA0ZDNlMzZkNThkMTAxMjI0NDczYjZlMGM3MGMwMzAwODg4MQ',
                                lat: 1.45406275,
                                lng: 103.6879395,
                                content: 'University of Metaphysical Sciences<br/>UoM61477<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NGM1NWUzMWI0ZDM4NTlhZDZkMmMwYTIzOTY0MjEwNWU5Yjk2YjU0NjBmNDU0MGU5ZTY0Nzc2M2Y1YWM1ZWNkZg',
                                lat: 1.21850742,
                                lng: 103.8670792,
                                content: 'University of Metaphysical Studies<br/>UoM52125<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2NiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MDk5M2U2YjAyZGU4ZTgzNzUwM2NiMjJkZTQ0M2VlY2RmMTdjMGU2ZTFiZDRlYzg5N2FmNDJjMjg3ZmNlMjFkNw',
                                lat: 1.2718142,
                                lng: 103.8099047,
                                content: 'University of Northern Washington<br/>UoN63323<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NzI5MmUyNzVkZmNmNDk2NjY5ZWE0MTljZDk3YTQ0NWM1OTNjZjEyM2Y2OWU5NGI5YzMyOTRjMmRiYWY2OWFhZg',
                                lat: 1.46109328,
                                lng: 103.7069276,
                                content: 'University of Singapore<br/>U49999<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ2OCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YmE1YTk3YjA5ZGU0NmMxOGYwODk0NGY5MjY3ZDcxM2RmZmNmOWFhYjk0MDJjYzhlOWU4MjIwMmY1MTk5NGE4YQ',
                                lat: 1.48413321,
                                lng: 103.708638,
                                content: 'University of Valley Union<br/>U338<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3MiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.YjFlYTI2NmNjZTAwOWZjY2EzMTYyZjU4ZmM1MjhhNDZhMmIyMGE5NGI1OTlmYmE5MGYwZTdhOGJjZjM1NTg5YQ',
                                lat: 1.22238355,
                                lng: 103.8785141,
                                content: 'Varanaseya Sanskrit Vishwavidyalaya<br/>VSV66096<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3MyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmY2ZGEyMDQ5OTAzODVmYjY2ODk5OTRjMTlhZTY4OWNlZWJkZThjODdmOTNmNDcxYjczZTk5OGUzZGEwZWZlNA',
                                lat: 1.17617631,
                                lng: 103.8439309,
                                content: 'Vision International University<br/>VIU41415<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDkxNTc2ZmVlZTBjMjZjMjhhNjA3N2U4YmU2NGMxNWU3NmZkZjY2M2M2NTA4ODYxNjg5NzIzOTc4MTY4ODkxNg',
                                lat: 1.29505199,
                                lng: 103.949059,
                                content: 'Volta University College in Ghana and Nigeria<br/>VUC55714<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3NSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.Nzg1OWExZGU5NDZiMTM3NDY0ODkwNjk0NGFjYTAwNmE1OWRhNmQwOWE4ZDU1NjEzM2M3NWYyMzYzYzdmYmM1NA',
                                lat: 1.26923912,
                                lng: 103.7938072,
                                content: 'Walesbridge University California<br/>WUC52815<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ3OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZmYwOThkYTQxODAxZTgxMTVmZDI0OTgzZTU0ZDZlNDY5NTlkNDllM2EwNjg3MzVjNTMwMDA0MzU1NzA3NGUxYw',
                                lat: 1.4013734,
                                lng: 103.9794238,
                                content: 'Washington College and University<br/>WCa66129<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2JlYmU4ZmYxM2EyZjc5MDBlOTI2ZGFkNDJkMGUwYmQ3NGZiZDY0NzY1ZTkwMjgxYjQ2MzdlNTQxOGFlOTM5Mg',
                                lat: 1.52187561,
                                lng: 103.8749068,
                                content: 'Washington Governors University<br/>WGU55154<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ4MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZWZjYmY5YjBhMmJhMWU0NjU3NmJkY2ZlMGFhZWY3MDJmZGU3YmQ0OTFkNGJiZGNkMGVjZjE1MTQxYzA0YWQxNg',
                                lat: 1.19139647,
                                lng: 103.873577,
                                content: 'Washington International University<br/>WIU268<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5MCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZTg1ZjliYzRlMGU2YmFkYjNhYTkwZGNhM2I0NWYwYWNmOTA5YWNlNjZiMTE3NTk4NzMyMjQwYTdkZmIzMmM0OA',
                                lat: 1.50243262,
                                lng: 103.9041936,
                                content: 'West Coast School<br/>WCS55264<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5MSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NjBiM2I3YTIzOWZlZGU1YWJmZGZjOWY0ZGNjMTZkM2E5ODg1YTRhZDcxZjJhNmZjODBhNzA2MjNmYTlkMjI1MQ',
                                lat: 1.49486796,
                                lng: 103.8687789,
                                content: 'Western Advanced Central University<br/>WAC49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5NCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NmNmZTEwODc1YTQ0MDI5MjYzMGQxZmRiMmRlYTZlMjIyMzFkNjVhNzhhYTg5ZDU5YzdhZTA5OTY0MTcyMWNkNQ',
                                lat: 1.31362555,
                                lng: 103.7620894,
                                content: 'Westmore College or Westmore University<br/>WCo44567<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5NyIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.N2YzNWNhYjA2NzVlYjlkYWNiZjY0NjdlZWEzYjRmM2Y1M2FlN2Y3NzVmM2M0YjJjYWIxNjQ4OTFjZWM5ODE2Nw',
                                lat: 1.42540161,
                                lng: 103.7840603,
                                content: 'William Carey International University<br/>WCI55045<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjQ5OSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.M2U0ZWM4NzJlZDlhZmZjY2UxM2IyODJkMzRlMmFjZDdhODkyYzRmZGE2M2NjNWJmN2Y0MmIyN2MyYjNmZmFmNg',
                                lat: 1.37210461,
                                lng: 103.6947984,
                                content: 'Winchester School<br/>WS50436<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwMCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NmZhZWQ0ZDBkNzFmNjgxMzYzNDhlYTBiNGIxZmIwYjVjNzcxM2Y4MzcyOWM0N2ViNTU4YmMyZGVlODU0NDUwMw',
                                lat: 1.3256185,
                                lng: 103.9393296,
                                content: 'Wisconsin International University<br/>WIU50964<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwNCIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MzY5OGFmMzc5N2E1YTgwOGQyNTNjYWE2OGE5MzFkM2I0ZTcwY2M3NjFmNjhhYTRlNjYwNzk0NzZmMTYxNmY2NA',
                                lat: 1.39488856,
                                lng: 103.8893303,
                                content: 'World High School<br/>WHS63354<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwNSIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.MmYwNmEyMWJmOGJlYzI2ZmZjNWRjODczZmQ4NWMxNmY5MjgzYzkyMzQ4OWY4NWVmNzRjOTBjNGRlZmE1NWE5OA',
                                lat: 1.37363767,
                                lng: 103.9496161,
                                content: 'World Information Distributed University<br/>WID63187<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUwNiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.NDQwNTBkMmU5MzBkZmY3NmI3ODk3NjUyMjkyODNiNGE2MjM3MjNjMWY2ODY2YzI0ZTlmNDAzYTE5MTUyNGUwOQ',
                                lat: 1.48078247,
                                lng: 103.8912133,
                                content: 'Worldwide International University<br/>WIU49349<br/><a href="http://www.google.com" target="_new">link</a>'
                            },
                            {
                                id: 'eyJpZCI6IjUxMiIsIjVjM2EwOWJmMjJlMTI0MTFiNmFmNDhkZmUwYjg1YzJkOWQxMTgxY2QzOTFlMDg5NTc0YzhjZjNjYTFlNWU0YWQiOiJ1dDg3dGQzNWM3N2JwOGs3NnZ2Z29ldmh1dSJ9.ZDIyYWM0NjRkZTRmYWYxNTZhN2E1NjUzODI5YTFhODI3M2YzYjIzNzc3ZTBlYWU4YjgyOTBhZDEyN2Q4YmIxNQ',
                                lat: 1.45902282,
                                lng: 103.8673464,
                                content: 'Yorker International University<br/>YIU41520<br/><a href="http://www.google.com" target="_new">link</a>'
                            }
                        ],
                        marker: {
                            icon: 'university',
                            markerColor: 'green',
                            prefix: 'fa',
                            iconColor: 'white',
                            title: 'Group 4',
                            id: "group_4"
                        }
                    }
                };
            }

        }]);
})();
