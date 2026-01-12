// Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular
    .module('institution.departments.ctrl', [
        'agGrid',
        'kd-angular-multi-select',
        'utils.svc',
        'alert.svc',
        'aggrid.locale.svc',
        'institution.departments.svc',
        'angular.chosen'
    ])
    .controller('InstitutionDepartmentsCtrl', InstitutionDepartmentsController);

InstitutionDepartmentsController.$inject = [
    '$scope',
    '$q',
    '$window',
    '$http',
    'UtilsSvc',
    'AlertSvc',
    'AggridLocaleSvc',
    'InstitutionDepartmentsSvc'
];

function InstitutionDepartmentsController(
    $scope,
    $q,
    $window,
    $http,
    UtilsSvc,
    AlertSvc,
    AggridLocaleSvc,
    InstitutionDepartmentsSvc
) {
    const Controller = this;

    //─── Constants & initial grid state ─────────────────────────────────────────
    const suppressMenu    = true;
    const suppressSorting = true;

    Controller.dataReady     = false;
    Controller.bodyDir = getComputedStyle(document.body).direction;
    Controller.departmentId  = null;
    Controller.institutionId = null;
    Controller.managerId = null;
    Controller.departmentName   = '';
    Controller.departmentCode   = '';

    Controller.managerOptions = [];

    Controller.assignedStaff   = [];
    Controller.unassignedStaff = [];

    Controller.postError = {};

    Controller.colDef = [
        { headerName: 'OpenEMIS ID',    field: 'openemis_no'      },
        { headerName: 'Name',           field: 'name'             },
        { headerName: 'Gender',         field: 'gender_name'      },
        { headerName: 'Staff Status',   field: 'staff_status_name'},
    ];

    // Top grid (available staff)
    Controller.columnTopData = [{
        headerName: '', field: 'checkbox', checkboxSelection: true,
        suppressMenu, suppressSorting, minWidth: 50, maxWidth: 50, pinned: 'left'
    }];
    Controller.rowTopData = [];
    Controller.gridOptionsTop = {
        columnDefs: [], rowData: [], primaryKey: 'id',
        overlayNoRowsTemplate:
            '<span class="ag-custom-overlay">' +
            '<i class="fa fa-info-circle fa-lg margin-right-10"></i>' +
            'No Staff Record Found</span>'
    };

    // Bottom grid (assigned staff)
    Controller.columnBottomData = angular.copy(Controller.columnTopData);
    Controller.rowBottomData = [];
    Controller.gridOptionsBottom = {
        columnDefs: [], rowData: [], primaryKey: 'id',
        overlayNoRowsTemplate: Controller.gridOptionsTop.overlayNoRowsTemplate
    };

    // Function mapping
    Controller.postForm = postForm;
    Controller.filterStaff = filterStaff;
    Controller.updateQueryStringParameter = updateQueryStringParameter;

    //─── Initialization ─────────────────────────────────────────────────────────
    angular.element(document).ready(() => {
        InstitutionDepartmentsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        if (!Controller.departmentId) {
            UtilsSvc.isAppendLoader(false);
            return;
        }

        // Promise chain to load all data
        getDepartmentDetails()
            .then(setBasicData)
            .then(getUnassignedStaff)
            .then(setUnassignedStaff)
            .then(getManagerOptions)
            .then(setManagerOptions)
            .then(translateHeaders)
            .then(setTranslatedHeaders)
            .catch(err => {
                console.warn('Load halted:', err);
            })
            .finally(() => {
                Controller.dataReady = true;
                filterStaff();
                UtilsSvc.isAppendLoader(false);
            });

    });

    //─── Data loaders & setters ─────────────────────────────────────────────────

    function getDepartmentDetails() {
        return InstitutionDepartmentsSvc
            .getDepartmentDetails(Controller.departmentId)
            .catch(err => Promise.reject(err));
    }

    function setBasicData(response) {
        Controller.departmentName    = response.name;
        Controller.departmentCode    = response.code;
        Controller.institutionId     = response.institution_id;
        Controller.managerId         = response.manager_id;
        // Assigned staff
        if (angular.isArray(response.assigned_staff)) {
            Controller.assignedStaff = response.assigned_staff;
        }

        return;
    }

    function getUnassignedStaff() {
        return InstitutionDepartmentsSvc
            .getUnassignedStaff(
                Controller.institutionId,
                Controller.departmentId
            )
            .catch(err => Promise.reject(err));
    }

    function setUnassignedStaff(unassigned) {

        Controller.unassignedStaff = unassigned;


        return;
    }

    function getManagerOptions() {
        return InstitutionDepartmentsSvc
            .getManagerOptions(Controller.departmentId, Controller.institutionId)
            .catch(err => Promise.reject(err));
    }

    function setManagerOptions(options) {

        Controller.managerOptions = options;

    }

    //─── Header translation ─────────────────────────────────────────────────────
    function translateHeaders() {
        const labels = Controller.colDef.map(c => c.headerName);
        return InstitutionDepartmentsSvc
            .translate(labels)
            .then(translated => angular.isArray(translated) ? translated : labels)
            .catch(() => labels);
    }

    function setTranslatedHeaders(translated) {
        translated.forEach((text, i) => {
            Controller.colDef[i].headerName = text;
        });
        // Build both grids
        setTopGrid(Controller.colDef, Controller.unassignedStaff);
        setBottomGrid(Controller.colDef, Controller.assignedStaff);

    }

    //─── Grid helpers ───────────────────────────────────────────────────────────
    function setTopGrid(columns, rows) {
        Controller.columnTopData = [ Controller.columnTopData[0] ];

        columns.forEach(col => {
            col.suppressMenu = suppressMenu;
            col.filter       = 'text';
            col.width        = 200;
            col.minWidth     = 200;
            Controller.columnTopData.push(col);
        });
        if (Controller.bodyDir !== 'ltr') {
            Controller.columnTopData.reverse();
        }
        rows.forEach(r => { r.checkbox = r.checkbox || ''; });
        Controller.rowTopData = rows;
        Controller.gridOptionsTop.columnDefs = Controller.columnTopData;
        Controller.gridOptionsTop.rowData    = Controller.rowTopData;
    }

    function setBottomGrid(columns, rows) {
        Controller.columnBottomData = [ Controller.columnBottomData[0] ];
        columns.forEach(col => {
            col.suppressMenu = suppressMenu;
            col.filter       = 'text';
            col.width        = 200;
            col.minWidth     = 200;
            Controller.columnBottomData.push(col);
        });
        if (Controller.bodyDir !== 'ltr') {
            Controller.columnBottomData.reverse();
        }
        rows.forEach(r => { r.checkbox = r.checkbox || ''; });
        Controller.rowBottomData = rows;
        Controller.gridOptionsBottom.columnDefs = Controller.columnBottomData;
        Controller.gridOptionsBottom.rowData    = Controller.rowBottomData;
    }


    //─── Utility: filter out already-selected teachers ───────────────────────────
    function filterStaff() {
        const mgrId = Controller.managerId;

        const filteredAssigned   = mgrId
            ? Controller.assignedStaff.filter(s => s.security_user_id !== mgrId)
            : Controller.assignedStaff;
        const filteredUnassigned = mgrId
            ? Controller.unassignedStaff.filter(s => s.security_user_id !== mgrId)
            : Controller.unassignedStaff;

        // reset your column arrays
        setTopGrid(Controller.colDef,    filteredUnassigned);
        setBottomGrid(Controller.colDef, filteredAssigned);
        Controller.assignedStaff = filteredAssigned;
        // now push the new rows into the grids' APIs:
        if (Controller.gridOptionsTop.api) {
            Controller.gridOptionsTop.api.setRowData(filteredUnassigned);
        }
        if (Controller.gridOptionsBottom.api) {
            Controller.gridOptionsBottom.api.setRowData(filteredAssigned);
        }
    }

    //─── Save ───────────────────────────────────────────────────────────────────
    function postForm() {
        Controller.postError = {};

        if (!Controller.departmentName.trim()) {
            AlertSvc.error(Controller, 'Name is required.');
            Controller.postError.name = ['Name Is Required'];
            return;
        }
        if (!Controller.departmentCode.trim()) {
            AlertSvc.error(Controller, 'Code is required.');
            Controller.postError.name = ['Code Is Required'];
            return;
        }

        const departmentStaff = Controller.assignedStaff.map(item => ({
            encodedVar:          item.encodedVar,
        }));

        const postData = {
            id:                Controller.departmentId,
            name:              Controller.departmentName,
            code:              Controller.departmentCode,
            institution_id:    Controller.institutionId,
            manager_id:    Controller.managerId,
            assigned_staff:  departmentStaff
        };
        InstitutionDepartmentsSvc
            .updateDepartment(postData)
            .then(function(resp) {
                var errors = resp.data.error;

                // 1) empty-array → success
                if (angular.isArray(errors) && errors.length === 0) {
                        Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'alertType', 'success');
                        Controller.alertUrl = Controller.updateQueryStringParameter(Controller.alertUrl, 'message', 'general.edit.success');

                        $http.get(Controller.alertUrl)
                            .then(function(response) {
                                $window.location.href = Controller.redirectUrl;
                            });


                }

                // something went wrong—normalize and assign
                Controller.postError = normalizeErrors(errors);
                AlertSvc.error(Controller, 'Update failed.');
            })
            .catch(function(err) {
                console.error(err);
            });
    }
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }
    /**
     * Normalize CakePHP validation error payloads:
     *  - Strings → [string]
     *  - Arrays  → unchanged
     *  - Objects → collect all nested values into an array
     */
    function normalizeErrors(rawErrors) {
        var out = {};
        angular.forEach(rawErrors, function(val, field) {
            if (angular.isString(val)) {
                out[field] = [ val ];
            }
            else if (angular.isArray(val)) {
                out[field] = val;
            }
            else if (angular.isObject(val)) {
                // e.g. { ruleUnique: "[Message Not Found]" }
                var msgs = [];
                angular.forEach(val, function(innerMsg) {
                    msgs.push(innerMsg);
                });
                out[field] = msgs;
            }
            else {
                // unknown shape, stringify
                out[field] = [ String(val) ];
            }
        });
        return out;
    }
}
