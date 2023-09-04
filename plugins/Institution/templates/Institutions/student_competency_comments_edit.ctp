<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_competency_comments/institution.student.competency_comments.ctrl', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_competency_comments/institution.student.competency_comments.svc', ['block' => true]); ?>
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
<form accept-charset="utf-8" id="content-main-form" class="ng-pristine ng-valid" novalidate="novalidate" ng-controller="InstitutionStudentCompetencyCommentsCtrl as InstitutionStudentCompetencyCommentsController" ng-init="InstitutionStudentCompetencyCommentsController.classId=<?= $classId ?>; InstitutionStudentCompetencyCommentsController.competencyTemplateId=<?=$competencyTemplateId ?>;">
    <div class="form-horizontal">
        <div class="alert {{InstitutionStudentCompetencyCommentsController.class}}" ng-hide="InstitutionStudentCompetencyCommentsController.message == null">
            <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionStudentCompetencyCommentsController.message}}
        </div>
        <div class="input string required">
            <label><?= __('Class Name') ?></label>
            <input ng-model="InstitutionStudentCompetencyCommentsController.className" type="text" ng-init="InstitutionStudentCompetencyCommentsController.className='';" disabled="disabled">
        </div>
        <div class="input string required">
            <label><?= __('Academic Period') ?></label>
            <input ng-model="InstitutionStudentCompetencyCommentsController.academicPeriodName" type="text" disabled="disabled">
        </div>
        <div class="input string required">
            <label><?= __('Competency Template') ?></label>
            <input ng-model="InstitutionStudentCompetencyCommentsController.competencyTemplateName" type="text" disabled="disabled">
        </div>
    </div>
    <div class="clearfix">
    </div>
    <hr>
    <h3><?= __('Students') ?></h3>
    <div id="institution-student-competency-comments-table" class="table-wrapper">
        <div ng-if="InstitutionStudentCompetencyCommentsController.dataReady" kd-ag-grid="InstitutionStudentCompetencyCommentsController.gridOptions" class="ag-height-fixed"></div>
    </div>
</form>
<?php
$this->end();
?>