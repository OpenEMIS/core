<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

// this table should not be used anymore, please refer to InstitutionGradesTable.php

class InstitutionSiteGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', 			['className' => 'Education.EducationGrades']);
		$this->belongsTo('InstitutionSiteProgrammes',	['className' => 'Institution.InstitutionSiteProgrammes']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {

		// $this->fields['name']['type'] = 'string';
		// $this->fields['academic_period_id']['type'] = 'select';		
		// $this->fields['start_time']['type'] = 'string';
		// $this->fields['end_time']['type'] = 'string';


		// $this->fields['name']['order'] = 0;
		// $this->fields['academic_period_id']['order'] = 1;		
		// $this->fields['start_time']['order'] = 2;
		// $this->fields['end_time']['order'] = 3;
		// $this->fields['location_institution_site_id']['order'] = 4;

	}

	public function addEditBeforeAction($event) {

		// $this->fields['location_institution_site_id']['visible'] = false;

	}

	public function getInstitutionSiteGradeOptions($institutionsId, $academicPeriodId, $listOnly=true) {
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id = ' . $institutionsId
		);
		$conditions = $this->InstitutionSiteProgrammes->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);
		$query = $this->InstitutionSiteProgrammes->find()->where($conditions)->select('id');
		$data = $query->toArray();
		$institutionSiteProgrammesId = [];
		foreach ($data as $entity) {
			$institutionSiteProgrammesId[] = $entity->id;
		}

		$query = $this->find('all')
					->contain(['EducationGrades'])
					->where([
						'institution_site_programme_id IN' => $institutionSiteProgrammesId
					])
					->order(['EducationGrades.education_programme_id', 'EducationGrades.order'])
					;
		$data = $query->toArray();
		if($listOnly) {
			$list = [];
			foreach ($data as $key => $obj) {
				$list[$obj->education_grade->id] = $obj->education_grade->programme_grade_name;
			}

			return $list;
		} else {
			return $data;
		}
	}

	public function getGradeOptions($institutionsId, $academicPeriodId, $programmeId=0) {
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId,
			'InstitutionSiteProgrammes.education_programme_id' => $programmeId
		);
		$conditions = $this->InstitutionSiteProgrammes->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);
		$query = $this->InstitutionSiteProgrammes->find()->where($conditions)->select('id');
		$data = $query->toArray();
		$institutionSiteProgrammesId = [];
		foreach ($data as $entity) {
			$institutionSiteProgrammesId[] = $entity->id;
		}

		$query = $this->find('all')
					->contain(['EducationGrades'])
					->where([
						'institution_site_programme_id IN' => $institutionSiteProgrammesId
					])
					->order(['EducationGrades.education_programme_id', 'EducationGrades.order'])
					;

		$list = array();
		foreach ($query as $key => $obj) {
			$list[$obj->education_grade->id] = $obj->education_grade->name;
		}

		return $list;
	}
	
}
