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

	<div class="alert {{StudentExaminationResultsController.class}}" ng-hide="StudentExaminationResultsController.message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{StudentExaminationResultsController.message}}
	</div>
	
	<div class="row section-header" ng-repeat-start="section in StudentExaminationResultsController.sections | orderBy:'order'" ng-show={{section.visible}}>
		{{section.name}}
	</div>
	<div ng-repeat-end class="table-wrapper" id="student-examination-result-table_{{section.id}}">
		<div ng-if="StudentExaminationResultsController.gridOptions[section.id]" ag-grid="StudentExaminationResultsController.gridOptions[section.id]" class="ag-fresh"></div>
	</div>
<?php
$this->end();
?>
