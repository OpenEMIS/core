//Multi Select v.1.0.0

angular.module('kd-angular-tree-dropdown', [])
.directive('kdTreeDropdownNg', kdTreeDropdown);

function kdTreeDropdown() {
    var directive = {
        restrict: 'E',
        replace: true,
        transclude: true,
        template: "<div class='tree-dropdown'>\
                        <multi-select-tree \
                        data-input-model='inputModel' \
                        multi-select='isMultiple' \
                        data-output-model='outputModel' \
                        data-callback='selectionCallback(item, selectedItems)'\
                        data-expand-click='expandClicked()'\
                        tree-id='{{elementId}}'\
                        ></multi-select-tree>\
                </div>\
        ",
        controller: kdTreeDropdownCtrl,
        link: kdTreeDropdownLink,
        scope: {
            inputModel: '=',
            outputModel: '=?',
            modelType: '@?'
        }
    };
    return directive;


    function kdTreeDropdownCtrl($scope) {
        $scope.selectedItem = [];
        // $scope.selectionText = "Please select";
        $scope.isMultiple = (angular.isDefined($scope.modelType) && $scope.modelType == "single") ? false : true;

        $scope.$watch('outputModel', function(_newSelectedList){
            updateSelectionText(generateSelectionText(_newSelectedList), $scope.elementId);
        })
    }

    function kdTreeDropdownLink(_scope, _element, _attrs) {
        _scope.elementId = getElementId(_element);

        angular.element(document).ready(function() {
            addInputText(_scope);
            angular.element(document.querySelector("#" + _scope.elementId + " .tree-input")).addClass("input-select-wrapper");
        });

        _scope.selectionCallback = function(_item, _selectedItem) {
            if(typeof _item == "undefined") return false;
            //updateSelectionText(_selectedItem, _item, _scope.elementId);
            return true;
        }
    }

    function generateSelectionText(_selectedList) {
        var selectionText = "Please select";

        if (angular.isDefined(_selectedList)) {
            if (_selectedList.length == 1) selectionText = _selectedList[0].name;
            else if (_selectedList.length > 1) selectionText = _selectedList.length + " items selected";
        }

        return selectionText;
    }

    function updateSelectionText(_selectionText, _elementId) {
        var selectionTextEle = angular.element(document.querySelector(_elementId + " .tree-selection-text"));
        selectionTextEle.text(_selectionText);
    }

    // function updateSelectionText(_selectedItem, _item, _elementId) {
    //     var selectionTextEle = angular.element(document.querySelector(_elementId + " .tree-selection-text"));

    //     if(_selectedItem.length == 0) selectionText = "Please select";
    //     else if(_selectedItem.length == 1) selectionText = _selectedItem[0].name;
    //     else selectionText = _selectedItem.length + " items selected";

    //     selectionTextEle.text(selectionText);
    // }

    function addInputText(_scope) {
        var div = angular.element("<div class='tree-selection-text'></div>");
        div.text(generateSelectionText(_scope.outputModel));
        angular.element(document.querySelector("#" + _scope.elementId + " .tree-control")).append(div);
    }

    function getElementId(_element) {
        if (angular.isDefined(_element[0].id) && _element[0].id != "") return _element[0].id;
        return "tree-dropdown";
    }
}