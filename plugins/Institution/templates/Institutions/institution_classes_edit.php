<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionclasses/institution.class.students.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionclasses/institution.class.students.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
<style type='text/css'>
    .ag-grid-duration {
        width: 50%;
        border: none;
        background-color: inherit;
        text-align: center;
    }

    .ag-grid-dir-ltr {
        direction: ltr !important;
    }
</style>
<?= $this->Html->link('<i class="fa kd-back"></i>', $viewUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escapeTitle' => false]) ?>

<?= $this->Html->link('<i class="fa kd-lists"></i>', $indexUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]) ?>
<?php
$this->end();
$this->start('panelBody');
?>
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" ng-controller="InstitutionClassStudentsCtrl as InstitutionClassStudentsController">
    <div class="alert {{InstitutionClassStudentsController.class}}" ng-hide="InstitutionClassStudentsController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionClassStudentsController.message}}
    </div>
    <div class="input string required">
        <label><?= __('Academic Period') ?></label>
        <input ng-model="InstitutionClassStudentsController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Class Name') ?></label>
        <input ng-model="InstitutionClassStudentsController.className" type="string" ng-init="InstitutionClassStudentsController.className='';">
        <div ng-if="InstitutionClassStudentsController.postError.name" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.name">{{ error }}</p>
        </div>
    </div>
    <div class="input select required error">
        <label><?= __('Shift') ?></label>
        <div class="input-select-wrapper">
            <select name="InstitutionClasses[institution_shift_id]" id="institutionclasses-institution-shift-id"
                ng-options="option.id as option.name for option in InstitutionClassStudentsController.shiftOptions"
                ng-model="InstitutionClassStudentsController.selectedShift"
                ng-init="InstitutionClassStudentsController.selectedShift=null;"
                >
                <option value="" >-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="InstitutionClassStudentsController.postError.institution_shift_id" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.institution_shift_id">{{ error }}</p>
        </div>
    </div>
    <!-- removed  unit and course visibility  for POCOR-8617 -- --->
    <!-- create if condition for unit and course visibility  for POCOR-8107 -- --->
<!-- <?php if ($viewUrl['unit_field'] == 1){ ?>
    <div class="input select error">
        <label><?= __('Internal Verification') ?></label>
        <div class="input-select-wrapper">
            <select name="InstitutionClasses[institution_unit_id]" id="institutionclasses-institution-unit-id"
                ng-options="option.id as option.name for option in InstitutionClassStudentsController.unitOptions"
                ng-model="InstitutionClassStudentsController.selectedUnit"
                ng-init="InstitutionClassStudentsController.selectedUnit=null;"
                >
                <option value="" >-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="InstitutionClassStudentsController.postError.institution_unit_id" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.institution_unit_id">{{ error }}</p>
        </div>
    </div>
<?php } ?> -->
<?php if ($viewUrl['course_field'] == 1){ ?>
    <!-- <div class="input select error">
        <label><?= __('External Verification') ?></label>
        <div class="input-select-wrapper">
            <select name="InstitutionClasses[institution_course_id]" id="institutionclasses-institution-course-id"
                ng-options="option.id as option.name for option in InstitutionClassStudentsController.courseOptions"
                ng-model="InstitutionClassStudentsController.selectedCourse"
                ng-init="InstitutionClassStudentsController.selectedCourse=null;"
                >
                <option value="" >-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="InstitutionClassStudentsController.postError.institution_course_id" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.institution_course_id">{{ error }}</p>
        </div>
    </div> -->
    <?php } ?>
    <div class="input select">
        <label><?= __($homeRoomTeacherName) ?></label>
        <div class="input-select-wrapper">
            <select name="InstitutionClasses[staff_id]" id="institutionclasses-staff-id"
                ng-options="option.id as option.name for option in InstitutionClassStudentsController.teacherOptions"
                ng-model="InstitutionClassStudentsController.selectedTeacher"
                ng-init="InstitutionClassStudentsController.selectedTeacher=null;"
                ng-change="InstitutionClassStudentsController.secondaryTeacherOptions = InstitutionClassStudentsController.changeStaff(InstitutionClassStudentsController.selectedTeacher);"
                >
                <option value="" >-- <?= __('Select Teacher or Leave Blank') ?> --</option>
            </select>
        </div>
        <div ng-if="InstitutionClassStudentsController.postError.staff_id" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.staff_id">{{ error }}</p>
        </div>
    </div>
    <div class="input select">
        <label><?= __($secondarystaffName) ?></label>
        <select chosen
            data-placeholder="-- <?=__('Select Teacher or Leave Blank') ?> --"
            name="InstitutionClasses[secondary_staff_id]"
            id="institutionclasses-secondary-staff-id"
            multiple="multiple"
            class="chosen-select"
            options="InstitutionClassStudentsController.secondaryTeacherOptions"
            ng-model="InstitutionClassStudentsController.selectedSecondaryTeacher"
            ng-options="option.id as option.name for option in InstitutionClassStudentsController.secondaryTeacherOptions"
            ng-init="InstitutionClassStudentsController.selectedSecondaryTeacher=[];"
            ng-change="InstitutionClassStudentsController.teacherOptions = InstitutionClassStudentsController.changeStaff(InstitutionClassStudentsController.selectedSecondaryTeacher);"
