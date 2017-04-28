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
<form method="post" accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" action="" ng-controller="InstitutionStudentCompetenciesCtrl as InstitutionStudentCompetenciesController" ng-init="InstitutionStudentCompetenciesController.classId=<?= $classId ?>; InstitutionStudentCompetenciesController.competencyTemplateId=<?=$competencyTemplateId ?>;">
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
        <input ng-model="InstitutionStudentCompetenciesController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Competency Period') ?></label>
        <input ng-model="InstitutionStudentCompetenciesController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Competency Item') ?></label>
        <input ng-model="InstitutionStudentCompetenciesController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save" type="button" ng-click="InstitutionStudentCompetenciesController.postForm();">
                <i class="fa fa-check"></i> <?= __('Save') ?>
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> '.__('Cancel'), $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
</form>
<?php
$this->end();
?>