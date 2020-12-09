<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/assessments_archive/institution.assessments.archive.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/assessments_archive/institution.assessments.archive.ctrl', ['block' => true]); ?>

<?php
$this->start('toolbar');
?>
<?php if ($backUrl) : ?>
    <a href="<?=$backUrl ?>" ng-show="$ctrl.action == 'view'">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back') ?>" >
            <i class="fa kd-back"></i>
        </button>
    </a>
</button>
<?php endif; ?>

<?php
$this->end();
?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<?= $this->element('OpenEmis.alert') ?>

<style>
    .attendance-dashboard .data-section.single-day {
        width: 24%;
    }

    .attendance-dashboard .data-section i.fa-address-book-o {
        background-color: transparent;
        border: none;
        color: #999;
    }

    .splitter-filter select[disabled] {
        background-color: #f2f2f2!important;
        border: 1px solid #ccc!important;
        color: #999!important;
    }
    /* .ag-root {
        overflow-x: scroll;
    }
    .ag-header {width:154%} */

    .splitter-filter .split-content-header {
        margin-bottom: 15px;
    }

    .splitter-filter .input-selection.attendance {
        width: 100%;
    }

    .splitter-filter .input-selection.attendance.disabled {
        background-color: #f2f2f2!important;
    }

    #institution-student-attendances-table .sg-theme .ag-cell {
        display: flex;
        flex-flow: column wrap;
        justify-content: center;
    }

    #institution-student-attendances-table .ag-cell .reason-wrapper {
        position: relative;
        width: 100%;
        display: inline-block;
    }

    #institution-student-attendances-table .ag-cell .reason-wrapper .input-select-wrapper {
        margin-bottom: 15px;
    }

    #institution-student-attendances-table .ag-cell textarea#comment.error,
    #institution-student-attendances-table .ag-cell #student_absence_reason_id select.error,
    #institution-student-attendances-table .ag-cell #absence_type_id select.error {
        border-color: #CC5C5C !important;
    }

    #institution-student-attendances-table .ag-cell textarea#comment:focus {
        outline: none;
    }

    #institution-student-attendances-table .ag-cell textarea#comment {
        display: block;
        padding: 5px 10px;
        -webkit-border-radius: 3px;
        border-radius: 3px;
        font-size: 12px;
        height: 70px;
        width: 100%;
        border: 1px solid #CCC;
    }

    #institution-student-attendances-table .ag-cell .input-select-wrapper {
        margin-bottom: 0;
    }

    #institution-student-attendances-table .ag-cell .input-select-wrapper select {
        background: #FFFFFF;
        display: block;
    }

    #institution-student-attendances-table .ag-cell .absence-reason,
    #institution-student-attendances-table .ag-cell .absences-comment {
        overflow: hidden;
        white-space: normal;
        text-overflow: ellipsis;
        max-height: 70px;
        display: flex;
        align-items: baseline;
    }

    #institution-student-attendances-table .ag-cell .absence-reason span,
    #institution-student-attendances-table .ag-cell .absences-comment span {
        margin: 0 10px;
    }


    #institution-student-attendances-table .ag-cell .absence-reason + .absences-comment  {
        margin-top: 15px;
    }

    #institution-student-attendances-table .sg-theme .ag-header-cell.children-period .ag-header-cell-label {
        display: flex;
        justify-content: center;
        padding: 10px 0;
    }

    #institution-student-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 1px solid #DDDDDD;
        border-bottom: 1px solid #DDDDDD;
        font-weight: 700;
        text-align: center;
        padding: 10px;
    }

    #institution-student-attendances-table .sg-theme .children-cell {
        text-align: center;
    }

    #institution-student-attendances-table .sg-theme .ag-row-hover {
        background-color: #FDFEE6 !important;
    }

    .rtl #institution-student-attendances-table .sg-theme .ag-header-group-cell {
        border-right: 0;
        border-left: 1px solid #DDDDDD;
    }

    .rtl #institution-student-attendances-table .ag-cell textarea#comment {
        font-size: 14px;
    }

    .mobile-split-btn button.btn-default{z-index:9999!important; bottom:40px; position:fixed !important; right:15px;}

    @media screen and (max-width:667px){
         .table-wrapper ::-webkit-scrollbar {
             -webkit-appearance: none;
         }

         .table-wrapper ::-webkit-scrollbar:vertical {
             width: 8px;
         }

         .table-wrapper ::-webkit-scrollbar:horizontal {
             height: 8px;
         }

         .table-wrapper ::-webkit-scrollbar-thumb {
             background-color: rgba(0, 0, 0, .3);
             border-radius: 10px;
             border: 2px solid #ffffff;
         }

         .table-wrapper ::-webkit-scrollbar-track {
             border-radius: 10px;
             background-color: #ffffff;
         }
    }

</style>

<div class="panel">
    <div class="panel-body" style="position: relative;">
        <bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.institutionId=<?= $institution_id ?>;$ctrl.exportexcel='<?=$excelUrl ?>';" float-btn="true">
            <bg-pane class="main-content">
                <div class="alert {{class}}" ng-hide="message == null">
                    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
                </div>

                <div id="institution-student-attendances-table" class="table-wrapper">
                    <div ng-if="$ctrl.gridReady" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
                </div>
            </bg-pane>

            <bg-pane class="" min-size-p="0" max-size-p="30" size-p="0">
                <div class="split-content-area">
                    <h5 ng-if="$ctrl.isMarkableSubjectAttendance==true"><?= __('Subjects') ?>: </h5>
                    <div class="input-select-wrapper" ng-if="$ctrl.isMarkableSubjectAttendance==true">
                        <select ng-disabled="$ctrl.action=='edit'" name="subject" ng-options="subject.id as subject.name for subject in $ctrl.subjectListOptions" ng-model="$ctrl.selectedSubject" ng-change="$ctrl.changeSubject();">
                            <option value="" ng-if="$ctrl.subjectListOptions.length == 0"><?= __('No Options') ?></option>
                        </select>
                    </div>
                </div>
            </bg-pane>
        </bg-splitter>
    </div>
</div>


<?php
$this->end();
?>