>
        </select>
        <div ng-if="InstitutionClassStudentsController.postError.staff_id" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.staff_id">{{ error }}</p>
        </div>
    </div>
    <div class="input string required">
        <label><?=
            __('Capacity') . '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' .  __('Capacity must not exceed') . ' {{InstitutionClassStudentsController.maxStudentsPerClass}} ' . __('students per class') . '"></i>'
        ?></label>
        <input ng-model="InstitutionClassStudentsController.classCapacity" type="string" ng-init="InstitutionClassStudentsController.classCapacity='';">
        <div ng-if="InstitutionClassStudentsController.postError.capacity" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.capacity">{{ error }}</p>
        </div>
    </div>

    <div ng-repeat="customField in InstitutionClassStudentsController.customFieldsArray">
        <div class="row section-header header-space-lg">{{customField.sectionName}}</div>

        <div ng-repeat="field in customField.data">
            <div class="input string" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL'">
                <label>{{field.name}}</label>
                <input ng-if="field.field_type === 'TEXT'"
                       ng-model="field.answer" type="text"
                       ng-required="field.is_mandatory !== 0">
                <textarea ng-if="field.field_type === 'TEXTAREA' || field.field_type === 'NOTE'" ng-model="field.answer" type="text" ng-required="field.is_mandatory !== 0"></textarea>
                <input ng-if="field.field_type === 'NUMBER'" ng-model="field.answer" type="number" ng-required="field.is_mandatory !== 0">
                <input ng-if="field.field_type === 'DECIMAL'" ng-model="field.answer" type="number" step="0.01" onKeyPress="if(this.value.length === 10) return false;"
                       ng-change="onDecimalNumberChange(field)"
                       ng-required="field.is_mandatory !== 0">
                <div ng-if="field.errorMessage" class="error-message">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
            <div class="input select" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DROPDOWN'">
                <label>{{field.name}}</label>
                <div class="input-select-wrapper">
                    <select name="Student[option_id]" id={{field.institution_custom_field_id}}
                            ng-options="option.option_id as option.option_name for option in field.option"
                            ng-model="field.answer"
                            ng-change="changeOption(field,field.answer)" ng-required="field.is_mandatory !== 0">
                        <option value="" >-- <?= __('Select') ?> --</option>
                    </select>
                </div>
                <div ng-if="field.errorMessage" class="error-message">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
            <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DATE'">
                <label for={{field.institution_custom_field_id}}>{{field.name}}</label>
                <div class="input-group date" id={{field.institution_custom_field_id}} datepicker="" ng-model="field.answer" ng-click="[field.isDatepickerOpen = !field.isDatepickerOpen]" ng-init="field.isDatepickerOpen = false">
                    <input type="text" class="form-control" ng-model="field.answer"
                           uib-datepicker-popup='<?= $angularFormat ?>'
                           is-open="field.isDatepickerOpen" datepicker-options="datepickerOptions" close-text="Close" alt-input-formats="altInputFormats" style="width: calc(100% - 52px) !important" ng-change="field.isDatepickerOpen = false" ng-required="field.is_mandatory !== 0">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                </div>
                <div ng-if="field.errorMessage" class="error-message">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
            <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TIME'">
                <label for={{field.institution_custom_field_id}}>{{field.name}}</label>
                <div class="input-group time" uib-timepicker ng-model="field.answer" hour-step="field.hourStep" minute-step="field.minuteStep" show-meridian="field.isMeridian"></div>
                <div ng-if="field.errorMessage" class="error-message" style="margin-left: 150px;">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
            <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'CHECKBOX'">
                <label for={{field.institution_custom_field_id}}>{{field.name}}</label>
                <div class="input-group check_box">
                    <div ng-repeat="option in field.option">
                        <input type="checkbox" id={{option.option_id}}
                               name={{option.option_name}}
                               value={{option.option_id}}
                               ng-model="option.selected"
                               ng-change="InstitutionClassStudentsController.selectOption(field)" ng-required="field.is_mandatory !== 0">
                        <label for={{option.option_id}}> {{option.option_name}}</label>
                    </div>
                    <div ng-if="field.errorMessage" class="error-message">
                        <p>{{ field.errorMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row section-header header-space-lg"><?= __('Students') ?></div>

    <div class="input select">
        <label><?= __('Add Student') ?></label>
        <div class="input-form-wrapper" ng-init="InstitutionClassStudentsController.classId='<?= $classId ?>'; InstitutionClassStudentsController.redirectUrl='<?= $this->Url->build($viewUrl) ?>'; InstitutionClassStudentsController.alertUrl='<?= $this->Url->build($alertUrl) ?>';">
    		<kd-multi-select ng-if="InstitutionClassStudentsController.dataReady" grid-options-top="InstitutionClassStudentsController.gridOptionsTop" grid-options-bottom="InstitutionClassStudentsController.gridOptionsBottom"></kd-multi-select>
    	</div>

        <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save" type="button" ng-click="InstitutionClassStudentsController.postForm();">
                <i class="fa fa-check"></i> <?= __('Save') ?>
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> '.__('Cancel'), $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
    </div>
</form>
<?php
$this->end();
?>
