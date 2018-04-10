//Mindmap v.1.0.0
(function() {
    'use strict';

    angular.module('OE_Styleguide')
        .controller('SgMindmapCtrl', ['$scope', function($scope, $window) {

            $scope.nodes = {
                'a': { id: 'a', childs: ['d'], 'type': 'database', title: 'Database 0', description: 'this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave' },
                'b': { id: 'b', childs: ['d', 'g', 'h', 'i', 'f'], 'type': 'database', title: 'Database 1', description: 'this is a test of long text.' },
                'c': { id: 'c', childs: ['h', 'f', 'i'], 'type': 'database', title: 'Database 2', description: 'this is a test of long text.' },

                'd': { id: 'd', childs: ['f'], parents: ['a', 'b'], 'type': 'cube', title: 'Cube 1', description: 'this is a test of long text.' },
                'e': { id: 'e', childs: ['1a'], parents: ['j'], 'type': 'cube', title: 'Cube 2', description: 'this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave' },
                'f': { id: 'f', childs: ['1b'], parents: ['b', 'c', 'd'], 'type': 'cube', title: 'Cube 3', description: 'this is a test of long text another complicated test is required to determin how it will behave' },

                'g': { id: 'g', childs: ['1c'], parents: ['b'], 'type': 'cube', title: 'Cube 4', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                'h': { id: 'h', 'type': 'cube', parents: ['b', 'c'], title: 'Cube 5', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                'i': { id: 'i', childs: ['1d'], parents: ['b', 'c'], 'type': 'cube', title: 'Cube 6', description: 'this is a test of long text another complicated test is required to determin how it will behave' },

                'j': { id: 'j', childs: ['e'], 'type': 'cube', title: 'cube but at first level' },

                'k': { id: 'k', 'type': 'cube', title: 'Cube 7', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                '1a': { id: '1a', childs: ['1c', '1d'], parents: ['e'], 'type': 'cube', title: 'Cube 8', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                '1b': { id: '1b', childs: ['1d'], parents: ['f'], 'type': 'cube', title: 'Cube 9', description: 'this is a test of long text another complicated test is required to determin how it will behave this is a test of long text another complicated test is required to determin how it will behave' },

                '1c': { id: '1c', childs: ['1d'], parents: ['g', '1a'], 'type': 'cube', title: 'Cube 10', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                '1d': { id: '1d', 'type': 'output', parents: ['i', '1a', '1b', '1c'], title: 'Output', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
                '1e': { id: '1e', 'type': 'cube', title: 'Loner', description: 'this is a test of long text another complicated test is required to determin how it will behave' },
            };
            
            $scope.config = {
                node: {
                    width: 200,
                    typeConfig: {
                        'database': {
                            class: 'kdx-mindmap-node-example-database', // controls color
                            iconType: 'icon',
                            icon: 'home',
                        },
                        'cube': {
                            iconType: 'icon',
                            icon: 'cube'
                        },
                        'output': {
                            class: 'kdx-mindmap-node-example-output',
                            iconType: 'image',
                            icon: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABkAAAApCAYAAADAk4LOAAAFgUlEQVR4Aa1XA5BjWRTN2oW17d3YaZtr2962HUzbDNpjszW24mRt28p47v7zq/bXZtrp/lWnXr337j3nPCe85NcypgSFdugCpW5YoDAMRaIMqRi6aKq5E3YqDQO3qAwjVWrD8Ncq/RBpykd8oZUb/kaJutow8r1aP9II0WmLKLIsJyv1w/kqw9Ch2MYdB++12Onxee/QMwvf4/Dk/Lfp/i4nxTXtOoQ4pW5Aj7wpici1A9erdAN2OH64x8OSP9j3Ft3b7aWkTg/Fm91siTra0f9on5sQr9INejH6CUUUpavjFNq1B+Oadhxmnfa8RfEmN8VNAsQhPqF55xHkMzz3jSmChWU6f7/XZKNH+9+hBLOHYozuKQPxyMPUKkrX/K0uWnfFaJGS1QPRtZsOPtr3NsW0uyh6NNCOkU3Yz+bXbT3I8G3xE5EXLXtCXbbqwCO9zPQYPRTZ5vIDXD7U+w7rFDEoUUf7ibHIR4y6bLVPXrz8JVZEql13trxwue/uDivd3fkWRbS6/IA2bID4uk0UpF1N8qLlbBlXs4Ee7HLTfV1j54APvODnSfOWBqtKVvjgLKzF5YdEk5ewRkGlK0i33Eofffc7HT56jD7/6U+qH3Cx7SBLNntH5YIPvODnyfIXZYRVDPqgHtLs5ABHD3YzLuespb7t79FY34DjMwrVrcTuwlT55YMPvOBnRrJ4VXTdNnYug5ucHLBjEpt30701A3Ts+HEa73u6dT3FNWwflY86eMHPk+Yu+i6pzUpRrW7SNDg5JHR4KapmM5Wv2E8Tfcb1HoqqHMHU+uWDD7zg54mz5/2BSnizi9T1Dg4QQXLToGNCkb6tb1NU+QAlGr1++eADrzhn/u8Q2YZhQVlZ5+CAOtqfbhmaUCS1ezNFVm2imDbPmPng5wmz+gwh+oHDce0eUtQ6OGDIyR0uUhUsoO3vfDmmgOezH0mZN59x7MBi++WDL1g/eEiU3avlidO671bkLfwbw5XV2P8Pzo0ydy4t2/0eu33xYSOMOD8hTf4CrBtGMSoXfPLchX+J0ruSePw3LZeK0juPJbYzrhkH0io7B3k164hiGvawhOKMLkrQLyVpZg8rHFW7E2uHOL888IBPlNZ1FPzstSJM694fWr6RwpvcJK60+0HCILTBzZLFNdtAzJaohze60T8qBzyh5ZuOg5e7uwQppofEmf2++DYvmySqGBuKaicF1blQjhuHdvCIMvp8whTTfZzI7RldpwtSzL+F1+wkdZ2TBOW2gIF88PBTzD/gpeREAMEbxnJcaJHNHrpzji0gQCS6hdkEeYt9DF/2qPcEC8RM28Hwmr3sdNyht00byAut2k3gufWNtgtOEOFGUwcXWNDbdNbpgBGxEvKkOQsxivJx33iow0Vw5S6SVTrpVq11ysA2Rp7gTfPfktc6zhtXBBC+adRLshf6sG2RfHPZ5EAc4sVZ83yCN00Fk/4kggu40ZTvIEm5g24qtU4KjBrx/BTTH8ifVASAG7gKrnWxJDcU7x8X6Ecczhm3o6YicvsLXWfh3Ch1W0k8x0nXF+0fFxgt4phz8QvypiwCCFKMqXCnqXExjq10beH+UUA7+nG6mdG/Pu0f3LgFcGrl2s0kNNjpmoJ9o4B29CMO8dMT4Q5ox8uitF6fqsrJOr8qnwNbRzv6hSnG5wP+64C7h9lp30hKNtKdWjtdkbuPA19nJ7Tz3zR/ibgARbhb4AlhavcBebmTHcFl2fvYEnW0ox9xMxKBS8btJ+KiEbq9zA4RthQXDhPa0T9TEe69gWupwc6uBUphquXgf+/FrIjweHQS4/pduMe5ERUMHUd9xv8ZR98CxkS4F2n3EUrUZ10EYNw7BWm9x1GiPssi3GgiGRDKWRYZfXlON+dfNbM+GgIwYdwAAAAASUVORK5CYII=',
                        }
                    },
                    selectOnLoadId: 14,
                },
                link: {
                    activeClass: 'kdx-mindmap-link-active',
                },
                border: true,
                zoomButton: true,
                iconLinkPath: 'open_emis/js/angular/ngx-adaptor/assets/font-awesome/font-awesome.svg#icon-',
                id: 'exampleMindmap',
            };

            $scope.api = {};

        }]);
})();
