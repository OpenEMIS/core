//Content Splitter v.1.0.0

angular.module('OE_Styleguide')
    .controller('SgContentSplitterCtrl', function($scope, $window) {

        var time = 550;

        //to access the splitter elements 
        $scope.getSplitterElements = function (_response){
            $scope.splitElems = _response;
            console.log($scope.splitElems);
        };

        $scope.splitterDragCallback = function (_response){
            console.log('splitterDragCallback = '+_response);
        }

        //On click on responsive button
        $scope.showSplitContentResponsive = function() {  
            addTransition($scope.splitElems.left);
            removeOverlayContent($scope.splitElems.left);
            addSlideInAnimation($scope.splitElems.right);

            setTimeout(function(){
                removeTransition($scope.splitElems.left);
                removeTransition($scope.splitElems.right);
            },time);

            showContentSplitHandler();
        }

        //For with Button Splitter View
        $scope.showSplitContentOverlay = function() {
            addTransition($scope.splitElems.left);
            addSlideInAnimation($scope.splitElems.right);

            setTimeout(function(){
                removeTransition($scope.splitElems.left);
                removeTransition($scope.splitElems.right);
            },time);

            showContentSplitHandler();
        }

        //For without Button Splitter
        $scope.removeSplitContentResponsive = function() {
            removeContentSplitHandler();
        }

        // On Click Button - Remove Split Handler
        function removeContentSplitHandler() {
            addSlideOutAnimation($scope.splitElems.splitter);
            addSlideOutAnimation($scope.splitElems.right);
            addOverlayContent($scope.splitElems.left);
            addTransition($scope.splitElems.left);

            setTimeout(function(){
                removeTransition($scope.splitElems.splitter);
                removeTransition($scope.splitElems.right);
                removeTransition($scope.splitElems.left);
            },time);
        }

        //On Click Button - Show Split Handler
        function showContentSplitHandler() {
            addSlideInAnimation($scope.splitElems.splitter);

            setTimeout(function(){
                removeTransition($scope.splitElems.left);
                removeTransition($scope.splitElems.splitter);
            },time);
        }

        //Slide in action
        function addSlideInContent(_elem) {
            _elem.addClass('splitter-slide-in').removeClass('splitter-slide-out').removeClass('slide-transition');  
        }

        //Slide out action
        function addSlideOutContent(_elem) {
            _elem.addClass('splitter-slide-out').removeClass('splitter-slide-in').removeClass('slide-transition');
        }

        //Slide in action with animation
        function addSlideInAnimation(_elem) {
            _elem.addClass('splitter-slide-in').removeClass('splitter-slide-out').addClass('slide-transition');
        }

        //Slide out action with animation
        function addSlideOutAnimation(_elem) {
            _elem.addClass('splitter-slide-out').removeClass('splitter-slide-in').addClass('slide-transition');
        }

        //Add Transition Only
        function addTransition(_elem) {
            _elem.addClass('slide-transition');
        }

        //Remove Transition Only
        function removeTransition(_elem) {
            _elem.removeClass('slide-transition');
        }

        //Add Overlay Content Only
        function addOverlayContent(_elem) {
            _elem.addClass('overlay-content');
        }

        //Remove Overlay Content Only
        function removeOverlayContent(_elem) {
            _elem.removeClass('overlay-content');
        }

    });