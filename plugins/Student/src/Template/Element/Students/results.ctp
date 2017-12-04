<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.ctrl', ['block' => true]); ?>

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

	<div class="row section-header" ng-repeat-start="section in StudentResultsController.sections | orderBy:'order'" ng-show={{section.visible}}>
		{{section.name}}
	</div>
	<div ng-repeat-end class="table-wrapper" id="student-result-table_{{section.id}}">
		<div ng-if="StudentResultsController.gridOptions[section.id]" kd-ag-grid="StudentResultsController.gridOptions[section.id]" class="ag-height-fixed"></div>
	</div>
<?php
$this->end();
?>
