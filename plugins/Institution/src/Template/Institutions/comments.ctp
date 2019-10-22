<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
    <?php
        $backUrl = [
            'plugin' => $this->request->params['plugin'],
            'controller' => $this->request->params['controller'],
            'action' => 'ReportCardComments',
            'index'
        ];
        echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false, 'ng-show' => 'action == \'view\'']);
    ?>
    <?php if ($_edit) : ?>
        <!-- Show buttons when action is view: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view'" ng-click="InstitutionCommentsController.onEditClick()">
            <i class="fa kd-edit"></i>
        </button>
        <!-- End -->

        <!-- Show buttons when action is edit: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="action == 'edit'" ng-click="InstitutionCommentsController.onBackClick()">
            <i class="fa kd-back"></i>
        </button>
        <!-- End -->
    <?php endif; ?>
<?php
$this->end();

$this->start('panelBody');

$paramsQuery = $this->ControllerAction->getQueryString();
$classId = $paramsQuery['institution_class_id'];
$reportCardId = $paramsQuery['report_card_id'];
$institutionId = $paramsQuery['institution_id'];
?>
    <div class="alert {{InstitutionCommentsController.class}}" ng-hide="InstitutionCommentsController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionCommentsController.message}}
    </div>

    <div ng-init="classId=<?= $classId; ?>;reportCardId=<?= $reportCardId; ?>;institutionId=<?= $institutionId; ?>;">
        <div class="scrolltabs sticky-content">
            <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="{{tab.tabName}}" ng-repeat="tab in InstitutionCommentsController.tabs" ng-click="InstitutionCommentsController.onChangeSubject(tab)">
                    </uib-tab>
                </uib-tabset>
                <div class="tabs-divider"></div>
            </scrollable-tabset>

            <div id="institution-comment-table" class="table-wrapper">
                <div ng-if="InstitutionCommentsController.gridOptions" kd-ag-grid="InstitutionCommentsController.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
            </div>
        </div>
    </div>

    <style>
        .ag-cell.ag-cell-inline-editing {
            padding: 0 !important;
        }
        .ag-cell textarea#comment.error,
        .ag-cell #student_absence_reason_id select.error,
        .ag-cell #absence_type_id select.error {
            border-color: #CC5C5C !important;
        }
        
        .ag-cell textarea#comment:focus {
            outline: none;
        }

        .ag-cell textarea#comment {
            display: block;
            padding: 9px 8px;
            -webkit-border-radius: 3px;
            border-radius: 3px;
            font-size: 12px;
            height: 98%;
            width: 100%;
            border: 1px solid #CCC;
        }
    </style>

<?php
$this->end();
?>