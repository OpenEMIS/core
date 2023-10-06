<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/examination_results/student.examination_results.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/examination_results/student.examination_results.ctrl', ['block' => true]); ?>
<?php
$this->start('toolbar');
?>
	<a href="javascript:void(0)" ng-show="$ctrl.action == 'view'">
        <button ng-click="$ctrl.ExportTimetable()" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Download') ?>" >
            <i class="fa kd-download" ></i>
        </button>
    </a>
    <?php /*************** Start POCOR-5188 */ ?>
    <?php 
        if(!empty($is_manual_exist)):
    ?>

    <a href="<?php echo $is_manual_exist['url']; ?>" target="_blank">
        <button  class="btn btn-xs btn-default icon-big"  data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Help') ?>" >
        <i class="fa fa-question-circle"></i>
        </button>
    </a>
    <?php endif ?>
    <?php /*************** End POCOR-5188 */ ?>
    
<?php
$this->end();
?>
<?php
$this->start('panelBody');
?>

	<style type='text/css'>
	    .ag-grid-dir-ltr {
	        direction: ltr !important;
	    }
	</style>

	<div class="alert {{class}}" ng-hide="message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
	</div>

	<div class="row section-header" ng-repeat-start="section in StudentExaminationResultsController.sections | orderBy:'order'" ng-show={{section.visible}}>
		{{section.name}}
	</div>
	<div ng-repeat-end class="table-wrapper" id="student-examination-result-table_{{section.id}}">
		<div ng-if="StudentExaminationResultsController.gridOptions[section.id]" kd-ag-grid="StudentExaminationResultsController.gridOptions[section.id]" class="ag-height-fixed"></div>
	</div>
<?php
$this->end();
?>
