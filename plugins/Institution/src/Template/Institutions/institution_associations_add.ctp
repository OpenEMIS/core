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
        <input ng-model="" type="text">
    </div>
    <div class="input string required">
        <label><?= __('Name') ?></label>
        <input  type="string" ng-init="InstitutionClassStudentsController.className='';">
        <div ng-if="InstitutionClassStudentsController.postError.name" class="error-message">
            <p ng-repeat="error in InstitutionClassStudentsController.postError.name">{{ error }}</p>
        </div>
    </div>
    <div class="input select">
        <label><?= __('Staff') ?></label>
        <select chosen
            data-placeholder="-- <?=__('Select Staff or Leave Blank') ?> --"
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
