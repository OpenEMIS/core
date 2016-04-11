<div class="input select">
	<label for="assessmentitems-education-grade-id"><?= __('Education Grade')?></label>

	<div class="input-select-wrapper">
		<select id="assessmentitems-education-grade-id" name="Assessments[education_grade_id]" kd-on-change-element="data" kd-on-change-source-url="{{baseUrl}}/restful/education-educationgradessubjects.json?_finder=visible&_contain=EducationSubjects&_fields=id&education_grade_id=" kd-on-change-target="assessment_items">

			<option value="">-- Select --</option>
			<option ng:repeat="option in onChangeTargets.education_grade_id" value="{{option.id}}">
			    {{option.name}}
			</option>
		
		</select>
	</div>
</div>
