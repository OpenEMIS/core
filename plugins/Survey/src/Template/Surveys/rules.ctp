<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Survey.angular/rules/survey.rules.svc', ['block' => true]); ?>
<?= $this->Html->script('Survey.angular/rules/survey.rules.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
	<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Add');?>" ng-show="action == 'index'" ng-click="SurveyRulesController.onAddClick()">
		<i class="fa kd-add"></i>
	</button>
<?php
$this->end();
$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
?>
	<div class="alert {{class}}" ng-hide="message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
	</div>
		<!-- <div class="toolbar-responsive panel-toolbar">
			<div class="toolbar-wrapper">
				<div class="input select">
					<div class="input-select-wrapper">
						<select class="form-control" ng-options="item.value as item.text for item in SurveyRulesController.surveyFormOptions" ng-model="SurveyRulesController.filters" ng-change="SurveyRulesController.update(SurveyRulesController.filters)"></select>
					</div>
				</div>
			</div>
		</div> -->
		<form method="post" accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" action="">
			<div style="display:none;"></div>
			<div class="input text">
				<label for="surveyrules-survey-form-id"><?= __('Survey Form')?></label>
				<input type="text" name="SurveyRules[survey_form_id]" disabled="disabled" id="surveyrules-survey-form-id" ng-model="SurveyRulesController.surveyFormName" />
				<input type="hidden" name="SurveyRules[survey_form_id]" id="surveyrules-survey-form-id" ng-value="SurveyRulesController.surveyFormId" />
			</div>
			<div class="input select">
				<label for="surveyrules-survey-form-section-name"><?= __('Survey Form Section')?></label>
				<div class="input-select-wrapper">
					<select name="StaffPositionProfiles[staff_change_type_id]" id="staffpositionprofiles-staff-change-type-id" ng-options="item.value as item.text for item in SurveyRulesController.surveySectionOptions" ng-model="SurveyRulesController.sectionFilters" ng-change="SurveyRulesController.update(SurveyRulesController.sectionFilters)">
					</select>
				</div>
			</div>
				<!-- <form method="post" accept-charset="utf-8" id="content-main-form" novalidate="novalidate" action="/openemis-phpoe/Surveys/Forms?module=1" class="ng-pristine ng-valid"><div style="display:none;"><input type="hidden" name="_method" value="POST"></div>
					<div class="table-wrapper">
						<div class="table-responsive">
							<table class="table table-curved table-sortable table-checkable">
								<thead>
									<tr>
										<th>Question</th>
										<th>Dependent On</th>
										<th>Show If</th>
										<th>Action</th>
									</tr>
								</thead>
									<tr>
										<td>sdsd</td>
										<td>sdsd</td>
										<td>sdsd</td>
									</tr>
							</table>
						</div>
					</div>
				</form> -->

			<div id="survey-rules-table" class="table-wrapper">
				<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh ag-height-fixed"></div>
			</div>
		</div>
	</form>

<?php
$this->end();
?>
