<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Examination.angular/results/examinations.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Examination.angular/results/examinations.results.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
    <?php if ($_edit) : ?>
        <!-- Show buttons when action is view: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view'" ng-click="ExaminationsResultsController.onEditClick()">
            <i class="fa kd-edit"></i>
        </button>
        <!-- End -->

        <!-- Show buttons when action is edit: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="action == 'edit'" ng-click="ExaminationsResultsController.onBackClick()">
            <i class="fa kd-back"></i>
        </button>
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Save');?>" ng-show="action == 'edit'" ng-click="ExaminationsResultsController.onSaveClick()">
            <i class="fa fa-save"></i>
        </button>
        <!-- End -->
    <?php endif; ?>
<?php
$this->end();

$this->start('panelBody');
?>
    <div class="alert {{class}}" ng-hide="message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
    </div>

    <?= $this->element('Examination.Examinations/controls'); ?>

    <div ng-init="">
        <div class="scrolltabs sticky-content">
            <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="{{subject.name}}" ng-repeat="subject in ExaminationsResultsController.subjects" ng-click="ExaminationsResultsController.onChangeSubject(subject)">
                    </uib-tab>
                </uib-tabset>
                <div class="tabs-divider"></div>
            </scrollable-tabset>

            <div id="examination-result-table" class="table-wrapper">
                <div ng-if="ExaminationsResultsController.gridOptions" ag-grid="ExaminationsResultsController.gridOptions" class="ag-fresh ag-height-fixed"></div>
            </div>
        </div>
    </div>

<?php
$this->end();
?>
