<div class="input select required">
	<label for="assessmentitems-education-grade-id"><?= __('Education Grade')?></label>

	<div class="input-select-wrapper">
		<select id="assessmentitems-education-grade-id" name="Assessments[education_grade_id]" kd-on-change-element="data" kd-on-change-source-url="{{baseUrl}}/restful/Education-EducationGradesSubjects.json?_finder=visible&_contain=EducationSubjects&_fields=id&education_grade_id=" kd-on-change-target="assessment_items" kd-on-change-spinner-parent="table_assessment_items" kd-selected-value="<?= $data->education_grade_id ?>">

			<option value=""><?= __('-- Select --')?></option>
			<option ng:repeat="option in onChangeTargets.education_grade_id" value="{{option.id}}" ng-selected="selectedOption('assessmentitems-education-grade-id', option.id)">
			    {{option.name}}
			</option>
		
		</select>
	</div>
</div>
