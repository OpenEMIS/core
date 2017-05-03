<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_compentencies/institution.student.competencies.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_compentencies/institution.student.competencies.svc', ['block' => true]); ?>
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
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" ng-controller="InstitutionStudentCompetenciesCtrl as InstitutionStudentCompetenciesController" ng-init="InstitutionStudentCompetenciesController.classId=<?= $classId ?>; InstitutionStudentCompetenciesController.competencyTemplateId=<?=$competencyTemplateId ?>;">
    <div class="alert {{InstitutionStudentCompetenciesController.class}}" ng-hide="InstitutionStudentCompetenciesController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionStudentCompetenciesController.message}}
    </div>
    <div class="input string required">
        <label><?= __('Class Name') ?></label>
        <input ng-model="InstitutionStudentCompetenciesController.className" type="text" ng-init="InstitutionStudentCompetenciesController.className='';" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Academic Period') ?></label>
        <input ng-model="InstitutionStudentCompetenciesController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Competency Template') ?></label>
        <input ng-model="InstitutionStudentCompetenciesController.competencyTemplateName" type="text" disabled="disabled">
    </div>
    <div class="input">
        <label><?= __('Competency Period') ?></label>
        <div class="input-selection">
            <div class="input">
                <div class="selection-wrapper">
                    <input ng-repeat="period in InstitutionStudentCompetenciesController.periodOptions" ng-value="{{period.id}}" kd-checkbox-radio="{{period.name}}" type="radio" name="competency_period" ng-model="InstitutionStudentCompetenciesController.selectedPeriod" ng-change="InstitutionStudentCompetenciesController.changeCompetencyOptions(true);">
                </div>
            </div>
        </div>
    </div>
    <div class="input">
        <label><?= __('Competency Item') ?></label>
        <div class="input-selection">
            <div class="input">
                <div class="selection-wrapper">
                    <input ng-repeat="item in InstitutionStudentCompetenciesController.itemOptions" ng-value="{{item.id}}" kd-checkbox-radio="{{item.name}}" type="radio" name="competency_item" ng-model="InstitutionStudentCompetenciesController.selectedItem" ng-change="InstitutionStudentCompetenciesController.changeCompetencyOptions(false);">
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix">
    </div>
    <hr>
    <h3><?= __('Students') ?></h3>
    <div id="institution-student-competency-table" class="table-wrapper">
        <div ng-if="InstitutionStudentCompetenciesController.dataReady" ag-grid="InstitutionStudentCompetenciesController.gridOptions" class="ag-fresh ag-height-fixed"></div>
    </div>
</form>
<?php
$this->end();
?>