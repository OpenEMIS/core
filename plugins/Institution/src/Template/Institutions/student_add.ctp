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
            <li data-step="3">
                <div class="step-wrapper">
                    Add Students
                    <span class="chevron"></span>
                </div>
            </li>
            <?php 
                } else {
            ?>
            <li data-step="2">
                <div class="step-wrapper">
                    Add Students
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
            ng-if="(!rowsThisPage && !initialLoad)"
            ng-click="onAddNewStudentClick()"
            type="button" class="btn btn-default">Add New Student
        </button>
        <button type="button" class="btn btn-default btn-prev" disabled="disabled">Previous</button>
        <button type="button" class="btn btn-default btn-next"
            ng-model="selectedStudent"
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
                    <input ng-model="internalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>First Name</label>
                    <input ng-model="internalFilterFirstName" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>Last Name</label>
                    <input ng-model="internalFilterLastName" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>{{ defaultIdentityTypeName }}</label>
                    <input ng-model="internalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? reloadInternalDatasource() : null" type="text" id="" maxlength="150">
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="reloadInternalDatasource()">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="clearInternalSearchFilters()" type="reset" value="Clear">Clear</button>
                </div>
            </div>

            <div class="table-wrapper">
                <div ng-init="institution_id=<?= $institutionId; ?>">
                    <div class="scrolltabs sticky-content">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="internalGridOptions" ag-grid="internalGridOptions" class="ag-fresh ag-height-fixed"></div>
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
                    <input ng-model="externalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>First Name</label>
                    <input ng-model="externalFilterFirstName" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>Last Name</label>
                    <input ng-model="externalFilterLastName" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource() : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label>{{ defaultIdentityTypeName }}</label>
                    <input ng-model="externalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? reloadExternalDatasource() : null" type="text" id="" maxlength="150">
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="reloadExternalDatasource()">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="clearExternalSearchFilters()" type="reset" value="Clear">Clear</button>
                </div>
            </div>

            <div class="table-wrapper">
                <div ng-init="institution_id=<?= $institutionId; ?>">
                    <div class="scrolltabs sticky-content">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="externalGridOptions" ag-grid="externalGridOptions" class="ag-fresh ag-height-fixed"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-pane sample-pane" data-step="3">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="input string" ng-model="postResponse">
                    <label>Student</label>
                    <input ng-model="selectedStudentData.name" type="string" disabled="disabled">
                    <div ng-if="postResponse.error.student_name" class="error-message">
                        <p ng-repeat="error in postResponse.error.student_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label>Identity Number</label>
                    <input ng-model="selectedStudentData.default_identity_type" type="string" disabled="disabled">
                </div>
                <div class="input string">
                    <label>Date of Birth</label>
                    <input ng-model="selectedStudentData.date_of_birth" type="string" disabled="disabled">
                </div>
                <div class="input string">
                    <label>Gender</label>
                    <input ng-model="selectedStudentData.gender.name" type="string" disabled="disabled">
                </div>
                <div class="input select required" ng-model="postResponse">
                    <label>AcademicPeriod</label>
                    <div class="input-select-wrapper">
                        <select name="Students[academic_period_id]" id="students-academic-period-id"
                            ng-options="option.name for option in academicPeriodOptions.availableOptions track by option.id"
                            ng-model="academicPeriodOptions.selectedOption"
                            ng-change="onChangeAcademicPeriod()"
                            >
                        </select>
                    </div>
                    <div ng-if="postResponse.error.academic_period_id" class="error-message">
                        <p ng-repeat="error in postResponse.error.academic_period_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select required error" ng-model="postResponse">
                    <label>Education Grade</label>
                    <div class="input-select-wrapper">
                        <select name="Students[education_grade_id]" id="students-education-grade-id"
                            ng-options="option.education_grade.name for option in educationGradeOptions.availableOptions track by option.id"
                            ng-model="educationGradeOptions.selectedOption"
                            ng-change="onChangeEducationGrade()"
                            >
                            <option value="" >-- Select --</option>
                        </select>
                    </div>
                    <div ng-if="postResponse.error.education_grade_id" class="error-message">
                        <p ng-repeat="error in postResponse.error.education_grade_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select" >
                    <label>Class</label>
                    <div class="input-select-wrapper">
                        <select name="Students[class]" id="students-class"
                            ng-options="option.name for option in classOptions.availableOptions track by option.id"
                            ng-model="classOptions.selectedOption"
                            ng-change="onChangeClass()"
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
                        <input type="text" class="form-control " name="Students[start_date]" ng-model="startDate">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="postResponse.error.start_date" class="error-message">
                        <p ng-repeat="error in postResponse.error.start_date">{{ error }}</p>
                    </div>
                </div>


                <div class="input text required">
                    <label for="students-end-date">End Date</label>
                    <input ng-model="endDateFormatted" type="text" disabled="disabled">
                </div>
            </form>
        </div>
    </div>
    <div class="actions bottom">
        <button type="button" class="btn btn-default btn-prev" disabled="disabled">Previous</button>
        <button type="button"
            ng-model="selectedStudent" ng-disabled="!selectedStudent"
            class="btn btn-default btn-next">
            Next
        </button>
    </div>
</div>

<script>
$(function () {
var datepicker0 = $('#Students_start_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
$( document ).on('DOMMouseScroll mousewheel scroll', function(){
window.clearTimeout( t );
t = window.setTimeout( function(){
datepicker0.datepicker('place');
});
}
);
});

//]]>
</script>


<?php
$this->end();
?>