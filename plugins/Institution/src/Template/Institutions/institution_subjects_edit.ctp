<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionsubjects/institution.subject.students.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionsubjects/institution.subject.students.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
<form method="post" accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" action="" ng-controller="InstitutionSubjectStudentsCtrl as InstitutionSubjectStudentsController">
    <div class="alert {{InstitutionSubjectStudentsController.class}}" ng-hide="InstitutionSubjectStudentsController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionSubjectStudentsController.message}}
    </div>
    <div class="input string required">
        <label><?= __('Name') ?></label>
        <input ng-model="InstitutionSubjectStudentsController.institutionSubjectName" type="string" ng-init="InstitutionSubjectStudentsController.institutionSubjectName='';">
        <div ng-if="InstitutionSubjectStudentsController.postError.name" class="error-message">
            <p ng-repeat="error in InstitutionSubjectStudentsController.postError.name">{{ error }}</p>
        </div>
    </div>
    <div class="input string required">
        <label><?= __('Academic Period') ?></label>
        <input ng-model="InstitutionSubjectStudentsController.academicPeriodName" type="text" disabled="disabled">
    </div>
    <div class="input string required">
        <label><?= __('Subject Name') ?></label>
        <input ng-model="InstitutionSubjectStudentsController.educationSubjectName" type="text" disabled="disabled">
    </div>
    <div class="input select">
        <label><?= __('Teachers') ?></label>
        <select chosen
            multiple="multiple"
            data-placeholder="<?=__('Select Teacher') ?>"
            class="chosen-select"
            options="InstitutionSubjectStudentsController.teacherOptions"
            ng-model="InstitutionSubjectStudentsController.teachers"
            ng-options="item.id as item.name for item in InstitutionSubjectStudentsController.teacherOptions"
            ng-init="InstitutionSubjectStudentsController.questionOptions = []">
        </select>
    </div>
    <div class="input select" ng-hide="InstitutionSubjectStudentsController.pastTeachers.length == 0">
        <label><?= __('Past Teachers') ?></label>
        <div class="form-input table-full-width">
            <div class="table-wrapper">
                <div class="table-in-view">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= __('Teacher Name')?></th>
                                <th><?= __('Start Date')?></th>
                                <th><?= __('End Date')?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr ng-repeat="teacher in InstitutionSubjectStudentsController.pastTeachers">
                                <td class="vertical-align-top">{{teacher.name_with_id}}</td>
                                <td class="vertical-align-top">{{teacher.start_date}}</td>
                                <td class="vertical-align-top">{{teacher.end_date}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="input select">
        <label><?= __('Rooms') ?></label>
        <select chosen
            multiple="multiple"
            data-placeholder="<?=__('Select Room') ?>"
            class="chosen-select"
            options="InstitutionSubjectStudentsController.roomOptions"
            ng-model="InstitutionSubjectStudentsController.rooms"
            ng-options="item.id as item.name for item in InstitutionSubjectStudentsController.teacherOptions"
            ng-init="InstitutionSubjectStudentsController.rooms = []">
        </select>
    </div>
	<div class="input select">
        <label><?= __('Add Student') ?></label>
        <div class="input-form-wrapper" ng-init="InstitutionSubjectStudentsController.institutionSubjectId='<?= $institutionSubjectId ?>'; InstitutionSubjectStudentsController.redirectUrl='<?= $this->Url->build($viewUrl) ?>';">
    		<kd-multi-select ng-if="InstitutionSubjectStudentsController.dataReady" grid-options-top="InstitutionSubjectStudentsController.gridOptionsTop" grid-options-bottom="InstitutionSubjectStudentsController.gridOptionsBottom"></kd-multi-select>
    	</div>

        <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save" type="button" ng-click="InstitutionSubjectStudentsController.postForm();">
                <i class="fa fa-check"></i> Save
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> Cancel</a>', $viewUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
    </div>
</form>
<?php
$this->end();
?>
