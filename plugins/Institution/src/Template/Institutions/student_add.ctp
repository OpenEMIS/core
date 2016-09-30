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
            <li data-step="1" class="active" data-name="internalSearch">
                <div class="step-wrapper">
                    Internal Search
                    <span class="chevron"></span>
                </div>
            </li>

            <li data-step="2" data-name="externalSearch" ng-show="<?= $externalDataSource ?>">
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
                    <input type="hidden" ng-model="InstitutionStudentController.hasExternalDataSource" ng-init="InstitutionStudentController.hasExternalDataSource = <?php if ($externalDataSource) echo 'true'; else echo 'false'; ?>"/>
                    <span class="chevron"></span>
                </div>
            </li>
        </ul>
    </div>
    <div class="actions top">
        <button
            ng-if="(InstitutionStudentController.rowsThisPage.length===0 && !InstitutionStudentController.initialLoad) && InstitutionStudentController.step!='create_user' && ((InstitutionStudentController.step=='external_search' && InstitutionStudentController.externalDataLoaded) || (InstitutionStudentController.step=='internal_search' && !InstitutionStudentController.hasExternalDataSource))"
            ng-click="InstitutionStudentController.onAddNewStudentClick()"
            type="button" class="btn btn-default"><?= __('Create New Student') ?>
        </button>
        <button
            type="button" class="btn btn-default" ng-click="InstitutionStudentController.onExternalSearchClick()" ng-if="(InstitutionStudentController.hasExternalDataSource && InstitutionStudentController.showExternalSearchButton && InstitutionStudentController.step=='internal_search')" ng-disabled="InstitutionStudentController.selectedStudent"><?= __('External Search') ?>
        </button>
        <button
            ng-if="InstitutionStudentController.rowsThisPage.length > 0 && (InstitutionStudentController.step=='internal_search' || InstitutionStudentController.step=='external_search')"
            ng-model="InstitutionStudentController.selectedStudent"
            ng-click="InstitutionStudentController.onAddStudentClick()"
            ng-disabled="!InstitutionStudentController.selectedStudent"
            type="button" class="btn btn-default">Add Student
        </button>
        <button type="button" class="btn btn-default btn-next"
            ng-model="InstitutionStudentController.selectedStudent"
            ng-disabled="InstitutionStudentController.completeDisabled"
            ng-show="InstitutionStudentController.step=='add_student' || InstitutionStudentController.step=='create_user'"
            data-last="Complete">
            Next
        </button>
    </div>
    <div class="step-content">
        <div class="step-pane sample-pane active" data-step="1" data-name="internalSearch">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label>Filter</label>
                </div>
                <div class="text">
                    <label><?= __('OpenEMIS ID')?></label>
                    <input ng-model="InstitutionStudentController.internalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? InstitutionStudentController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('First Name') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterFirstName" ng-keyup="$event.keyCode == 13 ? InstitutionStudentController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Last Name') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterLastName" ng-keyup="$event.keyCode == 13 ? InstitutionStudentController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('{{ InstitutionStudentController.defaultIdentityTypeName }}') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? InstitutionStudentController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label for="Students_date_of_birth"><?= __('Date of Birth') ?></label>
                    <div class="input-group date " id="Students_date_of_birth" style="">
                        <input type="text" class="form-control " name="Students[date_of_birth]" ng-model="InstitutionStudentController.internalFilterDateOfBirth" ng-keyup="$event.keyCode == 13 ? InstitutionStudentController.reloadInternalDatasource(true) : null">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="InstitutionStudentController.reloadInternalDatasource(true)">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="InstitutionStudentController.clearInternalSearchFilters()" type="reset" value="Clear">Clear</button>
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
        <div class="step-pane sample-pane active" data-step="2" data-name="externalSearch">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label>Filter</label>
                </div>
                <div class="text">
                    <label><?= __('OpenEMIS ID')?></label>
                    <input ng-model="InstitutionStudentController.internalFilterOpenemisNo" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('First Name') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterFirstName" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Last Name') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterLastName" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('{{ InstitutionStudentController.defaultIdentityTypeName }}') ?></label>
                    <input ng-model="InstitutionStudentController.internalFilterIdentityNumber" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label for="Students_date_of_birth"><?= __('Date of Birth') ?></label>
                        <input type="text" class="form-control " name="Students[date_of_birth]" ng-model="InstitutionStudentController.internalFilterDateOfBirth" ng-disabled="true">
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
                    <label><?= __('OpenEMIS ID') ?></label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.openemis_no" type="string" ng-disabled="true">
                    <div ng-if="InstitutionStudentController.postResponse.error.openemis_no" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.openemis_no">{{ error }}</p>
                    </div>
                </div>
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
                    <label for="Student_date_of_birth">Date of Birth</label>
                    <div class="input-group date " id="Student_date_of_birth" style="">
                        <input type="text" class="form-control " name="Student[date_of_birth]" ng-model="InstitutionStudentController.selectedStudentData.date_of_birth" ng-init="InstitutionStudentController.selectedStudentData.date_of_birth='';">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.date_of_birth" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.date_of_birth">{{ error }}</p>
                    </div>
                </div>
            </form>
        </div>
        <div class="step-pane sample-pane" data-step="4" data-name="addStudent">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post" >
                <div class="input string required">
                    <label><?= __('OpenEMIS ID') ?></label>
                    <input ng-model="InstitutionStudentController.selectedStudentData.openemis_no" type="string" ng-disabled="true">
                    <div ng-if="InstitutionStudentController.postResponse.error.openemis_no" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.openemis_no">{{ error }}</p>
                    </div>
                </div>
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
                <div class="input select required" ng-model="InstitutionStudentController.postResponse" ng-show="!InstitutionStudentController.completeDisabled">
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
                <div class="input select required error" ng-model="InstitutionStudentController.postResponse" ng-show="!InstitutionStudentController.completeDisabled">
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
                <div class="input select" ng-show="!InstitutionStudentController.completeDisabled">
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
                <div class="input string" ng-show="!InstitutionStudentController.completeDisabled">
                    <label>Student Status</label>
                    <input type="string" value="Enrolled" disabled="disabled">
                </div>


                <div class="input date required" ng-show="!InstitutionStudentController.completeDisabled">
                    <label for="Students_start_date">Start Date</label>
                    <div class="input-group date " id="Students_start_date" style="">
                        <input type="text" class="form-control " name="Students[start_date]" ng-model="InstitutionStudentController.startDate">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStudentController.postResponse.error.start_date" class="error-message">
                        <p ng-repeat="error in InstitutionStudentController.postResponse.error.start_date">{{ error }}</p>
                    </div>
                </div>


                <div class="input text required" ng-show="!InstitutionStudentController.completeDisabled">
                    <label for="students-end-date">End Date</label>
                    <input ng-model="InstitutionStudentController.endDateFormatted" type="text" disabled="disabled">
                </div>
                <div class="input string" ng-show="InstitutionStudentController.completeDisabled">
                    <label><?= __('Institution') ?></label>
                    <input type="string" ng-model="InstitutionStudentController['selectedStudentData']['institution_students'][0]['institution']['name']" disabled="disabled">
                </div>

                <div class="input string" ng-show="InstitutionStudentController.completeDisabled">
                    <label><?= __('Contact Name') ?></label>
                    <input type="string" ng-model="InstitutionStudentController['selectedStudentData']['institution_students'][0]['institution']['contact_person']" disabled="disabled">
                </div>

                <div class="input string" ng-show="InstitutionStudentController.completeDisabled">
                    <label><?= __('Contact Information') ?></label>
                    <input type="string" ng-model="InstitutionStudentController['selectedStudentData']['institution_students'][0]['institution']['telephone']" disabled="disabled">
                </div>
            </form>
        </div>
    </div>
    <div class="actions bottom">
    </div>
</div>

<script>
$(function () {
var datepicker0 = $('#Students_start_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
var datepicker1 = $('#Students_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
var datepicker2 = $('#Student_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
$( document ).on('DOMMouseScroll mousewheel scroll', function(){
    window.clearTimeout( t );
    t = window.setTimeout( function(){
        datepicker0.datepicker('place');
        datepicker1.datepicker('place');
        datepicker2.datepicker('place');
    });
});
});

//]]>
</script>


<?php
$this->end();
?>