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

	public function afterAction(Event $event) {
		$this->ControllerAction->field('institution_site_programme_id', ['type' => 'hidden']);
		$this->ControllerAction->field('level');
		$this->ControllerAction->field('programme');
		$this->ControllerAction->field('education_grade_id');

		if ($this->action == 'add') {
			$this->ControllerAction->setFieldOrder([
				'level', 'programme', 'start_date', 'end_date', 'education_grade_id'
			]);
		}
	}

	public function indexAfterAction(Event $event, $data) {
		// $this->ControllerAction
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
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

	public function onGetLevel(Event $event, Entity $entity) {
		$level = $entity->education_grade->education_programme->education_cycle->education_level->name;
		$cycle = $entity->education_grade->education_programme->education_cycle->name;
		return $level . ' - ' . $cycle;
	}

	public function onGetProgramme(Event $event, Entity $entity) {
		return $entity->education_grade->education_programme->name;
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
		}
		return $attr;
	}


}
