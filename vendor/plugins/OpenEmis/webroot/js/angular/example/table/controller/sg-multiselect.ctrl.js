//Multi Select v.1.0.1
(function() {
    'use strict';

    agGrid.initialiseAgGridWithAngular1(angular);

    angular.module('sg-multi-select.ctrl', ['agGrid', 'kd-angular-multi-select'])
        .controller('SgMultiSelectCtrl', SgMultiSelectCtrl);

    SgMultiSelectCtrl.$inject = ['$scope'];

    function SgMultiSelectCtrl($scope) {

        // var bodyDir = getComputedStyle(document.body).direction;
        var suppressMenu = true;
        var suppressSorting = true;

        var columnTopData = [
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50 },
            { headerName: "OpenEMIS ID", field: "id", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Name", field: "name", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Gender", field: "gender", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Education Grade", field: "grade", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Student Status", field: "status", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 }
        ];

        var rowTopData = [

            { checkbox: '', id: 'S101010101', name: 'Benjamin Lee 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S123123121', name: 'Dan Jones 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S890890891', name: 'Jamie Teo 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S678678671', name: 'Kenny Lee 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S908347741', name: 'Matthew 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S536768781', name: 'Nial Buchnan 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S456456451', name: 'Sarah Amy Martin 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S098098091', name: 'Veronica Lim 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },
            { checkbox: '', id: 'S765765761', name: 'Viknesh 1', gender: 'Male', grade: 'Pre−Primary 1', status: 'Enrolled' },

            { checkbox: '', id: 'S101010102', name: 'Benjamin Lee 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S123123122', name: 'Dan Jones 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S890890892', name: 'Jamie Teo 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S678678672', name: 'Kenny Lee 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S908347742', name: 'Matthew 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S536768782', name: 'Nial Buchnan 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S456456452', name: 'Sarah Amy Martin 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S098098092', name: 'Veronica Lim 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },
            { checkbox: '', id: 'S765765762', name: 'Viknesh 2', gender: 'Male', grade: 'Pre−Primary 2', status: 'Enrolled' },

            { checkbox: '', id: 'S101010103', name: 'Benjamin Lee 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S123123123', name: 'Dan Jones 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S890890893', name: 'Jamie Teo 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S678678673', name: 'Kenny Lee 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S908347743', name: 'Matthew 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S536768783', name: 'Nial Buchnan 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S456456453', name: 'Sarah Amy Martin 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S098098093', name: 'Veronica Lim 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },
            { checkbox: '', id: 'S765765763', name: 'Viknesh 3', gender: 'Male', grade: 'Pre−Primary 3', status: 'Enrolled' },

            { checkbox: '', id: 'S101010104', name: 'Benjamin Lee 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S123123124', name: 'Dan Jones 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S890890894', name: 'Jamie Teo 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S678678674', name: 'Kenny Lee 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S908347744', name: 'Matthew 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S536768784', name: 'Nial Buchnan 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S456456454', name: 'Sarah Amy Martin 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S098098094', name: 'Veronica Lim 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },
            { checkbox: '', id: 'S765765764', name: 'Viknesh 4', gender: 'Male', grade: 'Pre−Primary 4', status: 'Enrolled' },

            { checkbox: '', id: 'S101010105', name: 'Benjamin Lee 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S123123125', name: 'Dan Jones 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S890890895', name: 'Jamie Teo 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S678678675', name: 'Kenny Lee 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S908347745', name: 'Matthew 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S536768785', name: 'Nial Buchnan 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S456456455', name: 'Sarah Amy Martin 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S098098095', name: 'Veronica Lim 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },
            { checkbox: '', id: 'S765765765', name: 'Viknesh 5', gender: 'Male', grade: 'Pre−Primary 5', status: 'Enrolled' },

            { checkbox: '', id: 'S101010106', name: 'Benjamin Lee 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S123123126', name: 'Dan Jones 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S890890896', name: 'Jamie Teo 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S678678676', name: 'Kenny Lee 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S908347746', name: 'Matthew 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S536768786', name: 'Nial Buchnan 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S456456456', name: 'Sarah Amy Martin 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S098098096', name: 'Veronica Lim 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },
            { checkbox: '', id: 'S765765766', name: 'Viknesh 6', gender: 'Male', grade: 'Pre−Primary 6', status: 'Enrolled' },

            { checkbox: '', id: 'S123123127', name: 'Dan Jones 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S890890897', name: 'Jamie Teo 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S678678677', name: 'Kenny Lee 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S908347747', name: 'Matthew 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S536768787', name: 'Nial Buchnan 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S456456457', name: 'Sarah Amy Martin 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S098098097', name: 'Veronica Lim 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
            { checkbox: '', id: 'S765765767', name: 'Viknesh 7', gender: 'Male', grade: 'Pre−Primary 7', status: 'Enrolled' },
        ];

        //Bottom Data
        var columnBottomData = [
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50 },
            { headerName: "OpenEMIS ID", field: "id", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Name", field: "name", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Gender", field: "gender", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Education Grade", field: "grade", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Student Status", field: "status", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 }
        ];

        var rowBottomData = [
            { checkbox: "", id: "S872348734", name: 'Jade Spade', gender: "Male", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S964587346", name: 'Kamilia Sudin', gender: "Female", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S839573455", name: 'Kate Spade', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S092552562", name: 'Kenneth Tan', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S646374822", name: 'Marlene Lene Mark', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S987859598", name: 'Mary D Marlene', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S878345684", name: 'Rosalinda Amor', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S284756489", name: 'Ryan Nam', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S874569854", name: 'Thalia Nate', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" }
        ];

        $scope.gridOptionsTop = {
            columnDefs: columnTopData,
            rowData: rowTopData,
            primaryKey: 'id' // custom attribute to use to match both tables
        };

        $scope.gridOptionsBottom = {
            columnDefs: columnBottomData,
            rowData: rowBottomData,
            primaryKey: 'id' // custom attribute to use to match both tables
        };

        // $scope.config = {
        //     topCheckboxLabel: "Unassign Staff",
        //     topSearchPlaceholder: "Search Unassign",
        //     topToBottomButton: "Student Assign",
        //     bottomToTopButton: "Student Unassign",
        //     bottomCheckboxLabel: "Assign Staff",
        //     bottomSearchPlaceholder: "Search Assign"
        // }
    }
})();
