<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\Validation\Validator;

class AssessmentPeriodsTable extends AssessmentsAppTable {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);

		$this->fields['id']['label'] = false;
		$this->fields['id']['type'] = 'hidden';
		$this->fields['id']['fieldName'] = "Assessments[assessment_periods][{{key}}][id]";
		$this->fields['id']['attr']['value'] = "{{showValue(period.id)}}";

		$this->fields['assessment_id']['label'] = false;
		$this->fields['assessment_id']['type'] = 'hidden';
		$this->fields['assessment_id']['fieldName'] = "Assessments[assessment_periods][{{key}}][assessment_id]";
		$this->fields['assessment_id']['attr']['value'] = "{{showValue(period.assessment_id)}}";

		$this->fields['weight']['label'] = false;
		$this->fields['weight']['type'] = 'string';
		$this->fields['weight']['fieldName'] = "Assessments[assessment_periods][{{key}}][weight]";
		$this->fields['weight']['attr']['value'] = "{{showValue(period.weight)}}";

		$this->fields['code']['label'] = false;
		$this->fields['code']['required'] = true;
		$this->fields['code']['fieldName'] = "Assessments[assessment_periods][{{key}}][code]";
		$this->fields['code']['attr']['value'] = "{{showValue(period.code)}}";

		$this->fields['name']['label'] = false;
		$this->fields['name']['required'] = true;
		$this->fields['name']['fieldName'] = "Assessments[assessment_periods][{{key}}][name]";
		$this->fields['name']['attr']['value'] = "{{showValue(period.name)}}";

		$_options = [
			'format' => 'dd-mm-yyyy',
			'todayBtn' => 'linked',
			'orientation' => 'auto',
			'autoclose' => true,
		];
		$this->fields['start_date']['id'] = "Assessments-assessment_periods-start_date";
		$this->fields['start_date']['label'] = false;
		$this->fields['start_date']['required'] = true;
		// $this->fields['start_date']['inputWrapperStyle'] = 'margin-top:1px;margin-bottom:-2px;';
		$this->fields['start_date']['fieldName'] = "Assessments[assessment_periods][{{key}}][start_date]";
		$this->fields['start_date']['attr']['value'] = "{{showValue(period.start_date)}}";
		$this->fields['start_date']['date_options'] = $_options;

		$this->fields['end_date']['id'] = "Assessments-assessment_periods-end_date";
		$this->fields['end_date']['label'] = false;
		$this->fields['end_date']['required'] = true;
		// $this->fields['end_date']['inputWrapperStyle'] = 'margin-top:1px;margin-bottom:-2px;';
		$this->fields['end_date']['fieldName'] = "Assessments[assessment_periods][{{key}}][end_date]";
		$this->fields['end_date']['attr']['value'] = "{{showValue(period.end_date)}}";
		$this->fields['end_date']['date_options'] = $_options;

		$this->fields['date_enabled']['id'] = "Assessments-assessment_periods-date_enabled";
		$this->fields['date_enabled']['label'] = false;
		$this->fields['date_enabled']['required'] = true;
		// $this->fields['date_enabled']['inputWrapperStyle'] = 'margin-top:1px;margin-bottom:-2px;';
		$this->fields['date_enabled']['fieldName'] = "Assessments[assessment_periods][{{key}}][date_enabled]";
		$this->fields['date_enabled']['attr']['value'] = "{{showValue(period.date_enabled)}}";
		$this->fields['date_enabled']['date_options'] = $_options;

		$this->fields['date_disabled']['id'] = "Assessments-assessment_periods-date_disabled";
		$this->fields['date_disabled']['label'] = false;
		$this->fields['date_disabled']['required'] = true;
		// $this->fields['date_disabled']['inputWrapperStyle'] = 'margin-top:1px;margin-bottom:-2px;';
		$this->fields['date_disabled']['fieldName'] = "Assessments[assessment_periods][{{key}}][date_disabled]";
		$this->fields['date_disabled']['attr']['value'] = "{{showValue(period.date_disabled)}}";
		$this->fields['date_disabled']['date_options'] = $_options;

	}

	public function getFormFields($action = 'edit') {
		if ($action=='add') {
			return ['code'=>'', 'name'=>'', 'start_date'=>'', 'end_date'=>'', 'date_enabled'=>'', 'date_disabled'=>'', 'weight'=>''];
		} else if ($action=='edit') {
			return ['code'=>'', 'name'=>'', 'start_date'=>'', 'end_date'=>'', 'date_enabled'=>'', 'date_disabled'=>'', 'weight'=>'', 'assessment_id'=>'', 'id'=>''];
		} else {
			return ['code'=>'', 'name'=>'', 'start_date'=>'', 'end_date'=>'', 'date_enabled'=>'', 'date_disabled'=>'', 'weight'=>''];
		}
	}

	public function addNewAssessmentPeriod() {
		foreach ($this->fields as $key => $field) {
			$this->fields[$key]['errors'] = [];
			$this->fields[$key]['value'] = '';
		}
		return [
		    'id' => $this->fields['id'],
			'code' => $this->fields['code'],
		    'name' => $this->fields['name'],
		    'start_date' => $this->fields['start_date'],
		    'end_date' => $this->fields['end_date'],
		    'date_enabled' => $this->fields['date_enabled'],
		    'date_disabled' => $this->fields['date_disabled'],
		    'weight' => $this->fields['weight'],
		    'assessment_id' => $this->fields['assessment_id'],
		];
	}

	public function appendAssessmentPeriodsArray(Entity $entity, array $addedAssessmentPeriods = []) {
		$assessmentPeriods = $addedAssessmentPeriods;
		$assessmentPeriods[] = [
		    'id' => '',
			'code' => '',
		    'name' => '',
		    'start_date' => '',
		    'end_date' => '',
		    'date_enabled' => '',
		    'date_disabled' => '',
		    'weight' => '',
		    'assessment_id' => $entity->id,
		];
		return $assessmentPeriods;
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('assessment_id', 'update')
			->requirePresence('name')
			->add('code', 'ruleUniqueCode', [
			    'rule' => ['checkUniqueCode', 'assessment_id'],
			    'last' => true
			])
			->add('code', 'ruleUniqueCodeWithinForm', [
			    'rule' => ['checkUniqueCodeWithinForm', $this->Assessments],
			])
			->add('start_date', 'ruleInParentAcademicPeriod', [
			    'rule' => ['inParentAcademicPeriod', $this->Assessments],
			])
			->add('end_date', 'ruleInParentAcademicPeriod', [
			    'rule' => ['inParentAcademicPeriod', $this->Assessments],
			])
			;
		return $validator;
	}
}
