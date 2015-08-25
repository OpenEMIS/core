<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionGradesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_grades');
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', 			['className' => 'Education.EducationGrades']);
		// should not need to link to site programme anymore
		$this->belongsTo('InstitutionSiteProgrammes',	['className' => 'Institution.InstitutionSiteProgrammes']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		
		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->allowEmpty('end_date')
 			->add('end_date', 'ruleCompareDateReverse', [
					'rule' => ['compareDateReverse', 'start_date', false]
				])
 			;
		return $validator;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->field('institution_site_programme_id', ['type' => 'hidden']);
		$this->ControllerAction->field('level');
		$this->ControllerAction->field('programme');
		$this->ControllerAction->field('education_grade_id');

		if ($this->action == 'add') {
			$this->ControllerAction->setFieldOrder([
				'level', 'programme', 'start_date', 'end_date', 'education_grade_id'
			]);
		} else if ($this->action == 'index') {
			$this->ControllerAction->setFieldOrder([
				'education_grade_id', 'programme', 'level', 'start_date', 'end_date'
			]);
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
		$query->order(['EducationLevels.order', 'EducationCycles.order', 'EducationProgrammes.order', 'EducationGrades.order']);
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$process = function($model, $entity) use ($data) {
			$startDate = $entity->start_date;
			$institutionId = $entity->institution_site_id;
			if ($data->offsetExists('grades')) {
				// will need to remove institution_site_programme_id soon
				$programmeId = $entity->programme;
				$programmeEntity = $this->getSiteProgrammeEntity($institutionId, $programmeId, $startDate);

				$grades = $data['grades'];
				if ($entity->has('end_date')) {
					// if end date is earlier than start date, just set end date to be same as start date
					if (strtotime($startDate) > strtotime($entity->end_date)) {
						$entity->end_date = date('Y-m-d', strtotime($startDate));
					}
				}
				foreach ($grades as $grade) {
					if ($grade['education_grade_id'] != 0) {
						$grade['start_date'] = $startDate;
						$grade['institution_site_id'] = $institutionId;
						$grade['start_date'] = $startDate;
						if ($entity->has('end_date')) {
							$grade['end_date'] = $entity->end_date;
						}
						$grade['institution_site_programme_id'] = $programmeEntity->id; // to be removed

						$newEntity = $this->newEntity($grade);
						$this->save($newEntity);
					}
				}
			}
			return true;
		};
		return $process;
	}

	// remove this function when institution_site_programmes is dropped
	private function getSiteProgrammeEntity($institutionId, $programmeId, $startDate) {
		$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$entity = $InstitutionSiteProgrammes->find()
		->where([
			$InstitutionSiteProgrammes->aliasField('institution_site_id') => $institutionId,
			$InstitutionSiteProgrammes->aliasField('education_programme_id') => $programmeId
		])
		->first();

		if (is_null($entity)) {
			$newEntity = $InstitutionSiteProgrammes->newEntity([
				'institution_site_id' => $institutionId,
				'education_programme_id' => $programmeId,
				'start_date' => $startDate
			]);
			$entity = $InstitutionSiteProgrammes->save($newEntity);
		}
		return $entity;
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
		// $cycle = $entity->education_grade->education_programme->education_cycle->name;
		$programme = $entity->education_grade->education_programme->cycle_programme_name;
		$this->fields['level']['attr']['value'] = $level;
		$this->fields['programme']['attr']['value'] = $programme;
		$this->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;
		// pr($entity);
	}

	public function onGetLevel(Event $event, Entity $entity) {
		$level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
		return $level;
	}

	public function onGetProgramme(Event $event, Entity $entity) {
		return $programme = $entity->education_grade->education_programme->cycle_programme_name;;
	}

	public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$EducationLevels = TableRegistry::get('Education.EducationLevels');
			$levelOptions = $EducationLevels->find('list', ['valueField' => 'system_level_name'])
			->find('visible')
			->find('order')
			->toArray();
			$attr['empty'] = true;
			$attr['options'] = $levelOptions;
			$attr['onChangeReload'] = 'changeLevel';
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function onUpdateFieldProgramme(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['empty'] = true;
			$attr['options'] = [];
			if ($this->request->is(['post', 'put'])) {
				$levelId = $this->request->data($this->aliasField('level'));
				$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
				$query = $EducationProgrammes->find('list', ['valueField' => 'cycle_programme_name'])
				->find('visible')
				->find('order')
				->matching('EducationCycles', function($q) use ($levelId) {
					return $q->find('visible')->where(['EducationCycles.education_level_id' => $levelId]);
				});

				$programmeOptions = $query->toArray();
				$attr['options'] = $programmeOptions;
				$attr['onChangeReload'] = 'changeProgramme';
			}
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function addOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$data[$this->alias()]['programme'] = 0;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['type'] = 'element';
			$attr['element'] = 'Institution.Programmes/grades';
			if ($request->is(['post', 'put'])) {
				$programmeId = $request->data($this->aliasField('programme'));

				if (empty($programmeId)) {
					$programmeId = 0;
				}
				$data = $this->EducationGrades->find()
				->find('visible')
				->find('order')
				->where(['EducationGrades.education_programme_id' => $programmeId])
				->all();

				$institutionId = $this->Session->read('Institutions.id');
				$exists = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
				->where([$this->aliasField('institution_site_id') => $institutionId])
				->toArray();

				$attr['data'] = $data;
				$attr['exists'] = $exists;
			}
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function getConditionsByAcademicPeriodId($academicPeriodId=0, $conditions=[]) {
		$modelConditions = [];
		if($academicPeriodId > 0) {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriodObj = $AcademicPeriod->get($academicPeriodId);
			$startDate = $AcademicPeriod->getDate($academicPeriodObj->start_date);
			$endDate = $AcademicPeriod->getDate($academicPeriodObj->end_date);

			$modelConditions['OR'] = array(
				'OR' => array(
					array(
						'end_date IS NOT NULL',
						'start_date <= "' . $startDate . '"',
						'end_date >= "' . $startDate . '"'
					),
					array(
						'end_date IS NOT NULL',
						'start_date <= "' . $endDate . '"',
						'end_date >= "' . $endDate . '"'
					),
					array(
						'end_date IS NOT NULL',
						'start_date >= "' . $startDate . '"',
						'end_date <= "' . $endDate . '"'
					)
				),
				array(
					'end_date IS NULL',
					'start_date <= "' . $endDate . '"'
				)
			);
		}

		$conditions = array_merge($conditions, $modelConditions);

		return $conditions;
	}
	
	public function getGradeOptions($institutionsId, $academicPeriodId, $listOnly=true) {
		$conditions = array(
			'InstitutionGrades.institution_site_id = ' . $institutionsId
		);
		$conditions = $this->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);
		$query = $this->find('all')
					->contain(['EducationGrades'])
					->where($conditions)
					->order(['EducationGrades.education_programme_id', 'EducationGrades.order']);
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

	/**
	 * Used by InstitutionSiteSectionsTable & InstitutionSiteClassesTable.
	 * This function resides here instead of inside AcademicPeriodsTable because the first query is to get 'start_date' and 'end_date' 
	 * of registered Programmes in the Institution. 
	 * @param  integer $model           		 [description]
	 * @param  array   $conditions               [description]
	 * @return [type]                            [description]
	 */
	public function getAcademicPeriodOptions($Alert, $conditions=[]) {
		$query = $this->find('all')
					->select(['start_date', 'end_date'])
					->where($conditions)
					;
		$result = $query->toArray();
		$startDateObject = null;
		foreach ($result as $key=>$value) {
			$startDateObject = $this->getLowerDate($startDateObject, $value->start_date);
		}
		if (is_object($startDateObject)) {
			$startDate = $startDateObject->toDateString();
		} else {
			$startDate = $startDateObject;
		}

		$endDateObject = null;
		foreach ($result as $key=>$value) {
			$endDateObject = $this->getHigherDate($endDateObject, $value->end_date);
		}
		if (is_object($endDateObject)) {
			$endDate = $endDateObject->toDateString();
		} else {
			$endDate = $endDateObject;
		}

		$conditions = array_merge(array('end_date IS NULL'), $conditions);
		$query = $this->find('all')
					->where($conditions)
					;
		$nullDate = $query->count();

		$academicPeriodConditions = [];
		$academicPeriodConditions['parent_id >'] = 0;
		$academicPeriodConditions['end_date >='] = $startDate;
		if($nullDate == 0) {
			$academicPeriodConditions['start_date <='] = $endDate;
		} else {
			$academicPeriodConditions['end_date >='] = $startDate;
		}

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$query = $AcademicPeriods->find('list')
					->select(['id', 'name'])
					->where($academicPeriodConditions)
					->order('`order`')
					;
		$result = $query->toArray();
		if (empty($result)) {
			$Alert->warning('Institution.Institutions.noProgrammes');
			return [];
		} else {
			return $result;
		}
	}

	/**
	 * Used by $this->getAcademicPeriodOptions()
	 * @param  Time $a Time object
	 * @param  Time $b Time object
	 * @return Time    Time object
	 */
	private function getLowerDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		if (is_null($b)) {
			return $a;
		}
		return (($a->toUnixString() <= $b->toUnixString()) ? $a : $b);
	}

	/**
	 * Used by $this->getAcademicPeriodOptions()
	 * @param  Time $a Time object
	 * @param  Time $b Time object
	 * @return Time    Time object
	 */
	private function getHigherDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		if (is_null($b)) {
			return $a;
		}
		return (($a->toUnixString() >= $b->toUnixString()) ? $a : $b);
	}

}
