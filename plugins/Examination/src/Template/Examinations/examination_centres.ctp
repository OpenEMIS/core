<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Examination.angular/centres/examination.centres.svc', ['block' => true]); ?>
<?= $this->Html->script('Examination.angular/centres/examination.centres.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
$backUrl = [
    'plugin' => $this->request->params['plugin'],
    'controller' => $this->request->params['controller'],
    'action' => 'Centres',
    'index'
];
echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false, 'ng-show' => 'action == \'view\'']);

$this->end();
$this->start('panelBody');

?>
<div class="alert {{class}}" ng-hide="message == null">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
</div>
<div class="wizard" data-initialize="wizard" id="wizard">
    <div class="steps-container">
        <ul class="steps" style="margin-left: 0">
            <li data-step="1" class="active">
                <div class="step-wrapper">
                    Examination Centre
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="2" data-name="details">
                <div class="step-wrapper">
                    Details
                    <span class="chevron"></span>
                </div>
            </li>
            <li data-step="3" data-name="confirmation">
                <div class="step-wrapper">
                    Confirmation
                    <span class="chevron"></span>
                </div>
            </li>
        </ul>
    </div>
    <div class="actions top">
        <button type="button" class="btn btn-default btn-next"
            ng-disabled=""
            data-last="Complete"
            ng-click="ExamCentreController.getSubjects()"
            >
            Next
        </button>
    </div>
    <div class="step-content">
        <div class="step-pane sample-pane active" data-step="1">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="input string required">
                    <label>Academic Period</label>
                    <div class="input-select-wrapper">
                        <select name="ExaminationCentres[academic_period_id]" id="examinationcentres-academic_period_id"
                            ng-options="option.id as option.name for option in ExamCentreController.academicPeriods"
                            ng-model="ExamCentreController.academicPeriodId"
                            ng-change="ExamCentreController.changePeriod()"
                            ng-init="ExamCentreController.academicPeriodId=null;"
                            >
                            <option value="" >-- Select --</option>
                        </select>
                    </div>
                </div>
                <div class="input string required">
                    <label>Examination</label>
                    <div class="input-select-wrapper">
                        <select name="ExaminationCentres[examination]" id="examinationcentres-examination"
                            ng-options="option.id as option.name for option in ExamCentreController.examinations"
                            ng-model="ExamCentreController.examinationId"
                            ng-init="ExamCentreController.examinationId=null;"
                            >
                            <option value="" >-- Select --</option>
                        </select>
                    </div>
                </div>
                <div class="input string required">
                    <label>Examination Centre</label>
                    <div class="input-select-wrapper">
                        <select name="ExaminationCentres[centre_type]" id="examinationcentres-centre_type"
                            ng-model="ExamCentreController.centreType"
                            ng-init="ExamCentreController.centreType=null;"
                            >
                            <option value="" >-- Select --</option>
                            <option value="Existing" ><?= __('Existing Institution') ?></option>
                            <option value="New" ><?= __('New Exam Centre') ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="step-pane sample-pane active" data-step="2">
            <div class="dropdown-filter" ng-show="ExamCentreController.centreType=='Existing'">
                <div class="filter-label">
                    <i class="fa fa-filter"></i>
                    <label>Filter</label>
                </div>
                <div class="text">
                    <label><?=__('Institution Code')?></label>
                    <input ng-model="ExamCentreController.institutionCode" ng-keyup="$event.keyCode == 13 ? ExamCentreController.reloadDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?=__('Institution Name')?></label>
                    <input ng-model="ExamCentreController.institutionName" ng-keyup="$event.keyCode == 13 ? ExamCentreController.reloadDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="text">
                    <label><?=__('Institution Type')?></label>
                    <input ng-model="ExamCentreController.institutionType" ng-keyup="$event.keyCode == 13 ? ExamCentreController.reloadDatasource(true) : null" type="text" id="" maxlength="150">
                </div>
                <div class="search-action-btn margin-top-10 margin-bottom-10">
                    <button class="btn btn-default btn-xs" ng-click="ExamCentreController.reloadDatasource(true)">Filter</button>
                    <button class="btn btn-outline btn-xs" ng-click="ExamCentreController.clearSearchFilters()" type="reset" value="Clear">Clear</button>
                </div>
            </div>

            <form method="post" accept-charset="utf-8" id="content-main-form" novalidate="novalidate" action="/openemis-phpoe/Surveys/Forms?module=1" class="ng-pristine ng-valid"><div style="display:none;"><input type="hidden" name="_method" value="POST"></div>
                    <div class="table-wrapper">
                        <div class="table-responsive">

                            <table class="table table-curved table-sortable table-checkable" >
                                <thead>
                                    <tr>
                                        <th class="checkbox-column" width="50"></th>
                                        <th><?= __('Institution')?></th>
                                        <th><?= __('Subjects')?></th>
                                        <th><?= __('Capacity')?></th>
                                        <th><?= __('Special Needs')?></th>
                                    </tr>
                                </thead>
                                    <tr ng-repeat="institution in ExamCentreController.institutionList">
                                        <td class="checkbox-column">
                                        <input
                                            type="hidden"
                                            ng-init="ExamCentreController.institutionId[institution.id] = institution.id"
                                            ng-model="ExamCentreController.institutionId[institution.id]" />
                                            <input
                                                class="no-selection-label"
                                                kd-checkbox-radio
                                                type="checkbox"
                                                ng-true-value="1"
                                                ng-false-value="0"
                                                ng-model="ExamCentreController.examCentreEnabled[institution.id]"
                                                >
                                        </td>
                                        <td>
                                            {{institution.code_name}}
                                        </td>
                                        <td>
                                            <div class="input select">
                                                <select chosen
                                                    multiple="multiple"
                                                    data-placeholder="<?=__('Select Some Options') ?>"
                                                    class="chosen-select"
                                                    options="ExamCentreController.subjects"
                                                    ng-model="ExamCentreController.examCentreSubjects[institution.id]"
                                                    ng-options="item.subject_id as item.subject_name for item in ExamCentreController.subjects"
                                                    >
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input select">

                                            </div>
                                        </td>
                                        <td>
                                            <div class="input select">
                                                <select chosen
                                                    multiple="multiple"
                                                    data-placeholder="<?=__('Select Some Options') ?>"
                                                    class="chosen-select"
                                                    options="ExamCentreController.specialNeeds"
                                                    ng-model="ExamCentreController.examCentreSpecialNeeds[institution.id]"
                                                    ng-options="item.special_need_id as item.special_need_name for item in ExamCentreController.specialNeeds"
                                                    >
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                            </table>
                        </div>
                    </div>
                </form>
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

<?php
$this->end();
?>