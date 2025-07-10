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
    Controller.bodyDir       = document.body.style.direction;
    Controller.departmentId  = null;
    Controller.institutionId = null;
    Controller.academicPeriodId = null;
    Controller.departmentName   = '';
    Controller.academicPeriodName = '';

    Controller.selectedSecondaryTeachers = [];
    Controller.mainTeacherOptions        = [];
    Controller.secondaryTeacherOptions   = [];

    Controller.assignedStaff   = [];
    Controller.unassignedStaff = [];

    Controller.postError = {};

    Controller.colDef = [
        { headerName: 'OpenEMIS ID',    field: 'openemis_no'      },
        { headerName: 'Name',           field: 'name'             },
        { headerName: 'Gender',         field: 'gender_name'      },
        { headerName: 'Staff Status',   field: 'staff_status_name'}
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
    Controller.changeStaff = changeStaff;

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
            .then(getTeacherOptions)
            .then(setTeacherOptions)
            .then(translateHeaders)
            .then(setTranslatedHeaders)
            .catch(err => {
                console.warn('Load halted:', err);
            })
            .finally(() => {
                Controller.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });

        // Watch for secondary-teacher changes to update available list
        $scope.$watchCollection(
            'InstitutionDepartmentsController.selectedSecondaryTeachers',
            () => {
                Controller.secondaryTeacherOptions = changeStaff(Controller.selectedSecondaryTeachers);
            }
        );
    });

    //─── Data loaders & setters ─────────────────────────────────────────────────

    function getDepartmentDetails() {
        return InstitutionDepartmentsSvc
            .getDepartmentDetails(Controller.departmentId)
            .catch(err => Promise.reject(err));
    }

    function setBasicData(response) {
        console.log(response);
        Controller.departmentName    = response.name;
        Controller.institutionId     = response.institution_id;

        // Assigned staff
        if (angular.isArray(response.department_staff)) {
            Controller.assignedStaff = response.department_staff.map(mapAssignedStaff);
        }
        Controller.mainTeacherOptions = response.staff_options || [];
        Controller.secondaryTeacherOptions = changeStaff(Controller.selectedSecondaryTeachers);

        return response;
    }

    function getUnassignedStaff(/* response */) {
        return InstitutionDepartmentsSvc
            .getUnassignedStudent(
                Controller.departmentId,
                Controller.institutionId,
                Controller.academicPeriodId
            )
            .catch(err => Promise.reject(err));
    }

    function setUnassignedStaff(unassigned) {
        Controller.unassignedStaff = unassigned.map(mapUnassignedStaff);
        return;
    }

    function getTeacherOptions() {
        return InstitutionDepartmentsSvc
            .getTeacherOptions(Controller.institutionId, Controller.academicPeriodId)
            .catch(err => Promise.reject(err));
    }

    function setTeacherOptions(options) {
        Controller.mainTeacherOptions = options;
        Controller.secondaryTeacherOptions = changeStaff(Controller.selectedSecondaryTeachers);
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

    //─── Mappers ────────────────────────────────────────────────────────────────
    function mapAssignedStaff(item) {
        return {
            openemis_no:      item.user.openemis_no,
            name:             item.user.name,
            staff_status_name: item.staff_status.name,
            gender_name:      item.user.gender.name,
            security_user_id: item.security_group_user_id,
            encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify({
                staff_id:                  item.staff_id,
                security_user_id:          item.security_group_user_id,
                institution_department_id: item.institution_department_id,
                institution_id:            item.institution_id,
                staff_status_id:           item.staff_status_id,
                gender_id:                 item.user.gender.id
            }))
        };
    }

    function mapUnassignedStaff(item) {
        return {
            openemis_no:      item.openemis_no,
            name:             item.name,
            staff_status_name: item.staff_status_name,
            gender_name:      item.gender_name,
            security_user_id: item.security_user_id,
            encodedVar: UtilsSvc.urlsafeBase64Encode(JSON.stringify({
                security_user_id:          item.security_user_id,
                institution_department_id: item.institution_department_id,
                academic_period_id:        item.academic_period_id,
                institution_id:            item.institution_id,
                staff_status_id:           item.staff_status_id,
                gender_id:                 item.gender_id
            }))
        };
    }

    //─── Utility: filter out already-selected teachers ───────────────────────────
    function changeStaff(selected) {
        return Controller.mainTeacherOptions.filter(opt => {
            return Array.isArray(selected)
                ? !selected.includes(opt.id)
                : opt.id !== selected;
        });
    }

    //─── Save ───────────────────────────────────────────────────────────────────
    function postForm() {
        Controller.postError = {};

        if (!Controller.departmentName.trim()) {
            AlertSvc.error(Controller, 'Name is required.');
            Controller.postError.name = ['Name Is Required'];
            return;
        }

        const departmentStaff = Controller.selectedSecondaryTeachers.map(id => ({
            security_user_id:          id,
            institution_department_id: Controller.departmentId
        }));

        const postData = {
            id:                Controller.departmentId,
            name:              Controller.departmentName,
            institution_id:    Controller.institutionId,
            academic_period_id:Controller.academicPeriodId,
            department_staff:  departmentStaff
        };

        InstitutionDepartmentsSvc
            .updateDepartment(postData)
            .then(resp => {
                const errors = resp.data.error;
                if (Array.isArray(errors) && errors.length === 0) {
                    const successUrl = `${angular.baseUrl}/Institution/Institutions/Departments/view/` +
                        `${localStorage.getItem('queryString1')}/` +
                        `${localStorage.getItem('queryString2')}?alertType=success&message=general.edit.success`;
                    return $http.get(successUrl)
                        .then(() => $window.location.href = successUrl);
                }
                AlertSvc.error(Controller, 'Update failed.');
                errors.forEach((e, idx) => Controller.postError[idx] = e);
            })
            .catch(err => console.error(err));
    }
}
