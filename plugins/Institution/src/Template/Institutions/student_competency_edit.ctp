<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_compentencies/institution.student.competencies.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_compentencies/institution.student.competencies.svc', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
<?= $this->Html->link('<i class="fa kd-back"></i>', $viewUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escapeTitle' => false]) ?>

<?= $this->Html->link('<i class="fa kd-lists"></i>', $indexUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]) ?>
<?php
$this->end();
$this->start('panelBody');
?>
<style type="text/css">
    .ag-body-container {
        max-height: 380px;
    }
    .ag-floating-bottom-viewport 
    .ag-floating-bottom-container .ag-row{min-height:110px;}
</style>
<form accept-charset="utf-8" id="content-main-form" class="ng-pristine ng-valid" novalidate="novalidate" ng-controller="InstitutionStudentCompetenciesCtrl as InstitutionStudentCompetenciesController" ng-init="InstitutionStudentCompetenciesController.classId=<?= $classId ?>; InstitutionStudentCompetenciesController.competencyTemplateId=<?=$competencyTemplateId ?>;">
    <div class="form-horizontal">
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
    </div>
    <div class="clearfix"></div>
    <hr>
    <h3><?= __('Student') ?></h3>
    <div class="dropdown-filter">
        <div class="filter-label">
            <i class="fa fa-filter"></i>
            <label><?= __('Filter')?></label>
        </div>
        <div class="select">
            <label><?=__('Competency Period');?>:</label>
            <div class="input-select-wrapper">
                <select name="competency_period" ng-options="period.id as period.name for period in InstitutionStudentCompetenciesController.periodOptions" ng-model="InstitutionStudentCompetenciesController.selectedPeriod" ng-change="InstitutionStudentCompetenciesController.changeCompetencyOptions(true);">
                    <option value="" ng-if="InstitutionStudentCompetenciesController.periodOptions.length == 0"><?= __('No Options') ?></option>
                </select>
            </div>
        </div>
        <div class="select">
            <label><?=__('Competency Item');?>:</label>
            <div class="input-select-wrapper">
                <select name="competency_item" ng-options="item.id as item.name for item in InstitutionStudentCompetenciesController.itemOptions" ng-model="InstitutionStudentCompetenciesController.selectedItem" ng-change="InstitutionStudentCompetenciesController.changeCompetencyOptions(false);">
                    <option value="" ng-if="InstitutionStudentCompetenciesController.itemOptions.length == 0"><?= __('No Options') ?></option>
                </select>
            </div>
        </div>
        <div class="select">
            <label><?=__('Student');?>:</label>
            <div class="input-select-wrapper">
                <select name="student" ng-options="student.student_id as student.user.name_with_id for student in InstitutionStudentCompetenciesController.studentOptions" ng-model="InstitutionStudentCompetenciesController.selectedStudent" ng-change="InstitutionStudentCompetenciesController.changeStudentOptions(true);">
                    <option value="" ng-if="InstitutionStudentCompetenciesController.studentOptions.length == 0"><?= __('No Options') ?></option>
                </select>
            </div>
        </div>
        <div class="text">
            <label><?= __('Status') ?></label>
            <input ng-model="InstitutionStudentCompetenciesController.selectedStudentStatus" type="text" disabled="disabled">
        </div>
    </div>
    <div id="institution-student-competency-table" class="table-wrapper">
        <div ng-if="InstitutionStudentCompetenciesController.dataReady" kd-ag-grid="InstitutionStudentCompetenciesController.gridOptions"></div>
    </div>
</form>

<?php
$this->end();
?>