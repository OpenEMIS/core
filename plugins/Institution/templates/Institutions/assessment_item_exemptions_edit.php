<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionclasses/institution.class.students.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/assessment.item.exemptions.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/assessment.item.exemptions.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/angular-chosen.min', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->element('OpenEmis.breadcrumbs');
$this->start('toolbar');
?>
<style>
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
<?= $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escapeTitle' => false]) ?>
<!---->
<?php //= $this->Html->link('<i class="fa kd-lists"></i>', $indexUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]) ?>
<?php
$this->end();
$this->start('panelBody');

?>
<form accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate"
      ng-controller="AssessmentItemExemptionsCtrl as $ctrl"
      ng-init='
          $ctrl.abc = "abc";
          $ctrl.academic_period_id = <?= $academic_period_id ?>;
          $ctrl.institution_id = <?= $institution_id ?>;
          $ctrl.institution_class_id = <?= $institution_class_id ?>;
          $ctrl.assessment_id = <?= $assessment_id ?>;
          $ctrl.education_grade_id = <?= $education_grade_id ?>;
          $ctrl.assessment_items =  <?= json_encode($assessment_items) ?>;
          $ctrl.assessment_periods =  <?= json_encode($assessment_periods) ?>;
          $ctrl.backUrl =  "<?= \Cake\Routing\Router::url($backUrl) ?>";
          $ctrl.alertUrl =  "<?= \Cake\Routing\Router::url($alertUrl) ?>";
      '>
    <div class="alert {{$ctrl.messageClass}}" ng-if="$ctrl.message">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{$ctrl.message}}
    </div>

    <?php
    echo $this->Form->input('academic_period_name', [
        'type' => 'text',
        'label' => 'Academic Period',
        'value' => $academic_period_name,
        'required' =>'required',
        'disabled' => true,            // Make the field disabled
        'readonly' => true,            // Optional: Prevent any changes (if needed)
        ]);
        ?>
    <?php
    echo $this->Form->input('assessment_name', [
        'type' => 'text',
        'label' => 'Assessment',
        'value' => $assessment_name,
        'required' =>'required',
        'disabled' => true,            // Make the field disabled
        'readonly' => true,            // Optional: Prevent any changes (if needed)
    ]);
    ?>
    <?php
    echo $this->Form->input('education_grade_name', [
        'type' => 'text',
        'label' => 'Education Grade',
        'value' => $education_grade_name,
        'required' =>'required',
        'disabled' => true,            // Make the field disabled
        'readonly' => true,            // Optional: Prevent any changes (if needed)
    ]);
    ?>
    <?php
    echo $this->Form->input('class_name', [
        'type' => 'text',
        'label' => 'Class',
        'value' => $institution_class_name,
        'required' =>'required',
        'disabled' => true,            // Make the field disabled
        'readonly' => true,            // Optional: Prevent any changes (if needed)
    ]);
    ?>

    <div class="input select required">
        <label><?= __('Education Subjects') ?></label>
        <div class="input-select-wrapper">
            <select name="assessment_item_id" id="assessment-item-id"
                    ng-options="option.id as option.name for option in $ctrl.assessment_items"
                    ng-model="$ctrl.assessment_item_id"
                    ng-change="$ctrl.onSubjectChange();$ctrl.checkAndLoadStudents();"
            >
                <option value=""><?= __('-- Select --') ?></option>
            </select>
        </div>

    </div>
    <!--//POCOR-9114 START--->
    <div class="input select required">
        <label><?= __('Assessment Periods') ?></label>
            <select name="assessment_period_id" id="assessment-period-id" multiple="multiple"
            class="chosen-select"
                    ng-options="option.id as option.name for option in $ctrl.assessment_periods"
                    ng-model="$ctrl.assessment_period_id"
                    ng-change="$ctrl.onSubjectChange();$ctrl.checkAndLoadStudents();$ctrl.onPeriodChange();"
            >
                <option value=""><?= __('-- Select --') ?></option>
            </select>
        <div ng-if="error.assessment-period-id" class="error-message">
            <p>{{ error.assessment-period-id }}</p>
        </div>
    </div>
    <!--//POCOR-9114 END--->
    <!--//POCOR-9042 add Action strats--->
    <div class="input select required">
        <label><?= __('Action') ?></label>
        <div class="input-select-wrapper">
            <select name="type" id="excempttype"
                    ng-options="option.id as option.name for option in $ctrl.excempttype"
                    ng-model="$ctrl.excempttype_id"
                    ng-change="$ctrl.onExcemptTypeChange();$ctrl.checkAndLoadStudents();"
                    ng-disabled="!$ctrl.actionEnabled">
                <option value=""><?= __('-- Select --') ?></option>
            </select>
        </div>
    </div>
    <!--//POCOR-9428 student status start--->
    <div class="input select required">
        <label><?= __('Student Status') ?></label>
        <div class="input-select-wrapper">
             <select name="type" id="studentStatuses"
        ng-options="option.id as option.name for option in $ctrl.studentStatuses"
        ng-model="$ctrl.studentstatus_id"
        ng-change="$ctrl.onStudentStatusChange($ctrl.studentstatus_id);$ctrl.checkAndLoadStudents();">
        <option value=""><?= __('-- Select --') ?></option>
    </select>
        </div>
    </div> <!--//POCOR-9428 student status end--->

    <!--//POCOR-9042 add Action ends--->
    <div class="input select">
        <label><?= __('Students') ?></label>
        <div class="input-form-wrapper">
            <kd-multi-select ng-if="$ctrl.dataReady && $ctrl.isAllSelected()" text
                             grid-options-top="$ctrl.gridOptionsTop"
                             grid-options-bottom="$ctrl.gridOptionsBottom"
                             config="$ctrl.textConfig"
            >
            </kd-multi-select>
        </div>

        <div class="form-buttons">
            <div class="button-label"></div>
            <button class="btn btn-default btn-save"
                    type="button"
                    ng-click="$ctrl.postForm()"
                    ng-disabled="!$ctrl.saveEnabled">
                <i class="fa fa-check"></i> <?= __('Save') ?>
            </button>
            <?= $this->Html->link('<i class="fa fa-close"></i> '.__('Cancel'), $backUrl, ['class' => 'btn btn-outline btn-cancel', 'escapeTitle' => false]) ?>
            <button id="reload" type="submit" name="submit" value="reload" class="hidden">reload</button>
        </div>
    </div>

</form>
<?php
$this->end();
?>
