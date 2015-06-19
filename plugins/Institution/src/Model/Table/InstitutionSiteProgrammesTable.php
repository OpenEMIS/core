<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class InstitutionSiteProgrammesTable extends AppTable {
	public $EducationLevels;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		$this->hasMany('InstitutionSiteGrades',	['className' => 'Institution.InstitutionSiteGrades']);


		/**
		 * Short cuts to initialised models set in relations.
		 * By using initialised models set in relations, the relation's className is set at a single place.
		 * In add operations, these models attributes are empty by default.
		 */
		$this->EducationLevels = $this->EducationProgrammes->EducationCycles->EducationLevels;
		$this->EducationGrades = $this->EducationProgrammes->EducationGrades;
		$this->AcademicPeriods = $this->Institutions->InstitutionSiteShifts->AcademicPeriods;
	}



/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;

		$this->ControllerAction->addField('education_level', ['type' => 'string']);

		$this->fields['education_programme_id']['order'] = 1;
		$this->fields['education_level']['order'] = 2;
	}

	public function indexAfterAction(Event $event, $data) {
		// pr($data->toArray());
		return $data;
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;

		$this->ControllerAction->addField('education_level', ['type' => 'string']);

		$this->fields['education_programme_id']['order'] = 1;
		$this->fields['education_level']['order'] = 2;
	}



/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditBeforeAction($event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['education_programme_id']['type'] = 'select';
		$this->fields['education_programme_id']['onChangeReload'] = true;

		$this->ControllerAction->addField('education_level', ['type' => 'select', 'onChangeReload' => true]);

		$levelOptions = $this->EducationLevels
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('withSystem')
			->toArray();
			
		$this->fields['education_level']['options'] = $levelOptions;

		// TODO-jeff: write validation logic to check for loaded $levelOptions
		if (count($levelOptions)==0) {
			$this->Alert->warning('InstitutionSiteProgrammes.noEducationLevels');
		}
		$levelId = key($levelOptions);
		if ($this->request->data($this->aliasField('education_level'))) {
			$levelId = $this->request->data($this->aliasField('education_level'));
			if (!array_key_exists($levelId, $levelOptions)) {
				$levelId = key($levelOptions);
			}
		}

		$programmeOptions = $this->EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('withCycle')
			->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $levelId])
			->toArray();

		$this->fields['education_programme_id']['options'] = $programmeOptions;

		// start Education Grade field
		$this->ControllerAction->addField('education_grade', [
			'type' => 'element', 
			'order' => 5,
			'element' => 'Institution.Programmes/grades'
		]);

		if (count($programmeOptions)==0) {
			$this->Alert->warning('InstitutionSiteProgrammes.noEducationProgrammes');
		}
		$programmeId = key($programmeOptions);
		if ($this->request->data($this->aliasField('education_programme_id'))) {
			$programmeId = $this->request->data($this->aliasField('education_programme_id'));
			if (!array_key_exists($programmeId, $programmeOptions)) {
				$programmeId = key($programmeOptions);
			}
		}
		// TODO-jeff: need to check if programme id is empty

		$gradeData = $this->EducationGrades->find()
			->find('visible')->find('order')
			->where([$this->EducationGrades->aliasField('education_programme_id') => $programmeId])
			->all();

		$this->fields['education_grade']['data'] = $gradeData;
		if (count($gradeData)==0) {
			$this->Alert->warning('InstitutionSiteProgrammes.noEducationGrades');
		}
		// end Education Grade field
	}



/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeQuery(Event $event, Query $query, $contain) {
		$contain[] = 'EducationProgrammes';
		$contain[] = 'InstitutionSiteGrades';
		return compact('query', 'contain');
	}

	public function editAfterAction($event, $entity) {
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
				->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $levelId])
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

	public function editBeforePatch($event, $entity, $data, $options) {
		$data = $this->patchDates($data);
		$this->InstitutionSiteGrades->updateAll(['status' => 0], ['institution_site_programme_id' => $entity->id]);
		foreach ($data[$this->alias()]['institution_site_grades'] as $key => $row) {
			if (isset($row['education_grade_id'])) {
				$data[$this->alias()]['institution_site_grades'][$key]['status'] = 1;
				$data[$this->alias()]['institution_site_grades'][$key]['institution_site_id'] = $data[$this->alias()]['institution_site_id'];
			} else {
				unset($data[$this->alias()]['institution_site_grades'][$key]);
			}
		}
		return compact('entity', 'data', 'options');
	}




/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforePatch($event, $entity, $data, $options) {
		$data = $this->patchDates($data);
		foreach($data[$this->alias()]['institution_site_grades'] as $key => $row) {
			if (isset($row['education_grade_id'])) {
				$data[$this->alias()]['institution_site_grades'][$key]['status'] = 1;
				$data[$this->alias()]['institution_site_grades'][$key]['institution_site_id'] = $data[$this->alias()]['institution_site_id'];
			} else {
				unset($data[$this->alias()]['institution_site_grades'][$key]);
			}
		}
		return compact('entity', 'data', 'options');
	}

	public function addAfterAction($event, $entity) {
		/**
		 * @todo - To be done in a dynamic way so that the format is consistent throughout the whole system.
		 */
		$this->fields['start_date']['value'] = date('d-m-Y');
	}



/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/

	private function patchDates($data) {
		$modelData = $data[$this->alias()];
		if (array_key_exists('start_date', $modelData)) {
			$date = $modelData['start_date'];
			if ($date!='') {
				$data[$this->alias()]['start_year'] = date('Y', strtotime($date));
			}
		}
		if (array_key_exists('end_date', $modelData)) {
			$date = $modelData['end_date'];
			if ($date!='') {
				$data[$this->alias()]['end_year'] = date('Y', strtotime($date));
			}
		}
		return $data;

	}

	/**
	 * Used by InstitutionSiteGradesTable
	 * @param  integer $academicPeriodId [description]
	 * @param  array   $conditions       [description]
	 * @return [type]                    [description]
	 */
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
	
	/**
	 * Used by InstitutionSiteSectionsTable & InstitutionSiteClassesTable
	 * @param  integer $institutionsId           [description]
	 * @param  integer $selectedAcademicPeriodId [description]
	 * @param  array   $conditions               [description]
	 * @return [type]                            [description]
	 */
	public function getAcademicPeriodOptions($conditions=[]) {

		$query = $this->find('all')
					->select(['start_date', 'end_date'])
					->where($conditions)
					;
		// pr($query->__toString());die;
		$result = $query->toArray();
		$startDateObject = null;
		foreach ($result as $key=>$value) {
			$startDateObject = $this->getLowerDate($startDateObject, $value->start_date);
		}
		if (is_object($startDateObject)) {
			$startDate = $startDateObject->toDateString();
		}

		$endDateObject = null;
		foreach ($result as $key=>$value) {
			$endDateObject = $this->getHigherDate($endDateObject, $value->end_date);
		}
		if (is_object($endDateObject)) {
			$endDate = $endDateObject->toDateString();
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
		return $query->toArray();
	}

	private function getLowerDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		return (($a->toUnixString() <= $b->toUnixString()) ? $a : $b);
	}

	private function getHigherDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		return (($a->toUnixString() >= $b->toUnixString()) ? $a : $b);
	}

}
