//Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('sg-multi-select.ctrl', ['agGrid', 'kd-angular-multi-select'])
    .controller('SgMultiSelectCtrl', SgMultiSelectCtrl);

SgMultiSelectCtrl.$inject = ['$scope'];

function SgMultiSelectCtrl($scope) {

    var bodyDir = getComputedStyle(document.body).direction;
    var suppressMenu = true;
    var suppressSorting = true;

    if (bodyDir == 'ltr') {
        //Top Data
        var columnTopData = [
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' },
            { headerName: "OpenEMIS ID", field: "id", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Name", field: "name", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Gender", field: "gender", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Education Grade", field: "grade", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Student Status", field: "status", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 }
        ];

        var rowTopData = [
            { checkbox: "", id: "S101010101", name: 'Benjamin Lee', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S123123123", name: 'Dan Jones', gender: "Male", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S890890890", name: 'Jamie Teo', gender: "Female", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S678678678", name: 'Kenny Lee', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S908347746", name: 'Matthew', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S536768783", name: 'Nial Buchnan', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S456456456", name: 'Sarah Amy Martin', gender: "Female", grade: "Pre-Primary 1", status: "Transferred" },
            { checkbox: "", id: "S098098098", name: 'Veronica Lim', gender: "Female", grade: "Pre-Primary 1", status: "Enrolled" },
            { checkbox: "", id: "S765765765", name: 'Viknesh', gender: "Male", grade: "Pre-Primary 1", status: "Enrolled" }
        ];

        //Bottom Data
        var columnBottomData = [
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' },
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
    } else {
        //Top Data
        var columnTopData = [
            { headerName: "Student Status", field: "status", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Education Grade", field: "grade", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Gender", field: "gender", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Name", field: "name", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "OpenEMIS ID", field: "id", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' }
        ];

        var rowTopData = [
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Benjamin Lee' , id: "S101010101", checkbox: "" },
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Male", name: 'Dan Jones', id: "S123123123", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Female", name: 'Jamie Teo', id: "S890890890", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Kenny Lee', id: "S678678678", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Matthew', id: "S908347746", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Nial Buchnan', id: "S536768783", checkbox: "" },
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Sarah Amy Martin', id: "S456456456", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Female", name: 'Veronica Lim', id: "S098098098", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Viknesh', id: "S765765765", checkbox: "" }
        ];

        //Bottom Data
        var columnBottomData = [
            { headerName: "Student Status", field: "status", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Education Grade", field: "grade", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Gender", field: "gender", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "Name", field: "name", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "OpenEMIS ID", field: "id", filter: 'text', suppressMenu: suppressMenu, width: 200, minWidth: 200 },
            { headerName: "", field: "checkbox", checkboxSelection: true, suppressMenu: suppressMenu, suppressSorting: suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left' }
        ];

        var rowBottomData = [
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Male", name: 'Jade Spade', id: "S872348734", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Female", name: 'Kamilia Sudin', id: "S964587346", checkbox: "" },            
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Kate Spade', id: "S839573455", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Kenneth Tan', id: "S092552562", checkbox: "" },    
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Marlene Lene Mark', id: "S646374822", checkbox: "" },                  
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Mary D Marlene', id: "S987859598", checkbox: "" },
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Rosalinda Amor', id: "S878345684", checkbox: "" },
            { status: "Enrolled", grade: "Pre-Primary 1", gender: "Male", name: 'Ryan Nam', id: "S284756489", checkbox: "" },
            { status: "Transferred", grade: "Pre-Primary 1", gender: "Female", name: 'Thalia Nate', id: "S874569854", checkbox: "" } 
        ];
    }

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
}
