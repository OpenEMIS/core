// Multi Select v.1.0.0
agGrid.initialiseAgGridWithAngular1(angular);

angular.module('assessment.item.exemptions.ctrl', [
    'agGrid',
    'kd-angular-multi-select',
    'utils.svc',
    'alert.svc',
    'aggrid.locale.svc',
    'assessment.item.exemptions.svc',
    'angular.chosen'
])
    .controller('AssessmentItemExemptionsCtrl',
        AssessmentItemExemptionsController);

AssessmentItemExemptionsController.$inject = [
    '$scope',
    '$q',
    '$window',
    '$http',
    'UtilsSvc',
    'AlertSvc',
    'AggridLocaleSvc',
    'AssessmentItemExemptionsSvc',
];

function AssessmentItemExemptionsController(
    $scope,
    $q,
    $window,
    $http,
    UtilsSvc,
    AlertSvc,
    AggridLocaleSvc,
    AssessmentItemExemptionsSvc
) {
    const ctrl = this;

    // Constants
    const SUPPRESS_MENU = true;
    const SUPPRESS_SORTING = true;
    const DEFAULT_COLUMN_WIDTH = 200;
    const CHECKBOX_WIDTH = 50;
    ctrl.dataReady = false;
    // Initialization
    ctrl.assessment_period_id = []; //POCOR-9114
    ctrl.assessment_item_id = null;
    ctrl.assessment_item_ids = null;
    ctrl.institution_class_id = null;
    ctrl.selected_item_id = null;
    ctrl.selected_items_ids = [];
    ctrl.classification = null;
    ctrl.bodyDir = getComputedStyle(document.body).direction;
    ctrl.columnTopData = createColumnData();
    ctrl.columnBottomData = createColumnData();
    ctrl.rowTopData = [];
    ctrl.rowBottomData = [];
    ctrl.gridOptionsTop = createGridOptions();
    ctrl.gridOptionsBottom = createGridOptions();
    ctrl.colDef = getColumnDefinitions();
    ctrl.topKey = 'id';
    ctrl.bottomKey = 'id';
    //POCOR-9042 starts
    ctrl.excempttype = [
        { id: 1, name: 'Exempt Students' },
        { id: 2, name: 'Unassign Students' }
    ];
    ctrl.excempttype_id = 0;//POCOR-9042 ends
    ctrl.savedUk = false; //POCOR-9197 start
    ctrl.backUrl = "";
    ctrl.alertUrl = "";
    ctrl.actionEnabled = false;
    ctrl.saveEnabled = false;
    ctrl.savedOk = false; //POCOR-9197 end
    // Event Handlers
    ctrl.onSubjectChange = onSubjectChange;
    ctrl.onPeriodChange = onPeriodChange;
    ctrl.onExcemptTypeChange = onExcemptTypeChange;//POCOR-9042
    //POCOR-9042 starts
    ctrl.isAllSelected = function () {
        return ctrl.assessment_item_id && ctrl.assessment_period_id && ctrl.excempttype_id;
    };//POCOR-9042 ends
    ctrl.textConfig = {
        topCheckboxLabel: "Students",
        topSearchPlaceholder: "Search Students",
        topToBottomButton: "Exempt",
        bottomToTopButton: "Unexempt",
        bottomCheckboxLabel: "Students",
        bottomSearchPlaceholder: "Search Students"
    };
    angular.element(document).ready(initPage);

    function initPage() {
        AssessmentItemExemptionsSvc.init(angular.baseUrl);
        UtilsSvc.isAppendLoader(true);

        if (ctrl.institution_class_id &&
            ctrl.assessment_item_id &&
            ctrl.assessment_period_id) {
            loadClassDetails();
            // console.log('init');
        }
        UtilsSvc.isAppendLoader(false);

    }

    function getTeacherOptions() {
        var promise = InstitutionAssociationsSvc.getTeacherOptions(
            Controller.institutionId,
            Controller.academicPeriodId
        );
        return promise.then(function (result) {
            return result;
        });
    }

    function createColumnData() {
        return [{
            headerName: "+",
            field: "checkbox",
            checkboxSelection: true,
            suppressMenu: SUPPRESS_MENU,
            suppressSorting: SUPPRESS_SORTING,
            minWidth: CHECKBOX_WIDTH,
            maxWidth: CHECKBOX_WIDTH,
            pinned: 'left'
        }];
    }

    function createGridOptions() {
        return {
            columnDefs: [],
            rowData: [],
            primaryKey: []
        };
    }

    function getColumnDefinitions() {
        return [
            {headerName: 'OpenEMIS ID', field: 'openemis_no'},
            {headerName: 'Name', field: 'name'},
            {headerName: 'Gender', field: 'gender_name'},
            {headerName: 'Student Status', field: 'student_status_name'},
        ];
    }

    function onSubjectChange() {
        UtilsSvc.isAppendLoader(true);
        updateAssessmentItemIds();
        // console.log(ctrl);
        if (ctrl.institution_class_id &&
            ctrl.assessment_item_id &&
            ctrl.assessment_period_id) {

            // loadClassDetails();//POCOR-9042
            // console.log('init');
        }
        UtilsSvc.isAppendLoader(false);
    }

    function onPeriodChange() {
        UtilsSvc.isAppendLoader(true);
        // console.log(ctrl);
        if (ctrl.institution_class_id &&
            ctrl.assessment_item_id &&
            ctrl.assessment_period_id) {
            ctrl.actionEnabled = true; //POCOR-9197
            // loadClassDetails();//POCOR-9042
            // console.log(ctrl);
        } else {
            ctrl.actionEnabled = false;
            ctrl.saveEnabled = false;
        }
        UtilsSvc.isAppendLoader(false);

    }
    //POCOR-9042 starts
    function onExcemptTypeChange() {
        if (ctrl.assessment_period_id) { //POCOR-9197 start
            ctrl.saveEnabled = true;
        }else{
            ctrl.saveEnabled = false;
        } //POCOR-9197 end
        UtilsSvc.isAppendLoader(true);
        if(ctrl.excempttype_id == 1){
            ctrl.textConfig.topToBottomButton= "Exempt";
            ctrl.textConfig.bottomToTopButton= "Unexempt";
        }else{
            ctrl.textConfig.topToBottomButton= "Unassign";
            ctrl.textConfig.bottomToTopButton= "Assign";
        }
        // console.log(ctrl);
        if (ctrl.institution_class_id &&
            ctrl.assessment_item_id &&
            ctrl.assessment_period_id && ctrl.excempttype_id != 0) {

            loadClassDetails();
            // console.log('init');
        }
        UtilsSvc.isAppendLoader(false);
    }//POCOR-9042 ends

    function loadClassDetails() {
        // console.log(options);
        ctrl.message = '';
        ctrl.messageClass = '';
        if (!Array.isArray(ctrl.assessment_period_id)) {
            ctrl.assessment_period_id = [ctrl.assessment_period_id];  // POCOR-9114 Convert to array if it's a single value
        }
        const options = {
            institution_class_id: ctrl.institution_class_id,
            assessment_item_ids: ctrl.assessment_item_ids,
            assessment_item_id: ctrl.assessment_item_id,
            assessment_period_id: ctrl.assessment_period_id,
            classification: ctrl.classification
        }
        // console.log(options);
        //     .then(loadAdditionalClassData)
        //     .then(processAdditionalClassData)
        if (Array.isArray(options.assessment_item_ids) && options.assessment_item_ids.length > 0) {
            options.assessment_item_id = options.assessment_item_ids[0];
            delete options.assessment_item_ids;
        }
        ctrl.dataReady = false;
        // console.log(options);
        AssessmentItemExemptionsSvc.getExemptStudents(options)
            .then(setClassDetails)
            // .then(translateColumnHeaders)
            .then(() => {
                // translatedText.forEach((text, index) => {
                //     ctrl.colDef[index].headerName = text;
                // });
                ctrl.setTop(ctrl.colDef, ctrl.unexemptStudents);
                //POCOR-9042 starts
                if(ctrl.excempttype_id == 1){
                    ctrl.setBottom(ctrl.colDef, ctrl.exemptStudents);
                } else if(ctrl.excempttype_id == 2){
                    ctrl.setBottom(ctrl.colDef, ctrl.unassingStudents);
                }else{
                    ctrl.setBottom(ctrl.colDef, ctrl.exemptStudents);
                }
                //POCOR-9042 ends
            })
            .catch(handleError)
            .finally(() => {
                // console.log('Finally block executed');
                ctrl.dataReady = true;
                UtilsSvc.isAppendLoader(false);
            });
    }
    //POCOR-9042 starts
    ctrl.checkAndLoadStudents = function () {
        if (ctrl.isAllSelected()) {
            ctrl.onSubjectChange(); // or a more specific `loadStudents()` function
        }
    };//POCOR-9042 ends

    function setClassDetails(response) {

        ctrl.exemptStudents = [];
        ctrl.unexemptStudents = [];
        ctrl.unassingStudents = [];//POCOR-9042

        response.forEach(student => {
            // console.log(student);
            const studentData = {
                openemis_no: student.openemis_no,
                name: student.name,
                gender_name: student.gender,
                student_id: student.student_id,
                student_status_name: student.student_status_name,
                encodedVar: {
                    s_id: student.student_id,
                    eg_id: student.education_grade_id
                }
            };

            // Check if the student is exempt or not
            //POCOR-9042 starts
            if ((student.type == '1')) {
                ctrl.exemptStudents.push(studentData);  // Add to exempt students
            } else if ((student.type == '2')) {
                ctrl.unassingStudents.push(studentData);  // Add to unassign students
            } else {
                ctrl.unexemptStudents.push(studentData);  // Add to unexempt students
            }//POCOR-9042 ends
        });
    }

    function translateColumnHeaders() {
        const toTranslate = ctrl.colDef.map(col => col.headerName);
        const tr = AssessmentItemExemptionsSvc.translate(toTranslate);
        // console.log(toTranslate)
        // console.log(tr)
        return tr;
    }

    function handleError(error) {

        console.error(error);
    }

    ctrl.setTop = function (header, content, key = 'name') {
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = SUPPRESS_MENU;
            header[i].filter = 'text';
            header[i].width = DEFAULT_COLUMN_WIDTH;
            header[i].minWidth = DEFAULT_COLUMN_WIDTH;
            ctrl.columnTopData.push(header[i]);
        }
        if (ctrl.bodyDir != 'ltr') {
            ctrl.columnTopData.reverse();
        }
        for (var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = '';
            }
        }
        ctrl.rowTopData = content;
        ctrl.topKey = key;
        ctrl.gridOptionsTop.columnDefs = ctrl.columnTopData;
        ctrl.gridOptionsTop.rowData = ctrl.rowTopData;
        ctrl.gridOptionsTop.primaryKey = ctrl.topKey;
        // console.log(ctrl.gridOptionsTop);

    };

    ctrl.setBottom = function (header, content, key = 'name') {
        for (var i = 0; i < header.length; i++) {
            header[i].suppressMenu = SUPPRESS_MENU;
            header[i].filter = 'text';
            header[i].width = DEFAULT_COLUMN_WIDTH;
            header[i].minWidth = DEFAULT_COLUMN_WIDTH;
            ctrl.columnBottomData.push(header[i]);
        }
        if (ctrl.bodyDir != 'ltr') {
            ctrl.columnBottomData.reverse();
        }

        for (var i = 0; i < content.length; i++) {
            if (content[i].checkbox == undefined) {
                content[i].checkbox = '';
            }
        }
        ctrl.rowBottomData = content;
        ctrl.bottomKey = key;
        ctrl.gridOptionsBottom.columnDefs = ctrl.columnBottomData;
        ctrl.gridOptionsBottom.rowData = ctrl.rowBottomData;
        ctrl.gridOptionsBottom.primaryKey = ctrl.bottomKey;
        // console.log(ctrl.gridOptionsBottom);

    };

    function configureGrid(header, content, key, gridOptions, columnData) {
        header.forEach(col => {
            col.suppressMenu = SUPPRESS_MENU;
            col.filter = 'text';
            col.width = DEFAULT_COLUMN_WIDTH;
            col.minWidth = DEFAULT_COLUMN_WIDTH;
            columnData.push(col);
        });

        if (ctrl.bodyDir !== 'ltr') {
            columnData.reverse();
        }

        content.forEach(row => {
            row.checkbox = row.checkbox || '';
        });

        gridOptions.columnDefs = columnData;
        gridOptions.rowData = content;
        gridOptions.primaryKey = key;
        gridOptions.rowData;
    }

    ctrl.postForm = function () {
        if (!Array.isArray(ctrl.assessment_period_id)) {
            ctrl.assessment_period_id = [ctrl.assessment_period_id];  // POCOR-9114 Convert to array if it's a single value
        }
        //POCOR-9042 starts
        // Validate required fields
        if (
            !ctrl.assessment_item_id ||
            !ctrl.assessment_period_id ||
            !ctrl.excempttype_id || // assuming this field is needed too
            !ctrl.institution_class_id
        ) {
            AlertSvc.warning(ctrl, 'Please fill in all required fields before submitting.');
            ctrl.message = 'Form submission failed: some required fields are missing.';
            ctrl.messageClass = 'alert-warning';
            return;
        }//POCOR-9042 ends

        const exemptStudents = ctrl.gridOptionsBottom.rowData.map(row => row.encodedVar);
        const exempt_students = UtilsSvc.urlsafeBase64Encode(JSON.stringify(exemptStudents));
        const unexemptStudents = ctrl.gridOptionsTop.rowData.map(row => row.encodedVar);
        const unexempt_students = UtilsSvc.urlsafeBase64Encode(JSON.stringify(unexemptStudents));
        const postData = {
            classification:ctrl.classification,
            assessment_item_id:ctrl.assessment_item_id,
            assessment_item_ids:ctrl.assessment_item_ids,
            assessment_period_id: ctrl.assessment_period_id,
            institution_class_id: ctrl.institution_class_id,
            type: ctrl.excempttype_id,//POCOR-9042
            exempt_students: exempt_students,
            unexempt_students: unexempt_students,
        };
        UtilsSvc.isAppendLoader(true);
            AssessmentItemExemptionsSvc.saveStudents(postData)
                .then(handlePostSuccess)
                .catch(handleError)
                .finally(function(){
                    UtilsSvc.isAppendLoader(false);
                    if (ctrl.savedOk) { //POCOR-9197 start
                        ctrl.alertUrl = ctrl.updateQueryStringParameter(ctrl.alertUrl, 'alertType', 'success');
                        ctrl.alertUrl = ctrl.updateQueryStringParameter(ctrl.alertUrl, 'message', 'general.edit.success');
                        var queryString1 = localStorage.getItem('queryString1');
                        var queryString2 = localStorage.getItem('queryString2');
                        $http.get(ctrl.alertUrl)
                            .then(function(response) {
                                //$window.location.href = Controller.redirectUrl;
                                console.log(ctrl.alertUrl);
                                $window.location.href = ctrl.backUrl;
                            });

                    } //POCOR-9197 end
                });

    };

    function handlePostSuccess(response) {

        if (response.data.status === 'success') {
            AlertSvc.success(ctrl, 'The exemption list is updated.');
            ctrl.message = 'The information is updated.';
            ctrl.messageClass = 'alert-success';
            ctrl.savedOk = true; //POCOR-9197
        } else {
            AlertSvc.error(ctrl, 'The exemption list is not updated due to errors encountered.');
            ctrl.message = 'The record is not updated due to errors encountered.';
            ctrl.messageClass = 'alert-warning';
            console.error(response);
        }
    }

    ctrl.updateQueryStringParameter = function (uri, key, value) {
        const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        const separator = uri.includes('?') ? "&" : "?";
        return uri.match(re) ? uri.replace(re, '$1' + key + "=" + value + '$2') : uri + separator + key + "=" + value;
    };

    function updateAssessmentItemIds() {
        let selectedItem = ctrl.assessment_items[ctrl.assessment_item_id];

        // If the selected assessment_item has 'ids', get the unique IDs
        if (selectedItem && selectedItem.ids) {
            ctrl.assessment_item_ids = Array.from(new Set(selectedItem.ids)); // Unique IDs
            ctrl.classification = selectedItem.classification;
        } else {
            // If no 'ids' are present, set it to an empty array
            ctrl.assessment_item_ids = [];
            ctrl.classification = null;
        }
    }

    $scope.$watch('gridOptionsTop', function(newVal, oldVal) {
        if (newVal !== oldVal) {
            // Handle updates to gridOptionsTop
        }
    }, true); // The 'true' parameter ensures a deep watch
}
