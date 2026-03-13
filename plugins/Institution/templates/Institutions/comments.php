<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.ctrl', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
<style type='text/css'>
    .toolbar .search {
        position: inherit;
        right: inherit;
        top: inherit;
    }
</style>


    <?php
        $backUrl = [
            'plugin' => $this->request->getAttribute('params')['plugin'],
            'controller' => $this->request->getAttribute('params')['controller'],
            'action' => 'ReportCardComments',
            'index',
            $queryString //POCOR-8987
        ];
        echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false, 'ng-show' => 'action == \'view\'']);
    ?>

    <?php if ($_edit) : ?>
        <!-- Show buttons when action is view: -->
        <!-- POCOR-6800: added ng-show="action == 'view' && checkaction == 1" || initial value ng-show="action == 'view'" -->
         <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view' && (checkEditAction == 1 || checkPrincipalEditAction == 1 || checkHomeroomTeacherEditAction == 1 || checkMyTeacherEditAction == 1|| isHomeRoomClass == 1 || subjectIsEditable == 1)" ng-click="InstitutionCommentsController.onEditClick()">
             <i class="fa kd-edit"></i>
         </button>
        <!-- End -->

        <!-- Show buttons when action is edit: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="action == 'edit'" ng-click="InstitutionCommentsController.onBackClick()">
            <i class="fa kd-back"></i>
        </button>
        <!-- End -->
    <?php endif; ?>
<div class="search">
    <div class="input-group">
        <div class="input text"><input type="text"
                                       name="SearchTeacher"
                                       class="form-control search-input focus"
                                       data-input-name="SearchTeacher"
                            placeholder="<?= __('Search Teachers') ?>"
                            ng-model="InstitutionCommentsController.teacherSearchText"
                            ng-change="InstitutionCommentsController.filterTeachers()"
                            ng-model-options="{ debounce: 300 }"
                            id="search-teachers"
            ></div>
    </div>
</div>

<?php
$this->end();
$this->start('panelBody');
?>

<?php

// $paramsQuery = $this->ControllerAction->getQueryString();

$paramsQuery = base64_decode($this->request->getAttribute('params')['?']['queryString']);

$jsonEndPosition = strpos($paramsQuery, '}') + 1;

$jsonData = substr($paramsQuery, 0, $jsonEndPosition);
$paramsQuery = json_decode($jsonData, true);
$classId = $paramsQuery['institution_class_id'];
$reportCardId = $paramsQuery['report_card_id'];
$institutionId = $paramsQuery['institution_id'];
$loginUserId = $_SESSION['Auth']['User']['id'];
?>
    <div class="alert {{InstitutionCommentsController.class}}" ng-hide="InstitutionCommentsController.message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{InstitutionCommentsController.message}}
    </div>

    <div ng-init="classId=<?= $classId; ?>;reportCardId=<?= $reportCardId; ?>;institutionId=<?= $institutionId; ?>;">
        <div class="scrolltabs sticky-content">
            <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="{{tab.tabName}}" ng-repeat="tab in InstitutionCommentsController.filteredTabs" ng-click="InstitutionCommentsController.onChangeSubject(tab)">
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
    <script>
        localStorage.setItem('login_user_id', '<?php echo $loginUserId;?>');
    </script>

<?php
$this->end();
?>
