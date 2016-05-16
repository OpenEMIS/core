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
					<select name="StaffPositionProfiles[staff_change_type_id]" id="staffpositionprofiles-staff-change-type-id" ng-options="item.value as item.text for item in SurveyRulesController.surveySectionOptions" ng-model="SurveyRulesController.sectionName" ng-change="SurveyRulesController.onChangeSection(SurveyRulesController.sectionName)">
					</select>
				</div>
			</div>
				<form method="post" accept-charset="utf-8" id="content-main-form" novalidate="novalidate" action="/openemis-phpoe/Surveys/Forms?module=1" class="ng-pristine ng-valid"><div style="display:none;"><input type="hidden" name="_method" value="POST"></div>
					<div class="table-wrapper">
						<div class="table-responsive">
							
							<table class="table table-curved table-sortable table-checkable">
								<thead>
									<tr>
										<th><?= __('Enable')?></th>
										<th><?= __('Dependent On')?></th>
										<th><?= __('Show If')?></th>
									</tr>
								</thead>
									<tr ng-repeat-start="question in SurveyRulesController.surveyQuestions">
										<td colspan="3"><div class="section-header">{{question.no}}. {{question.name}}</div></td>
									</tr>
									<tr ng-repeat-end>
										<td>
										<input 
											type="hidden" 
											ng-init="SurveyRulesController.questionId[question.no] = question.survey_question_id"
											ng-model="SurveyRulesController.questionId[question.no]" />
										<input 
											type="hidden" 
											ng-init="SurveyRulesController.ruleId[question.no] = question.rule.id"
											ng-model="SurveyRulesController.ruleId[question.no]"/>
										<input 
											type="checkbox" 
											ng-true-value="1" 
											ng-false-value="0" 
											ng-model="SurveyRulesController.enabled[question.no]" 
											ng-init="SurveyRulesController.enabled[question.no] = 0; SurveyRulesController.initEnabled(question);"></td>
										<td>
											<div class="input-select-wrapper">
												<select 
													ng-options="item.survey_question_id as item.short_name for item in SurveyRulesController.surveyQuestions | filter:SurveyRulesController.filterByOrderAndType({{question.order}})" 
													ng-model="SurveyRulesController.dependentQuestion[question.no]" 
													ng-change="SurveyRulesController.populateOptions(question.rule.dependent_question_id)"
													ng-init="SurveyRulesController.dependentQuestion[question.no] = 0; SurveyRulesController.populateOptions(SurveyRulesController.dependentQuestion[question.no]); SurveyRulesController.initDependentQuestion(question);">
													<option value="">-- <?= __('Select One') ?> --</option>
												</select>
											</div>
										</td>
										<td>
											<div class="input-select-wrapper">
												<select 
													chosen multiple options="SurveyRulesController.questionOptions" 
													ng-model="SurveyRulesController.dependentOptions[question.no]" 
													ng-options="item.survey_question_choice_id as item.survey_question_choice_name for item in SurveyRulesController.questionOptions | filter:SurveyRulesController.filterChoiceBySurveyQuestionId(SurveyRulesController.dependentQuestion[question.no])"
													ng-init="SurveyRulesController.dependentOptions[question.no] = question.rule.show_options">
												</select>
											</div>
										</td>
									</tr>
							</table>

							<div class="form-buttons" style="text-align: center"><button class="btn btn-default btn-save" value="save" type="button" ng-click="SurveyRulesController.saveValue()"><i class="fa fa-check"></i> Save</button></div>
						</div>
					</div>
				</form>
		</div>
	</form>

<?php
$this->end();
?>
