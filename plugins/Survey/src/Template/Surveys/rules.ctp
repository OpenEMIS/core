<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Survey.angular/rules/survey.rules.svc', ['block' => true]); ?>
<?= $this->Html->script('Survey.angular/rules/survey.rules.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
$this->end();

$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
?>
	<div class="alert {{class}}" ng-hide="message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
	</div>
		<div class="toolbar-responsive panel-toolbar">
			<div class="toolbar-wrapper">
				<div class="input select">
					<div class="input-select-wrapper">
						<select class="form-control" ng-options="item.text for item in SurveyRulesController.surveyFormOptions track by item.value" ng-model="selectSurveyForms"></select>
					</div>
				</div>
			</div>
		</div>
		<div id="survey-rules-table" class="table-wrapper">

			<!-- <div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh ag-height-fixed"></div> -->
		</div>
	</div>

<?php
$this->end();
?>
