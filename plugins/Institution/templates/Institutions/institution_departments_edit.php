<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutiondepartments/institution.departments.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutiondepartments/institution.departments.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
$paramsQueryStringFirst = $this->request->getAttribute('params')['pass'][1];
$paramsQueryStringSecond = $this->request->getAttribute('params')['pass'][2];
$institutionId = $this->ControllerAction->paramsDecode($paramsQueryStringFirst)['institution_id'];
$departmentId = $this->ControllerAction->paramsDecode($paramsQueryStringFirst)['id'];
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
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate"
      ng-controller="InstitutionDepartmentsCtrl as InstitutionDepartmentsController">
    <div class="alert {{InstitutionDepartmentsController.class}}"
         ng-hide="InstitutionDepartmentsController.message == null">
        <a class="close" aria-hidden="true" href="#"
           data-dismiss="alert">×</a>{{InstitutionDepartmentsController.message}}
    </div>
    <div class="input string required">
        <label><?= __('Name') ?></label>
        <input ng-model="InstitutionDepartmentsController.departmentName" type="string"
               ng-init="InstitutionDepartmentsController.departmentName='';">
        <div ng-if="InstitutionDepartmentsController.postError.name" class="error-message">
            <p ng-repeat="error in InstitutionDepartmentsController.postError.name">{{ error }}</p>
        </div>
    </div>
    <div class="input string required">
        <label><?= __('Code') ?></label>
        <input ng-model="InstitutionDepartmentsController.departmentCode" type="string"
               ng-init="InstitutionDepartmentsController.departmentCode='';">
        <div ng-if="InstitutionDepartmentsController.postError.code" class="error-message">
            <p ng-repeat="error in InstitutionDepartmentsController.postError.code">
                {{ error }}
            </p>
        </div>
    </div>
    <div class="input select">
        <label><?= __('Manager') ?></label>
        <div class="input-select-wrapper">
            <select
                name="InstitutionDepartments[manager]"
                id="institution-departments-manager-id"
                ng-model="InstitutionDepartmentsController.managerId"
                ng-options="option.security_user_id as option.name for option in InstitutionDepartmentsController.managerOptions"
                ng-init="InstitutionDepartmentsController.managerId=null;"
                ng-change="InstitutionDepartmentsController.filterStaff();"
            >
                <option value="">-- <?= __('Select Manager or Leave Blank') ?> --</option>
            </select>
        </div>
        <div ng-if="InstitutionDepartmentsController.postError.manager_id" class="error-message">
            <p ng-repeat="error in InstitutionDepartmentsController.postError.manager_id">{{ error }}</p>
        </div>
    </div>
    <div class="input select">
        <label><?= __('Add Staff') ?></label>
        <div class="input-form-wrapper"
             ng-init="InstitutionDepartmentsController.departmentId='<?= $departmentId ?>'; InstitutionDepartmentsController.redirectUrl='<?= $this->Url->build($viewUrl) ?>'; InstitutionDepartmentsController.alertUrl='<?= $this->Url->build($alertUrl) ?>';">
            <kd-multi-select ng-if="InstitutionDepartmentsController.dataReady"
                             config="InstitutionDepartmentsController.textConfig"
                             grid-options-top="InstitutionDepartmentsController.gridOptionsTop"
                             grid-options-bottom="InstitutionDepartmentsController.gridOptionsBottom"></kd-multi-select>
        </div>

        <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save" type="button"
                    ng-click="InstitutionDepartmentsController.postForm();">
                <i class="fa fa-check"></i> <?= __('Save') ?>
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> ' . __('Cancel'), $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
    </div>
</form>
<?php
echo "<script>

// Set values in local storage
localStorage.setItem('queryString1', '" . $paramsQueryStringFirst . "');
localStorage.setItem('queryString2', '" . $paramsQueryStringSecond . "');
</script>";
$this->end();
?>
