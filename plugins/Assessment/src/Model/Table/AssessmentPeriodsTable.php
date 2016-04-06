<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\Validation\Validator;

class AssessmentPeriodsTable extends AssessmentsAppTable {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);

		$this->fields['id']['type'] = 'hidden';
		$this->fields['assessment_id']['type'] = 'hidden';
		$this->fields['weight']['type'] = 'string';
		$this->fields['code']['required'] = true;
		$this->fields['name']['required'] = true;
		$this->fields['start_date']['label'] = false;
		$this->fields['start_date']['required'] = true;
		$this->fields['end_date']['label'] = false;
		$this->fields['end_date']['required'] = true;
		$this->fields['date_enabled']['label'] = false;
		$this->fields['date_enabled']['required'] = true;
		$this->fields['date_disabled']['label'] = false;
		$this->fields['date_disabled']['required'] = true;
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
			'code' => 'codi',
		    'name' => 'nama',
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