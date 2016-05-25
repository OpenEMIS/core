<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>

<?php
$this->end();
$this->start('panelBody');
?>
	<div class="alert {{class}}" ng-hide="message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
	</div>

	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<div class="input select">
				<div class="input-select-wrapper">
					<select name="StudentResults[academic_period_id]" id="studentresults-academic-period-id" class="form-control" ng-options="item.value as item.text for item in StudentResultsController.periodOptions" ng-model="StudentResultsController.academicPeriodId" ng-change="StudentResultsController.onChangePeriod(StudentResultsController.academicPeriodId)">
						<option value="">-- <?= __('Select Period') ?> --</option>
					</select>
				</div>
			</div>

			<div class="input select">
				<div class="input-select-wrapper">
					<select name="StudentResults[assessment_id]" id="studentresults-assessment-id" class="form-control" ng-options="item.value as item.text for item in StudentResultsController.assessmentOptions" ng-model="StudentResultsController.assessmentId" ng-change="StudentResultsController.onChangeAssessment(StudentResultsController.assessmentId)">
						<option value="">-- <?= __('Select Assessment') ?> --</option>
					</select>
				</div>
			</div>
		</div>
	</div>

	<div id="student-result-table" class="table-wrapper">
		<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh ag-height-fixed"></div>
	</div>
<?php
$this->end();
?>
