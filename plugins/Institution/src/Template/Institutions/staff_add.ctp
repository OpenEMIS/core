<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/staff/institutions.staff.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/staff/institutions.staff.ctrl', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
$this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]);
?>
<div class="alert {{class}}" ng-hide="message == null">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a><?= __('{{message}}') ?>
</div>
<div class="wizard" data-initialize="wizard" id="wizard">
    <div class="steps-container">
        <ul class="steps" style="margin-left: 0">
            <li data-step="1" class="active" data-name="internalSearch">
                <div class="step-wrapper">
                    <?= __('Internal Search') ?>
                    <span class="chevron"></span>
                </div>
            </li>

            <li data-step="2" data-name="externalSearch" ng-show="InstitutionStaffController.hasExternalDataSource">
                <div class="step-wrapper">
                    <?= __('External Search') ?>
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="3" data-name="createUser" ng-show="InstitutionStaffController.createNewStudent">
                <div class="step-wrapper">
                    <?= __('New Staff Details') ?>
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="4" data-name="addStudent">
                <div class="step-wrapper">
                    <?= __('Add Staff') ?>
                    <input type="hidden" ng-model="InstitutionStaffController.hasExternalDataSource" ng-init="InstitutionStaffController.hasExternalDataSource = <?php if ($externalDataSource) echo 'true'; else echo 'false'; ?>; InstitutionStaffController.institutionId=<?= $institutionId; ?>;"/>
                    <span class="chevron"></span>
                </div>
            </li>
        </ul>
    </div>
    <div class="actions top">
        <?php if ($_createNewStaff) : ?>
        <button
            ng-if="((!InstitutionStaffController.initialLoad && !InstitutionStaffController.hasExternalDataSource)
            || (!InstitutionStaffController.initialLoad && InstitutionStaffController.step == 'external_search')
            ) && (InstitutionStaffController.step == 'external_search' || InstitutionStaffController.step == 'internal_search')"
            ng-disabled="InstitutionStaffController.selectedStudent"
            ng-click="InstitutionStaffController.onAddNewStudentClick()"
            type="button" class="btn btn-default"><?= __('Create New Staff') ?>
        </button>
        <?php endif; ?>
        <button
            type="button" class="btn btn-default" ng-click="InstitutionStaffController.onExternalSearchClick()"
            ng-if="(!InstitutionStaffController.initialLoad && InstitutionStaffController.hasExternalDataSource && InstitutionStaffController.showExternalSearchButton && InstitutionStaffController.step=='internal_search')" ng-disabled="InstitutionStaffController.selectedStudent"><?= __('External Search') ?>
        </button>
        <button
            ng-if="InstitutionStaffController.rowsThisPage.length > 0 && (InstitutionStaffController.step=='internal_search' || InstitutionStaffController.step=='external_search')"
            ng-model="InstitutionStaffController.selectedStudent"
            ng-click="InstitutionStaffController.onAddStudentClick()"
            ng-disabled="!InstitutionStaffController.selectedStudent"
            type="button" class="btn btn-default"><?= __('Add Staff') ?>
        </button>
        <button type="button" class="btn btn-default btn-next"
            ng-model="InstitutionStaffController.selectedStudent"
            ng-disabled="InstitutionStaffController.completeDisabled"
            ng-show="(InstitutionStaffController.step=='add_student' || InstitutionStaffController.step=='create_user')"
            data-last="Complete">
            <?= __('Next') ?>
        </button>
    </div>
    <div class="step-content">
        <div class="step-pane sample-pane active" data-step="1" data-name="internalSearch">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label><?= __('Filter') ?></label>
                </div>
                <div class="text">
                    <label><?= __('OpenEMIS ID') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterOpenemisNo" ng-keyup="$event.keyCode == 13 ? InstitutionStaffController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('First Name') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterFirstName" ng-keyup="$event.keyCode == 13 ? InstitutionStaffController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Last Name') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterLastName" ng-keyup="$event.keyCode == 13 ? InstitutionStaffController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Identity Number') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterIdentityNumber" ng-keyup="$event.keyCode == 13 ? InstitutionStaffController.reloadInternalDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="date">
                    <label for="Staff_date_of_birth"><?= __('Date of Birth') ?></label>
                    <div class="input-group date " id="Staff_date_of_birth" style="">
                        <input type="text" class="form-control " name="Staff[date_of_birth]" ng-model="InstitutionStaffController.internalFilterDateOfBirth" ng-keyup="$event.keyCode == 13 ? InstitutionStaffController.reloadInternalDatasource(true) : null">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                </div>

                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="InstitutionStaffController.reloadInternalDatasource(true)">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="InstitutionStaffController.clearInternalSearchFilters()" type="reset" value="Clear"><?= __('Clear') ?></button>
                </div>
            </div>

            <div class="table-wrapper">
                <div>
                    <div class="scrolltabs">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="InstitutionStaffController.internalGridOptions" ag-grid="InstitutionStaffController.internalGridOptions" class="sg-theme"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-pane sample-pane active" data-step="2" data-name="externalSearch">
            <div class="dropdown-filter">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label><?= __('Filter') ?></label>
                </div>
                <div class="text">
                    <label><?= __('First Name') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterFirstName" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Last Name') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterLastName" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?= __('Identity Number') ?></label>
                    <input ng-model="InstitutionStaffController.internalFilterIdentityNumber" ng-disabled="true" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label for="Staff_date_of_birth"><?= __('Date of Birth') ?></label>
                        <input type="text" class="form-control " name="Staff[date_of_birth]" ng-model="InstitutionStaffController.internalFilterDateOfBirth" ng-disabled="true">
                </div>
            </div>

            <div class="table-wrapper">
                <div>
                    <div class="scrolltabs sticky-content">
                        <div id="institution-student-table" class="table-wrapper">
                            <div ng-if="InstitutionStaffController.externalGridOptions" ag-grid="InstitutionStaffController.externalGridOptions" class="sg-theme"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-pane sample-pane" data-step="3" data-name="createUser">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="input string required">
                    <label><?= __('OpenEMIS ID') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.openemis_no" type="string" ng-disabled="true">
                    <div ng-if="InstitutionStaffController.postResponse.error.openemis_no" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.openemis_no">{{ error }}</p>
                    </div>
                </div>
                <div class="input string required">
                    <label><?= __('First Name') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.first_name" ng-change="InstitutionStaffController.setStudentName()" type="string" ng-init="InstitutionStaffController.selectedStudentData.first_name='';">
                    <div ng-if="InstitutionStaffController.postResponse.error.first_name" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.first_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label><?= __('Middle Name') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.middle_name" ng-change="InstitutionStaffController.setStudentName()" type="string">
                </div>
                <div class="input string">
                    <label><?= __('Third Name') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.third_name" ng-change="InstitutionStaffController.setStudentName()" type="string">
                </div>
                <div class="input string required">
                    <label><?= __('Last Name') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.last_name" ng-change="InstitutionStaffController.setStudentName()" type="string" ng-init="InstitutionStaffController.selectedStudentData.last_name='';">
                    <div ng-if="InstitutionStaffController.postResponse.error.last_name" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.last_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label><?= __('Preferred Name') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.preferred_name" type="string">
                </div>
                <div class="input select required error">
                    <label><?= __('Gender') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[gender_id]" id="staff-gender_id"
                            ng-options="option.id as option.name for option in InstitutionStaffController.genderOptions"
                            ng-model="InstitutionStaffController.selectedStudentData.gender_id"
                            ng-change="InstitutionStaffController.changeGender()"
                            ng-init="InstitutionStaffController.selectedStudentData.gender_id='';"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.gender_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.gender_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input date required">
                    <label for="Student_date_of_birth"><?= __('Date of Birth') ?></label>
                    <div class="input-group date " id="Student_date_of_birth" style="">
                        <input type="text" class="form-control " name="Staff[date_of_birth]" ng-model="InstitutionStaffController.selectedStudentData.date_of_birth" ng-init="InstitutionStaffController.selectedStudentData.date_of_birth='';">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.date_of_birth" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.date_of_birth">{{ error }}</p>
                    </div>
                </div>
                <div ng-class="InstitutionStaffController.Student.nationality_class" ng-show="InstitutionStaffController.StudentNationalities != 2">
                    <label><?= __('Nationality') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[nationality_id]" id="staff-nationality_id"
                            ng-options="option.id as option.name for option in InstitutionStaffController.StudentNationalitiesOptions"
                            ng-model="InstitutionStaffController.Student.nationality_id"
                            ng-change="InstitutionStaffController.changeNationality()"
                            ng-init="InstitutionStaffController.Student.nationality_id='';"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.nationalities[0].nationality_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.nationalities[0].nationality_id">{{ error }}</p>
                    </div>
                </div>
                <div ng-class="InstitutionStaffController.Student.identity_type_class" ng-show="InstitutionStaffController.StudentIdentities != 2 && InstitutionStaffController.StudentNationalities == 2">
                    <label><?= __('Identity Type') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[identities_type_id]" id="staff-identities_type_id"
                            ng-options="option.id as option.name for option in InstitutionStaffController.StudentIdentitiesOptions"
                            ng-model="InstitutionStaffController.Student.identity_type_id"
                            ng-change="InstitutionStaffController.changeIdentityType()"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.identities[0].identity_type_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.identities[0].identity_type_id">{{ error }}</p>
                    </div>
                </div>
                <div ng-class="InstitutionStaffController.Student.identity_class" ng-show="InstitutionStaffController.StudentIdentities != 2">
                    <label><?= __('{{InstitutionStaffController.Student.identity_type_name}}') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.identity_number" type="string" ng-init="InstitutionStaffController.selectedStudentData.identity_number='';">
                    <div ng-if="InstitutionStaffController.postResponse.error.identities[0].number" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.identities[0].number">{{ error }}</p>
                    </div>
                </div>
            </form>
        </div>
        <div class="step-pane sample-pane" data-step="4" data-name="addStudent">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post" >
                <div class="input string required">
                    <label><?= __('OpenEMIS ID') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.openemis_no" type="string" ng-disabled="true">
                    <div ng-if="InstitutionStaffController.postResponse.error.openemis_no" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.openemis_no">{{ error }}</p>
                    </div>
                </div>
                <div class="input string" ng-model="InstitutionStaffController.postResponse">
                    <label><?= __('Staff') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.name" type="string" disabled="disabled">
                    <div ng-if="InstitutionStaffController.postResponse.error.first_name" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.first_name">{{ error }}</p>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.last_name" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.last_name">{{ error }}</p>
                    </div>
                </div>
                <div class="input string" ng-show="InstitutionStaffController.StudentNationalities != 2 && StudentController.createNewStudent == true">
                    <label><?= __('Nationality') ?></label>
                    <input ng-model="InstitutionStaffController.Student.nationality_name" type="string" disabled="disabled">
                    <div ng-if="InstitutionStaffController.postResponse.error.nationalities[0].nationality_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.nationalities[0].nationality_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input string" ng-show="InstitutionStaffController.StudentIdentities != 2">
                    <label><?= __('Identity Number') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.identity_number" type="string" disabled="disabled">
                    <div ng-if="InstitutionStaffController.postResponse.error.identities[0].number" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.identities[0].number">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label><?= __('Date of Birth') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.date_of_birth" type="string" disabled="disabled">
                    <div ng-if="InstitutionStaffController.postResponse.error.student_name" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.student_name">{{ error }}</p>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.date_of_birth" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.date_of_birth">{{ error }}</p>
                    </div>
                </div>
                <div class="input string">
                    <label><?= __('Gender') ?></label>
                    <input ng-model="InstitutionStaffController.selectedStudentData.gender.name" type="string" disabled="disabled">
                </div>
                <div class="input select required" ng-model="InstitutionStaffController.postResponse" ng-show="!InstitutionStaffController.completeDisabled">
                    <label><?= __('Academic Period') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[academic_period_id]" id="staff-academic-period-id"
                            ng-options="option.name for option in InstitutionStaffController.academicPeriodOptions.availableOptions track by option.id"
                            ng-model="InstitutionStaffController.academicPeriodOptions.selectedOption"
                            ng-change="InstitutionStaffController.onChangeAcademicPeriod()"
                            >
                        </select>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.academic_period_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.academic_period_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select required error" ng-model="InstitutionStaffController.postResponse" ng-show="!InstitutionStaffController.completeDisabled">
                    <label><?= __('Education Grade') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[education_grade_id]" id="staff-education-grade-id"
                            ng-options="option.education_grade.name for option in InstitutionStaffController.educationGradeOptions.availableOptions track by option.id"
                            ng-model="InstitutionStaffController.educationGradeOptions.selectedOption"
                            ng-change="InstitutionStaffController.onChangeEducationGrade()"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.education_grade_id" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.education_grade_id">{{ error }}</p>
                    </div>
                </div>
                <div class="input select" ng-show="!InstitutionStaffController.completeDisabled">
                    <label><?= __('Class') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[class]" id="staff-class"
                            ng-options="option.name for option in InstitutionStaffController.classOptions.availableOptions track by option.id"
                            ng-model="InstitutionStaffController.classOptions.selectedOption"
                            ng-change="InstitutionStaffController.onChangeClass()"
                            >
                            <option value="" >-- <?= __('No Class Assignment') ?> --</option>
                        </select>
                    </div>
                </div>
                <div class="input string" ng-show="!InstitutionStaffController.completeDisabled">
                    <label><?= __('Staff Status') ?></label>
                    <input type="string" value="<?= __('Pending Admission') ?>" disabled="disabled">
                </div>


                <div class="input date required" ng-show="!InstitutionStaffController.completeDisabled">
                    <label for="Staff_start_date"><?= __('Start Date') ?></label>
                    <div class="input-group date " id="Staff_start_date" style="">
                        <input type="text" class="form-control " name="Staff[start_date]" ng-model="InstitutionStaffController.startDate">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                    <div ng-if="InstitutionStaffController.postResponse.error.start_date" class="error-message">
                        <p ng-repeat="error in InstitutionStaffController.postResponse.error.start_date">{{ error }}</p>
                    </div>
                </div>


                <div class="input text required" ng-show="!InstitutionStaffController.completeDisabled">
                    <label for="staff-end-date"><?= __('End Date') ?></label>
                    <input ng-model="InstitutionStaffController.endDateFormatted" type="text" disabled="disabled">
                </div>
                <div class="section-header" ng-show="InstitutionStaffController.completeDisabled"><?= __('Institution Information') ?></div>
                <div class="input string" ng-show="InstitutionStaffController.completeDisabled">
                    <label><?= __('Institution') ?></label>
                    <input type="string" ng-model="InstitutionStaffController['selectedStudentData']['institution_staff'][0]['institution']['code_name']" disabled="disabled">
                </div>

                <div class="input string" ng-show="InstitutionStaffController.completeDisabled">
                    <label><?= __('Area') ?></label>
                    <input type="string" ng-model="InstitutionStaffController['selectedStudentData']['institution_staff'][0]['institution']['area']['code_name']" disabled="disabled">
                </div>

                <div class="input string" ng-show="InstitutionStaffController.completeDisabled">
                    <label><?= __('Contact Name') ?></label>
                    <input type="string" ng-model="InstitutionStaffController['selectedStudentData']['institution_staff'][0]['institution']['contact_person']" disabled="disabled">
                </div>

                <div class="input string" ng-show="InstitutionStaffController.completeDisabled">
                    <label><?= __('Telephone') ?></label>
                    <input type="string" ng-model="InstitutionStaffController['selectedStudentData']['institution_staff'][0]['institution']['telephone']" disabled="disabled">
                </div>
            </form>
        </div>
    </div>
    <div class="actions bottom">
    </div>
</div>

<script>
$(function () {
var datepicker0 = $('#Staff_start_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
var datepicker1 = $('#Staff_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true});
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