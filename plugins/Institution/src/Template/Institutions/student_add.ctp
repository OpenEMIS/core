<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/students/institutions.students.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/students/institutions.students.ctrl', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');

$this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]);


?>
<div class="alert {{class}}" ng-hide="message == null">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
</div>
<div class="wizard" data-initialize="wizard" id="wizard">
    <div class="steps-container">
        <ul class="steps" style="margin-left: 0">
            <li data-step="1" class="active">
                <div class="step-wrapper">
                    Internal Search
                    <span class="chevron"></span>
                </div>
            </li>
            <?php 
                if ($externalDataSource) {
            ?>
            <li data-step="2">
                <div class="step-wrapper">
                    External Search
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="3" data-name="createUser" ng-show="InstitutionStudentController.createNewStudent">
                <div class="step-wrapper">
                    New Student Details
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="4" data-name="addStudent">
                <div class="step-wrapper">
                    Add Student
                    <input type="hidden" ng-model="InstitutionStudentController.hasExternalDataSource" ng-init="InstitutionStudentController.hasExternalDataSource = true"/>
                    <span class="chevron"></span>
                </div>
            </li>
            <?php 
                } else {
            ?>
            <li data-step="3" data-name="createUser" ng-show="InstitutionStudentController.createNewStudent">
                <div class="step-wrapper">
                    New Student Details
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="4" data-name="addStudent">
                <div class="step-wrapper">
                    Add Students
                    <input type="hidden" ng-model="InstitutionStudentController.hasExternalDataSource" ng-init="InstitutionStudentController.hasExternalDataSource = false"/>
                    <span class="chevron"></span>
                </div>
            </li>
            <?php 
                }
            ?>
        </ul>
    </div>
    <div class="actions top">
        <button
            ng-if="(InstitutionStudentController.rowsThisPage.length===0 && !InstitutionStudentController.initialLoad) && InstitutionStudentController.step!='create_user'"
            ng-click="onAddNewStudentClick()"
            type="button" class="btn btn-default">Add New Student
        </button>
        <button type="button" class="btn btn-default btn-next"
            ng-model="InstitutionStudentController.selectedStudent"
            ng-disabled="!InstitutionStudentController.selectedStudent && (InstitutionStudentController.externalSearch || !InstitutionStudentController.hasExternalDataSource)"
            data-last="Complete">
            Next
        </button>
    </div>
    <div class="step-content">
        <div class="step-pane sample-pane active" data-step="1">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label>Filter</label>
                </div>
                <div class="text">
                    <label>Openemis No.</label>
                    <input ng-model="InstitutionStudentController.internalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>First Name</label>
                    <input ng-model="InstitutionStudentController.internalFilterFirstName" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>Last Name</label>
                    <input ng-model="InstitutionStudentController.internalFilterLastName" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>{{ InstitutionStudentController.defaultIdentityTypeName }}</label>
                    <input ng-model="InstitutionStudentController.internalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="reloadInternalDatasource(true)">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="clearInternalSearchFilters()" type="reset" value="Clear">Clear</button>
                </div>
            </div>

            <div class="table-wrapper">
                <div ng-init="institution_id=<?= $institutionId; ?>">
                    <div class="scrolltabs sticky-content">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="InstitutionStudentController.internalGridOptions" ag-grid="InstitutionStudentController.internalGridOptions" class="sg-theme"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-pane sample-pane active" data-step="2">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label>Filter</label>
                </div>
                <div class="text">
                    <label>Openemis No.</label>
                    <input ng-model="InstitutionStudentController.externalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>First Name</label>
                    <input ng-model="InstitutionStudentController.externalFilterFirstName" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>Last Name</label>
                    <input ng-model="InstitutionStudentController.externalFilterLastName" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>{{ InstitutionStudentController.defaultIdentityTypeName }}</label>
                    <input ng-model="InstitutionStudentController.externalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="reloadExternalDatasource(true)">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="clearExternalSearchFilters()" type="reset" value="Clear">Clear</button>
                </div>
            </div>

            <div class="table-wrapper">
                <div ng-init="institution_id=<?= $institutionId; ?>">
                    <div class="scrolltabs sticky-content">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="InstitutionStudentController.externalGridOptions" ag-grid="InstitutionStudentController.externalGridOptions" class="sg-theme"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-pane sample-pane" data-step="3" data-name="createUser">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="input string required">
                    <label>First Name</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.first_name" ng-change="InstitutionStudentController.setStudentName()" type="string" ng-init="InstitutionStudentController.selectedStudentData.first_name='';">
                    <div ng-if="InstitutionStudentController.postResponse.error.first_name" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.first_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label>Middle Name</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.middle_name" ng-change="InstitutionStudentController.setStudentName()" type="string">
                </div>
                <div class="input string">
                    <label>Third Name</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.third_name" ng-change="InstitutionStudentController.setStudentName()" type="string">
                </div>
                <div class="input string required">
                    <label>Last Name</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.last_name" ng-change="InstitutionStudentController.setStudentName()" type="string" ng-init="InstitutionStudentController.selectedStudentData.last_name='';">
                    <div ng-if="InstitutionStudentController.postResponse.error.last_name" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.last_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label>Preferred Name</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.preferred_name" type="string">
                </div>
                <div class="input select required error">
                    <label>Gender</label>
                    <div class="input-select-wrapper">
                        <select name="Students[gender_id]" id="students-gender_id"
                            ng-options="option.id as option.name for option in InstitutionStudentController.genderOptions"
                            ng-model="InstitutionStudentController.selectedStudentData.gender_id"
                            ng-change="InstitutionStudentController.changeGender()"
                            ng-init="InstitutionStudentController.selectedStudentData.gender_id='';"
                            >
                            <option value="" >-- Select --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.gender_id" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.gender_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input date required">
                    <label for="Students_date_of_birth">Date of Birth</label>
                    <div class="input-group date " id="Students_date_of_birth" style="">
                        <input type="text" class="form-control " name="Students[date_of_birth]" ng-model="InstitutionStudentController.selectedStudentData.date_of_birth" ng-init="InstitutionStudentController.selectedStudentData.date_of_birth='';">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.date_of_birth" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.date_of_birth">{{ error }}</p>
                    </div>
                </div>
            </form>
        </div>
        <div class="step-pane sample-pane" data-step="4" data-name="addStudent">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="input string" ng-model="InstitutionStudentController.postResponse">
                    <label>Student</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.name" type="string" disabled="disabled">
                    <div ng-if="InstitutionStudentController.postResponse.error.first_name" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.first_name">{{ error }}</p>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.last_name" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.last_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label>Identity Number</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.default_identity_type" type="string" disabled="disabled">
                </div>
                <div class="input string">
                    <label>Date of Birth</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.date_of_birth" type="string" disabled="disabled">
                    <div ng-if="InstitutionStudentController.postResponse.error.student_name" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.student_name">{{ error }}</p>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.date_of_birth" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.date_of_birth">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label>Gender</label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.gender.name" type="string" disabled="disabled">
                </div>
                <div class="input select required" ng-model="InstitutionStudentController.postResponse">
                    <label>Academic Period</label>
                    <div class="input-select-wrapper">
                        <select name="Students[academic_period_id]" id="students-academic-period-id"
                            ng-options="option.name for option in InstitutionStudentController.academicPeriodOptions.availableOptions track by option.id"
                            ng-model="InstitutionStudentController.academicPeriodOptions.selectedOption"
                            ng-change="InstitutionStudentController.onChangeAcademicPeriod()"
                            >
                        </select>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.academic_period_id" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.academic_period_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select required error" ng-model="InstitutionStudentController.postResponse">
                    <label>Education Grade</label>
                    <div class="input-select-wrapper">
                        <select name="Students[education_grade_id]" id="students-education-grade-id"
                            ng-options="option.education_grade.name for option in InstitutionStudentController.educationGradeOptions.availableOptions track by option.id"
                            ng-model="InstitutionStudentController.educationGradeOptions.selectedOption"
                            ng-change="InstitutionStudentController.onChangeEducationGrade()"
                            >
                            <option value="" >-- Select --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.education_grade_id" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.education_grade_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select" >
                    <label>Class</label>
                    <div class="input-select-wrapper">
                        <select name="Students[class]" id="students-class"
                            ng-options="option.name for option in InstitutionStudentController.classOptions.availableOptions track by option.id"
                            ng-model="InstitutionStudentController.classOptions.selectedOption"
                            ng-change="InstitutionStudentController.onChangeClass()"
                            >
                            <option value="" >-- No Class Assignment --</option>
                        </select>
                    </div>
                </div>
                <div class="input string">
                    <label>Student Status</label>
                    <input type="string" value="Enrolled" disabled="disabled">
                </div>


                <div class="input date required">
                    <label for="Students_start_date">Start Date</label>
                    <div class="input-group date " id="Students_start_date" style="">
                        <input type="text" class="form-control " name="Students[start_date]" ng-model="InstitutionStudentController.startDate">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.start_date" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.start_date">{{ error }}</p>
                    </div>
                </div>


                <div class="input text required">
                    <label for="students-end-date">End Date</label>
                    <input ng-model="InstitutionStudentController.endDateFormatted" type="text" disabled="disabled">
                </div>
            </form>
        </div>
    </div>
    <div class="actions bottom">
        <button type="button"
            ng-model="InstitutionStudentController.selectedStudent"
            class="btn btn-default btn-next"
            ng-disabled="!InstitutionStudentController.selectedStudent && (InstitutionStudentController.externalSearch || !InstitutionStudentController.hasExternalDataSource)"
            >
            Next
        </button>
    </div>
</div>

<script>
$(function () {
var datepicker0 = $('#Students_start_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
var datepicker1 = $('#Students_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
$( document ).on('DOMMouseScroll mousewheel scroll', function(){
    window.clearTimeout( t );
    t = window.setTimeout( function(){
        datepicker0.datepicker('place');
        datepicker1.datepicker('place');
    });
});
});

//]]>
</script>


<?php
$this->end();
?>