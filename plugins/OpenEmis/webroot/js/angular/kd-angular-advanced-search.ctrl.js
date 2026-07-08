//Advanced Search v.1.0.1
(function () {
    'use strict';

    angular.module('advanced.search.ctrl', [])
        .controller('AdvancedSearchCtrl', ['$scope', '$location', '$timeout', function ($scope, $location, $timeout) {
            // POCOR-8219 redone some parts
            //Advanced Search
            $scope.searchResultsHeader = false;
            $scope.selectedState = '';
            $scope.disableElement = '';
            $scope.classification = '';
            $scope.showEducationalSearch = true;
            $scope.fieldOptions = [];
            $scope.educationSystems = []; // Array to store education systems
            $scope.educationLevels = []; // Array to store education levels
            $scope.educationPrograms = []; // Array to store education programs
            $scope.filteredLevels = []; // Array to store education levels
            $scope.filteredPrograms = []; // Array to store education programs
            $scope.selectedSystem = "1";
            $scope.selectedLevel = "2";
            $scope.selectedProgram = "3";
            //Show or Hide the Advanced Search Component
            $scope.toggleAdvancedSearch = function () {
                $scope.showAdvSearch = !$scope.showAdvSearch;

                if ($scope.showAdvSearch) {
                    $scope.selectedState = 'btn-toggled';
                    $scope.disableElement = 'disable-element';
                } else {
                    $scope.selectedState = '';
                    $scope.disableElement = '';
                }
            }

            //Remove the Advanced Search Component
            $scope.removeAdvSearch = function () {
                $scope.showAdvSearch = false;
                $scope.selectedState = '';
                $scope.disableElement = '';
            }

            //Reset the values on the Advanced Search
            $scope.resetFields = function() {
                var searchForm = angular.element('#search-form');
                //POCOR-9644
                if (!searchForm.length) {
                    var resetBtn = document.getElementById('reset');
                    if (resetBtn && resetBtn.form) {
                        searchForm = angular.element(resetBtn.form);
                    } else {
                        searchForm = angular.element('form').first();
                    }
                }
                var resetCheckbox = angular.element('input[type="checkbox"]');

                if (!angular.isUndefined($scope.originalModel) && $scope.originalModel !== null) {
                    $scope.inputModelText = angular.copy($scope.originalModel);
                } else {
                    $scope.inputModelText = {};
                }
                //POCOR-9644

                searchForm.find('input:text, select').val('');
                resetCheckbox.removeAttr('checked');

                //POCOR-9644
                // Clear server-side AdvanceSearch session values (AdvanceSearchBehavior checks reset=Reset).
                if (searchForm.length && searchForm[0]) {
                    var resetInput = searchForm.find('input[name="reset"]');
                    if (!resetInput.length) {
                        resetInput = angular.element('<input type="hidden" name="reset" />');
                        searchForm.append(resetInput);
                    }
                    resetInput.val('Reset');
                    searchForm[0].submit();
                } else {
                    window.location.reload();
                }
                //POCOR-9644
            }

            $scope.submitSearch = function () {
                $scope.showAdvSearch = false;
                $scope.searchResultsHeader = true;
                $scope.selectedState = '';
                $scope.disableElement = '';
            }
            $scope.initEduField = function (educationData) {

                if (!$scope.fieldOptions['education_systems']) {

                    // Array is empty
                    // Transform education data into separate arrays for systems, levels, and programs
                    educationData.forEach(function (item) {
                        var system_exists = $scope.educationSystems.some(function (system) {
                            return system.id === item.education_system_id;
                        });
                        var level_exists = $scope.educationLevels.some(function (level) {
                            return level.id === item.education_level_id;
                        });
                        var program_exists = $scope.educationPrograms.some(function (program) {
                            return program.id === item.education_program_id;
                        });
                        if (!system_exists) {
                            $scope.educationSystems.push({
                                id: item.education_system_id,
                                value: item.education_system_id,
                                label: item.education_system_name
                            });
                        }
                        ;
                        if (!level_exists) {
                            $scope.educationLevels.push({
                                id: item.education_level_id,
                                value: item.education_level_id,
                                label: item.education_level_name,
                                system_id: item.education_system_id
                            });
                        }
                        if (!program_exists) {
                            $scope.educationPrograms.push({
                                id: item.education_program_id,
                                value: item.education_program_id,
                                label: item.education_program_name,
                                level_id: item.education_level_id
                            });
                        }

                    });
                    $timeout(function () {
                        $scope.fieldOptions['education_systems'] = $scope.educationSystems;
                        $scope.fieldOptions['education_levels'] = $scope.educationLevels;
                        $scope.fieldOptions['education_programmes'] = $scope.educationPrograms;
                    });

                    // Update scope variables here
                }
            };
            $scope.onChangeClassification = function () {
                if ($scope.classification === '1') {
                    $scope.showEducationalSearch = true;
                } else if ($scope.classification === '2') {
                    $scope.showEducationalSearch = false;
                } else {
                    // Default options or empty options if needed
                    $scope.showEducationalSearch = true;
                }
            };

            $scope.updateLevels = function () {
                if ($scope.selectedSystem) {
                    $scope.filteredLevels = $scope.educationLevels.filter(function (level) {
                        return level.system_id === $scope.selectedSystem;
                    });
                }
                $scope.selectedLevel = null; // Reset selected level when system changes
            };

            $scope.updatePrograms = function () {
                $scope.filteredPrograms = $scope.educationPrograms.filter(function (program) {
                    return program.level_id === $scope.selectedLevel;
                });
                $scope.selectedProgram = null; // Reset selected program when level changes
            };


        }]);
})();
