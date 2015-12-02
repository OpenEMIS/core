var sheet = (function() {
// Create the <style> tag
    var style = document.createElement("style");

    // Add a media (and/or media query) here if you'd like!
    // style.setAttribute("media", "screen")
    // style.setAttribute("media", "only screen and (max-width : 1024px)")

    // WebKit hack :(
    style.appendChild(document.createTextNode(""));

    //addCSSRule(style.sheet, ".left-pane", "background-color: #FF0;", 0);

    // Add the <style> element to the page
    document.head.appendChild(style);

    return style.sheet;
})();

pos = localStorage.lastHandlerPos;

if(typeof pos == 'undefined'){
    pos = window.innerWidth * 0.1;
}
// console.log(window.innerWidth);
sheet.insertRule('.left-pane{width:'+ pos + 'px;}', 0); 


angular.module('bgDirectives', [])
    .directive('bgSplitter', function() {
        return {
            restrict: 'E',
            replace: true,
            transclude: true,
            scope: {
                orientation: '@'
            },
            template: '<div class="split-panes {{orientation}}" ng-transclude></div>',
            controller: ['$scope', function($scope) {
                $scope.panes = [];

                this.addPane = function(pane) {
                    if ($scope.panes.length > 1)
                        throw 'splitters can only have two panes';
                    $scope.panes.push(pane);
                    return $scope.panes.length;
                };
            }],
            link: function(scope, element, attrs) {
                var handler = angular.element('<div class="split-handler"></div>');
                var pane1 = scope.panes[0];
                var pane2 = scope.panes[1];
                var vertical = scope.orientation == 'vertical';
                var pane1Min = pane1.minSize || 0;
                var pane2Min = pane2.minSize || 0;

                var bodyDir = getComputedStyle(document.body).direction;
                var affectedDir = (bodyDir == 'ltr') ? 'left' : 'right';

                pane1Min = ((pane1.minSizeP / 100) * window.innerWidth) || pane1Min;
                pane2Min = ((pane2.minSizeP / 100) * window.innerWidth) || pane2Min;
                var drag = false;

                pane1.elem.after(handler);

                if (!angular.isUndefined(localStorage.lastHandlerPos)) {
                    pos = localStorage.lastHandlerPos;

                    handler.css(affectedDir, pos + 'px');
                    pane1.elem.css('width', pos + 'px');
                    pane2.elem.css(affectedDir, pos + 'px');
                }

                element.bind('mousemove', function(ev) {
                    if (!drag) return;

                    var bounds = element[0].getBoundingClientRect();
                    var pos = 0;

                    $.each($('.highchart'), function(key, group) {
                        $(group).highcharts().reflow();
                    });

                    if (vertical) {

                        var height = bounds.bottom - bounds.top;
                        pos = ev.clientY - bounds.top;

                        if (pos < pane1Min) return;
                        if (height - pos < pane2Min) return;

                        handler.css('top', pos + 'px');
                        pane1.elem.css('height', pos + 'px');
                        pane2.elem.css('top', pos + 'px');

                    } else {
                        var width = bounds.right - bounds.left;

                        if (bodyDir == 'ltr') {
                            pos = ev.clientX - bounds.left;
                        } else {
                            pos = bounds.right - ev.clientX;
                        }

                        if (pos < pane1Min) return;

                        if (bodyDir == 'ltr') {
                            if (width - pos < pane2Min) return;
                        } else {
                            if (pos > width - pane2Min) return;
                        }

                        handler.css(affectedDir, pos + 'px');
                        pane1.elem.css('width', pos + 'px');
                        pane2.elem.css(affectedDir, pos + 'px');
                        localStorage.lastHandlerPos = pos;
                    }
                });

                handler.bind('mousedown', function(ev) {
                    ev.preventDefault();
                    drag = true;
                });

                angular.element(document).bind('mouseup', function(ev) {
                    drag = false;
                });
            }
        };
    })
    .directive('bgPane', function() {
        return {
            restrict: 'E',
            require: '^bgSplitter',
            replace: true,
            transclude: true,
            scope: {
                minSize: '=',
                minSizeP: '=',
            },
            template: '<div class="split-pane{{index}}" ng-transclude></div>',
            link: function(scope, element, attrs, bgSplitterCtrl) {
                scope.elem = element;
                scope.index = bgSplitterCtrl.addPane(scope);
            }
        };
    });