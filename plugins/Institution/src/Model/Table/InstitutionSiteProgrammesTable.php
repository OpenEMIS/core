<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionSiteProgrammesTable extends AppTable {
	public $institutionId = 0;
	private $_levelOptions;
	private $_programmeOptions;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		$this->hasMany('InstitutionSiteGrades',	['className' => 'Institution.InstitutionSiteGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

		/**
		 * Short cuts to initialised models set in relations.
		 * By using initialised models set in relations, the relation's className is set at a single place.
		 * In add operation, these models attributes are empty by default.
		 */
		$this->EducationLevels = $this->EducationProgrammes->EducationCycles->EducationLevels;
		$this->EducationGrades = $this->EducationProgrammes->EducationGrades;
		$this->AcademicPeriods = $this->Institutions->InstitutionSiteShifts->AcademicPeriods;

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function validationDefault(Validator $validator) {
		$validator
	        ->allowEmpty('end_date')
 	        ->add('end_date', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'start_date', false]
	    	    ])
 	    	->add('education_programme_id', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'institution_site_id']],
			        'provider' => 'table'
			    ]
		    ]);
		return $validator;
	}

	public function beforeAction(Event $event) {
		/**
		 * Set default_date to false to show a blank date input on page load
		 */
		$this->ControllerAction->field('end_date', ['default_date' => false]);
		
		$this->ControllerAction->field('education_level', ['type' => 'select', 'onChangeReload' => true]);
		$this->ControllerAction->field('education_programme_id', ['type' => 'select', 'onChangeReload' => true]);
		$this->ControllerAction->field('education_grade', ['type' => 'element', 'element' => 'Institution.Programmes/grades']);

		$this->ControllerAction->setFieldOrder([
			'education_programme_id', 'education_level', 'start_date', 'end_date', 'education_grade',
		]);
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		// $this->fields['education_grade']['visible'] = false;
		$this->fields['education_programme_id']['type'] = 'string';
		$this->fields['education_level']['type'] = 'string';
		unset($this->fields['education_level']['options']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['InstitutionSiteGrades.EducationGrades']);
	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeAction(Event $event) {
		$this->fields['education_level']['type'] = 'text';
		$this->ControllerAction->setFieldOrder([
			'education_level', 'education_programme_id', 'start_date', 'end_date', 'education_grade',
		]);

	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'EducationProgrammes.EducationCycles.EducationLevels.EducationSystems',
			'InstitutionSiteGrades.EducationGrades'
		]);
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
		return $entity;
    }


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event) {

		$this->ControllerAction->setFieldOrder([
			'education_level', 'education_programme_id', 
			'start_date', 'end_date', 'education_grade',
		]);

	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (isset($data[$this->alias()]['institution_site_grades']) && count($data[$this->alias()]['institution_site_grades']>0)) {
			foreach($data[$this->alias()]['institution_site_grades'] as $key => $row) {
				if (isset($row['education_grade_id'])) {
					$data[$this->alias()]['institution_site_grades'][$key]['institution_site_id'] = $data[$this->alias()]['institution_site_id'];
					$data[$this->alias()]['institution_site_grades'][$key]['start_date'] = $data[$this->alias()]['start_date'];
					$data[$this->alias()]['institution_site_grades'][$key]['end_date'] = $data[$this->alias()]['end_date'];
				} else {
					if ($row['id']!='') {
						$data[$this->alias()]['institution_site_grades'][$key]['status'] = 0;
					} else {
						unset($data[$this->alias()]['institution_site_grades'][$key]);
					}
				}
			}
		} else {
			/**
			 * set institution_site_id to empty to trigger validation error in ControllerActionComponent
			 */
			$data[$this->alias()]['institution_site_id'] = '';
			$this->Alert->error('Institution.'.$this->alias().'.noGrade');
		}
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'EducationProgrammes',
			'InstitutionSiteGrades'
		]);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$recorded = [];
		$selected = [];
		foreach ($entity->institution_site_grades as $key=>$value) {
			$recorded[$value->education_grade_id] = $value->id;
			if ($value->status==1) {
				$selected[$value->education_grade_id] = true;
			}
		}
		$this->fields['education_grade']['recorded'] = $recorded;
		$this->fields['education_grade']['selected'] = $selected;

		if (count($this->request->data)>0 && $this->request->data['submit']=='reload') {
			// pr('submit reload');
		} else {

			$levelId = $entity->education_programme->education_cycle_id;
			$this->fields['education_level']['attr']['value'] = $levelId;

			$programmeOptions = $this->EducationProgrammes
				->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
				->find('withCycle')
				->where(['EducationCycles.education_level_id' => $levelId])
				->toArray();
			$this->fields['education_programme_id']['options'] = $programmeOptions;
			$this->fields['education_programme_id']['attr']['value'] = $entity->education_programme_id;

			$gradeData = $this->EducationGrades->find()
				->find('visible')->find('order')
				->where([$this->EducationGrades->aliasField('education_programme_id') => $entity->education_programme_id])
				->all();
			$this->fields['education_grade']['data'] = $gradeData;
			if (count($gradeData)==0) {
				$this->Alert->warning('InstitutionSiteProgrammes.noEducationGrades');
			}

		}
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addAfterAction(Event $event, Entity $entity) {
		/**
		 * @todo - To be done in a dynamic way so that the format is consistent throughout the whole system.
		 */
		$this->fields['start_date']['value'] = date('d-m-Y');
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onUpdateFieldEducationLevel(Event $event, array $attr, $action, $request) {
		$this->_levelOptions = $this->EducationLevels
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('withSystem')
			->toArray();
		$attr['options'] = $this->_levelOptions;

		if (count($this->_levelOptions)==0) {
			$this->Alert->warning('InstitutionSiteProgrammes.noEducationLevels');
		}
		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$levelId = $this->postString('education_level', $this->_levelOptions);
			$this->_programmeOptions = $this->EducationProgrammes
				->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
				->find('withCycle')
				->where(['EducationCycles.education_level_id' => $levelId])
				->toArray();
			$attr['options'] = $this->_programmeOptions;

			if (count($this->_programmeOptions)==0) {
				$this->Alert->warning('InstitutionSiteProgrammes.noEducationProgrammes');
			}
		}
		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$programmeId = $this->postString('education_programme_id', $this->_programmeOptions);
			$gradeData = $this->EducationGrades->find()
				->find('visible')->find('order')
				->where([$this->EducationGrades->aliasField('education_programme_id') => $programmeId])
				->all();
			$attr['data'] = $gradeData;

			if (count($gradeData)==0) {
				$this->Alert->warning('InstitutionSiteProgrammes.noEducationGrades');
			}
		}
		return $attr;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/

	/**
	 * Used by InstitutionSiteGradesTable && InstitutionSiteFeesTable
	 * @param  integer $academicPeriodId [description]
	 * @param  array   $conditions       [description]
	 * @return [type]                    [description]
	 */
	public function getConditionsByAcademicPeriodId($academicPeriodId=0, $conditions=[]) {
		$modelConditions = [];
		if($academicPeriodId > 0) {
			$AcademicPeriod = $this->AcademicPeriods;
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

		$query = $this->AcademicPeriods->find('list')
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
