<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.ctrl', ['block' => true]); ?>

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
            'action' => 'Assessments',
            'index',
            $queryString
        ];
        echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false, 'ng-show' => 'action == \'view\'']);
    ?>
    <?php if ($_edit) : ?>
        <!-- Show buttons when action is view: -->
        <!-- <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view' && editPermissionForSelectedSubject" ng-click="onEditClick()">
            <i class="fa kd-edit"></i>
        </button> -->
        <?php if ($_exempt) : ?>
            <a ng-show="action == 'view' && (editPermission == 1 || editPermission == undefined)"  href="<?= $exemptUrl ?>">
                <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body"
                        title="<?= __('Exempt/Unassign'); ?>"
                >
                    <i class="fa kd-null"></i>
                </button>
            </a>
        <?php endif; ?>

        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view' && (editPermission == 1 || editPermission == undefined)" ng-click="onEditClick()">
            <i class="fa kd-edit"></i>
        </button>
        <!-- End -->

        <!-- Show buttons when action is edit: -->
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="action == 'edit' && editPermissionForSelectedSubject" ng-click="onBackClick()">
            <i class="fa kd-back"></i>
        </button>
        <!-- End -->
    <?php endif; ?>
    <?php if ($_excel) : ?>
        <?php if (isset($reportCardGenerate)) : ?>
            <a href="<?=$reportCardGenerate ?>"><button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Report') ?>" ><i class="fa kd-header-row"></i></button></a>
            <a href="<?=$exportPDF ?>"><button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('PDF') ?>" ><i class="fa fa-file-pdf-o"></i></button></a>
        <?php endif;?>

        <a href="<?=$excelUrl ?>"><button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Export') ?>" ><i class="fa kd-export"></i></button></a>
    <?php endif; ?>
<div class="search">
    <div class="input-group">
        <div class="input text"><input type="text"
                                       name="SearchSubject"
                                       class="form-control search-input focus"
                                       data-input-name="SearchSubject"
                                       placeholder="<?= __('Search Subject') ?>"
                                       ng-model="subjectSearchText"
                                       ng-change="filterSubjects()"
                                       ng-model-options="{ debounce: 300 }"
                                       id="search-subjects"
            ></div>
    </div>
</div>
<?php
$this->end();

$this->start('panelBody');

$paramsQuery = base64_decode($this->request->getAttribute('params')['?']['queryString']);

$jsonEndPosition = strpos($paramsQuery, '}') + 1;
$jsonData = substr($paramsQuery, 0, $jsonEndPosition);
$paramsQuery = json_decode($jsonData, true);
// $paramsQuery = $this->ControllerAction->getQueryString();
$classId = $paramsQuery['class_id'];
$assessmentId = $paramsQuery['assessment_id'];
$institutionId = $paramsQuery['institution_id'];

//follow the JS array requirement
$roles = '[' . implode(",", $_roles) . ']';
?>
    <div class="alert {{class}}" ng-hide="message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
    </div>

    <div class="toolbar-responsive panel-toolbar" ng-show="academicTermOptions.length > 0">
        <div class="toolbar-wrapper">
            <div class="input select">
                <div class="input-select-wrapper">
                    <select
                        class="form-control"
                        ng-options="option.id as option.name for option in academicTermOptions"
                        ng-model="selectedAcademicTerm"
                        ng-change="changeAcademicTerm();"
                        >
                            <option value=""><?= '-- '. __('Select Academic Term').' --' ?></option>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div ng-init="class_id=<?= $classId; ?>;assessment_id=<?= $assessmentId; ?>;institution_id=<?= $institutionId; ?>;roles=<?=$roles; ?>;dynamicTotalMarkHeader='<?= addslashes($dynamicTotalMarkHeader); ?>'">
        <div class="scrolltabs sticky-content">
      <scrollable-tabset show-tooltips="false" show-drop-down="false">
                <uib-tabset justified="true">
                    <uib-tab heading="<?= __('{{subject.name}}') ?>" ng-repeat="subject in filteredSubjects track by subject.id" ng-click="onChangeSubject(subject, subject.is_editable)"><!--POCOR-7785: Use filteredSubjects for search-->
                    </uib-tab>
                </uib-tabset>
                <div class="tabs-divider"></div>
            </scrollable-tabset>

            <div id="institution-result-table" class="table-wrapper">
                <div ng-if="gridOptions" kd-ag-grid="gridOptions" has-tabs="true" class="ag-height-fixed"></div>
            </div>
        </div>
    </div>

<?php
$this->end();
?>
