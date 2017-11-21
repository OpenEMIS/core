<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/examination_results/student.examination_results.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/examination_results/student.examination_results.ctrl', ['block' => true]); ?>

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
