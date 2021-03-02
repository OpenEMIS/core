<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionassociations/institutionadd.associations.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionassociations/institutionadd.associations.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
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
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" ng-controller="InstitutionAssociationsCtrl as InstitutionAssociationsController">
    <div class="alert {{InstitutionAssociationsController.class}}" ng-hide="InstitutionAssociationsController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionAssociationsController.message}}
    </div>
    <div class="input string required">
        <label><?= __('Academic Period') ?></label>
        <div class="input-select-wrapper">
         <select name="academic_period_id" id="academic-period-id"
            ng-options="option.name for option in InstitutionAssociationsController.academicPeriodOptions.availableOptions track by option.id"
            ng-model="InstitutionAssociationsController.academicPeriodOptions.selectedOption"
            ng-change="InstitutionAssociationsController.onChangeAcademicPeriod()"
            >
        </select>
        </div>
    </div>
    <div class="input string required">
        <label><?= __('Name') ?></label>
        <input ng-model="InstitutionAssociationsController.associationName" type="string" ng-init="InstitutionAssociationsController.associationName='';">
        <div ng-if="InstitutionAssociationsController.postError.name" class="error-message">
            <p ng-repeat="error in InstitutionAssociationsController.postError.name">{{ error }}</p>
        </div>
    </div>
    <div class="input select">
        <label><?= __('Staff') ?></label>
        <select chosen
            data-placeholder="-- <?=__('Select Staff or Leave Blank') ?> --"
            name="InstitutionAssociations[security_user_id]"
            id="institutionassociations-security-user-id"
            multiple="multiple"
            class="chosen-select"
            options="InstitutionAssociationsController.secondaryTeacherOptions"
            ng-model="InstitutionAssociationsController.selectedSecondaryTeacher"
            ng-options="option.id as option.name for option in InstitutionAssociationsController.secondaryTeacherOptions"
            ng-init="InstitutionAssociationsController.selectedSecondaryTeacher=[];"
            ng-change="InstitutionAssociationsController.teacherOptions = InstitutionAssociationsController.changeStaff(InstitutionAssociationsController.selectedSecondaryTeacher);"
>
        </select>
        <div ng-if="InstitutionAssociationsController.postError.staff_id" class="error-message">
            <p ng-repeat="error in InstitutionAssociationsController.postError.staff_id">{{ error }}</p>
        </div>
    </div>
	<div class="input select">
        <label><?= __('Add Student') ?></label>
        <div class="input-form-wrapper" ng-init="InstitutionAssociationsController.classId='<?= $classId ?>'; InstitutionAssociationsController.redirectUrl='<?= $this->Url->build($viewUrl) ?>'; InstitutionAssociationsController.alertUrl='<?= $this->Url->build($alertUrl) ?>';">
    		<kd-multi-select ng-if="InstitutionAssociationsController.dataReady" grid-options-top="InstitutionAssociationsController.gridOptionsTop" grid-options-bottom="InstitutionAssociationsController.gridOptionsBottom"></kd-multi-select>
    	</div>

        <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save" type="button" ng-click="InstitutionAssociationsController.postForm();">
                <i class="fa fa-check"></i> <?= __('Save') ?>
                <input type="hidden"  ng-model="InstitutionAssociationsController.institutionId"  ng-init="InstitutionAssociationsController.institutionId=<?= $institutionId; ?>; "/>
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> '.__('Cancel'), $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
    </div>
</form>
<?php
$this->end();
?>
