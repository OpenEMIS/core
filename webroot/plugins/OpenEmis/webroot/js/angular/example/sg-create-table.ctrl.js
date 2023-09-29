//Create Table v.1.0.0

angular.module('OE_Styleguide')
    .controller('SgCreateTableCtrl', ['$scope', function($scope) {

        function copy(_obj) {
            if (angular.isArray(_obj)) {
                return angular.merge([], _obj);
            } else if (angular.isObject(_obj)) {
                return angular.merge({}, _obj);
            }
            return _obj;
        }

        var defaultBtnObj = {
            'showButton': true,
            'buttonIcon': 'kd-alphabet'
        };

        $scope.init = function() {
            var bodyDir = getComputedStyle(document.body).direction;
            $scope.affectedDir = (bodyDir == 'ltr') ? 'left' : 'right';

            // initialize the array
            $scope.data = [];

            // by default show table placeholder
            $scope.showPlaceholder = true;
            $scope.isDisabled = true;

            $scope.removeIconRow = true;
            $scope.removeIconCol = true;

            $scope.headerDirection = "horizontal";
            $scope.headerIcon = "kd-header-row";
            $scope.validateIcon = "kd-validate-row";

            setIconValidation();
            setupValidationDropdown();
        }

        // add a column
        $scope.addColumn = function(_direction) {
            //you must cycle all the rows and add a column 
            //to each one
            $scope.data.forEach(function($row) {
                if (($scope.headerDirection == 'horizontal') || ($scope.headerDirection == 'vertical')) {
                    $row.push(copy(defaultBtnObj));
                } else if ($scope.headerDirection == 'both') {
                    if ($scope.validateIcon == "kd-validate-row") {
                        $row.push(copy(defaultBtnObj));
                    } else if ($scope.validateIcon == "kd-validate-col") {
                        var newCol = copy(defaultBtnObj);
                        newCol.showButton = false;
                        $row.push(newCol);
                    }
                }

            });
        };

        $scope.shouldDisableButton = function(_direction) {
            if ($scope.data.length == 0) {
                return _direction == 'horizontal' ? false : true;
            }
            return (_direction == $scope.headerDirection);
        }

        // remove the selected column
        $scope.removeColumn = function(index) {
            // remove the column specified in index
            // you must cycle all the rows and remove the item
            // row by row
            $scope.data.forEach(function(row) {
                row.splice(index, 1);

                //if no columns left in the row push a blank array
                if (row.length === 0) {
                    row.data = [];
                    $scope.data = [];
                    $scope.showPlaceholder = true;
                    $scope.isDisabled = true;
                    resetToolbar();
                }
            });
        };

        //add a row in the array
        $scope.addRow = function(isDisabled, _direction) {
            // create a blank array
            var newrow = [];

            // if array is blank add a standard item
            if ($scope.data.length === 0) {
                $scope.isDisabled = false;

                $scope.removeIconRow = true;
                $scope.removeIconCol = true;

                newrow = [copy(defaultBtnObj)];

                if ($scope.headerDirection == 'horizontal') {
                    $scope.data = $scope.data.concat([copy(newrow), copy(newrow)]);
                }

            } else {
                // else cycle thru the first row's columns
                // and add the same number of items
                $scope.data[0].forEach(function(col) {
                    if (($scope.headerDirection == 'horizontal') || ($scope.headerDirection == 'vertical')) {
                        newrow.push(copy(defaultBtnObj));
                    } else if ($scope.headerDirection == 'both') {
                        if ($scope.validateIcon == "kd-validate-row") {
                            var newCol = copy(defaultBtnObj);
                            newCol.showButton = false;
                            newrow.push(newCol);
                        } else if ($scope.validateIcon == "kd-validate-col") {
                            newrow.push(copy(defaultBtnObj));
                        }
                    }
                });

            }
            // add the new row at the end of the array 
            $scope.data.push(newrow);

            // when add row button is clicked, table placeholder is hidden
            $scope.showPlaceholder = false;
        };

        // remove the selected row
        $scope.removeRow = function(index) {
            // remove the row specified in index
            $scope.data.splice(index, 1);

            // if no rows left in the array create a blank array
            if ($scope.data.length === 0) {
                $scope.data = [];
                $scope.showPlaceholder = true;
                $scope.isDisabled = true;
                resetToolbar();
            }
        };

        // remove button toggle
        $scope.removeToggle = function() {
            var removeElement = angular.element(document.querySelector('#removeElement'));
            removeElement.toggleClass('btn-red');

            if ($scope.headerDirection == 'horizontal') {
                $scope.removeIconRow = $scope.removeIconRow === false ? false : true;
                $scope.removeIconCol = $scope.removeIconCol === false ? true : false;
            } else if ($scope.headerDirection == 'vertical') {
                $scope.removeIconRow = $scope.removeIconRow === false ? true : false;
                $scope.removeIconCol = $scope.removeIconCol === false ? false : true;
            } else if ($scope.headerDirection == 'both') {
                $scope.removeIconRow = $scope.removeIconRow === false ? true : false;
                $scope.removeIconCol = $scope.removeIconCol === false ? true : false;
            }
        };

        // swap the number of row and columns when toggle from horizontal to vertical, vice versa
        function swapHeaderDirection(_oldDirection, _newDirection) {
            if ($scope.data.length > 0 && _oldDirection != _newDirection && _oldDirection != 'both' && _newDirection != 'both') {
                var newTable = [];
                var firstRow = $scope.data[0];
                angular.forEach(firstRow, function(col) {
                    var newRow = [];
                    angular.forEach($scope.data, function(row) {
                        newRow.push(copy(defaultBtnObj));
                    });
                    newTable.push(newRow);
                });
                $scope.data = newTable;
            }
            $scope.headerDirection = _newDirection;
        }

        // header layout dropdown: horizontal, vertical, both
        $scope.isTableHeader = function(_isFirstRow, _isFirstColumn) {

            if ($scope.headerDirection == 'horizontal' && _isFirstRow) {
                return true;
            } else if ($scope.headerDirection == 'vertical' && _isFirstColumn) {
                return true;
            } else if ($scope.headerDirection == 'both' && (_isFirstColumn || _isFirstRow)) {
                return true;
            }

            return false;
        }

        // set header type
        $scope.setHeaderType = function(_direction) {
            swapHeaderDirection($scope.headerDirection, _direction);

            setIconHeader();

            setIconValidation();
            setupValidationDropdown();
            resetRemoveButton();
        }

        // set icon header when clicked the header layout
        function setIconHeader() {
            if ($scope.headerDirection == 'horizontal') {
                $scope.headerIcon = "kd-header-row";
            } else if ($scope.headerDirection == 'vertical') {
                $scope.headerIcon = "kd-header-col";
            } else if ($scope.headerDirection == 'both') {
                $scope.headerIcon = "kd-header-row-col";
            }
        }

        // show/hide item on the validation dropdown
        function setupValidationDropdown() {
            var validateRow = angular.element(document.querySelector('.validate-row'));
            var validateCol = angular.element(document.querySelector('.validate-col'));

            if ($scope.headerDirection == 'horizontal') {
                validateRow.css('display', 'block');
                validateCol.css('display', 'none');
            } else if ($scope.headerDirection == 'vertical') {
                validateRow.css('display', 'none');
                validateCol.css('display', 'block');
            } else if ($scope.headerDirection == 'both') {
                validateRow.css('display', 'block');
                validateCol.css('display', 'block');
            }
        }

        // set validation type
        $scope.setValidationType = function(_direction, _column, _row) {
            setIconValidation(_direction);

            setupValidationDropdown();

            // if it is first row
            angular.forEach($scope.data[0], function(_column) {
                _column;
            });

            // if it is first column
            angular.forEach($scope.data, function(_row) {
                _row[0];
            });
        }

        // change validation icon on the toolbar buttons
        function setIconValidation(_direction) {
            var validationButton = angular.element(document.querySelector('#btn-append-to-to-body'));
            var firstRow = $scope.data[0];

            if ($scope.headerDirection == 'horizontal') {
                $scope.validateIcon = "kd-validate-row";
                validationButton.css('display', 'block');

                angular.forEach(firstRow, function(_column) {
                    _column.showButton = true;
                });

                angular.forEach($scope.data, function(_row) {
                    var firstColumn = _row[0];
                    firstColumn.showButton = true;
                });

            } else if ($scope.headerDirection == 'vertical') {
                $scope.validateIcon = "kd-validate-col";
                validationButton.css('display', 'block');

                angular.forEach(firstRow, function(_column) {
                    _column.showButton = true;
                });

                angular.forEach($scope.data, function(_row) {
                    var firstColumn = _row[0];
                    firstColumn.showButton = true;
                });

            } else if ($scope.headerDirection == 'both') {
                validationButton.css('display', 'none');
                // var shouldShowRow = $scope.data[0][$scope.data[0].length - 1].showButton;
                // var shouldShowCol = $scope.data[$scope.data.length - 1][0].showButton;
                $scope.validateIcon = "kd-validate-row";

                angular.forEach(firstRow, function(_column) {
                    _column.showButton = true;
                });
                angular.forEach($scope.data, function(_row) {
                    var firstColumn = _row[0];
                    firstColumn.showButton = false;
                });

                if (angular.isDefined(_direction)) {
                    if (_direction == "row") {
                        $scope.validateIcon = "kd-validate-row";

                        angular.forEach(firstRow, function(_column) {
                            _column.showButton = true;
                        });

                        angular.forEach($scope.data, function(_row) {
                            var firstColumn = _row[0];
                            firstColumn.showButton = false;
                        });

                    } else if (_direction == "col") {
                        $scope.validateIcon = "kd-validate-col";

                        angular.forEach(firstRow, function(_column) {
                            _column.showButton = false;
                        });

                        angular.forEach($scope.data, function(_row) {
                            var firstColumn = _row[0];
                            firstColumn.showButton = true;
                        });
                    }
                }
            }
        }

        // reset toolbar button to default state
        function resetToolbar() {
            if ($scope.removeIcon = true) {
                var removeElement = angular.element(document.querySelector('#removeElement'));
                removeElement.removeClass('btn-red');
            }

            $scope.headerDirection = "horizontal";
            $scope.headerIcon = "kd-header-row";
            $scope.validateIcon = "kd-validate-row";
        }

        // reset remove button as the header changes layout
        function resetRemoveButton() {
            if ($scope.removeIcon = true) {
                var removeElement = angular.element(document.querySelector('#removeElement'));
                removeElement.removeClass('btn-red');
            }

            if ($scope.headerDirection == 'horizontal') {
                $scope.removeIconRow = true;
                $scope.removeIconCol = true;
            } else if ($scope.headerDirection == 'vertical') {
                $scope.removeIconRow = true;
                $scope.removeIconCol = true;
            } else if ($scope.headerDirection == 'both') {
                $scope.removeIconRow = true;
                $scope.removeIconCol = true;
            }

        }

        // set the input button direction
        $scope.setInputType = function(_column, _inputDirection) {
            updateInputButtonIcon(_column, _inputDirection);
        }

        // update the icon according to the input type selected
        function updateInputButtonIcon(_column, _inputType) {
            if (_inputType == 'alphabet') {
                _column.buttonIcon = "kd-alphabet";
            } else if (_inputType == 'numeric') {
                _column.buttonIcon = "kd-numeric";
            } else if (_inputType == 'alphanumeric') {
                _column.buttonIcon = "kd-alphanumeric";
            }
        }

    }]);
