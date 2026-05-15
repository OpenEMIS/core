<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.archived.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.archived.ctrl', ['block' => true]); ?>

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
<?php
$this->end();

$this->start('panelBody');

$paramsQuery = $this->ControllerAction->getQueryString();
if (!is_array($paramsQuery)) {
    $paramsQuery = [];
}
$q = $this->getRequest()->getQuery();
foreach (['class_id', 'assessment_id', 'institution_id', 'academic_period_id'] as $key) {
    if (!isset($paramsQuery[$key]) || $paramsQuery[$key] === '' || $paramsQuery[$key] === null) {
        if (isset($q[$key])) {
            $paramsQuery[$key] = $q[$key];
        }
    }
}
if (
    empty($paramsQuery['class_id']) && empty($paramsQuery['assessment_id']) && empty($paramsQuery['institution_id'])
) {
    foreach (['queryString', 'querystring'] as $qsKey) {
        if (!empty($q[$qsKey]) && is_string($q[$qsKey])) {
            try {
                $decoded = $this->ControllerAction->getQueryString(null, $q[$qsKey]);
                if (is_array($decoded)) {
                    $paramsQuery = $decoded;
                    break;
                }
            } catch (\Exception $e) {
            }
        }
    }
}
$classId = (int)($paramsQuery['class_id'] ?? 0);
$assessmentId = (int)($paramsQuery['assessment_id'] ?? 0);
$institutionId = (int)($paramsQuery['institution_id'] ?? 0);

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
                    <uib-tab heading="<?= __('{{subject.name}}') ?>" ng-repeat="subject in subjects" ng-click="onChangeSubject(subject, subject.is_editable)">
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
