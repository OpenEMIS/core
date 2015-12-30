<?php 
namespace Student\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use User\Model\Entity\User;

class StudentBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->belongsToMany('Guardians', [
			'className' => 'Student.Guardians',
			'foreignKey' => 'student_user_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Student.StudentGuardians',
			'dependent' => true
		]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.index.beforePaginate' => 'indexBeforePaginate',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
			'ControllerAction.Model.afterAction' => 'afterAction',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['Users', 'Institutions', 'StudentStatuses']);

		$search = $this->_table->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query = $this->_table->addSearchConditions($query, ['searchTerm' => $search]);
			
			if ($request->params['controller'] == 'Institutions') {
				$session = $request->session();
				$institutionId = $session->read('Institution.Institutions.id');
				$query->andWhere(['Students.institution_id' => $institutionId]);
			}
		}

		// this part filters the list by institutions/areas granted to the group
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->_table->AccessControl->getInstitutionsByUser();
			$query->where(['Students.institution_id IN ' => $institutionIds]);
		}
	}

	public function onGetName(Event $event, Entity $entity) {
		return $entity->user->name;
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		if (!empty ($entity->user->openemis_no)) {
			return $entity->user->openemis_no;
		}
	}

	public function onGetDefaultIdentityType(Event $event, Entity $entity) {
		return $entity->user->default_identity_type;
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		if(!empty($entity->institution->name)) {
			return $entity->institution->name;
		}
	}

	public function onGetStudentStatus(Event $event, Entity $entity) {
		$name = '';
		if ($entity instanceof User) {
			$session = $event->subject()->request->session();
			$institutionId = $session->read('Institutions.id');

			$InstitutionStudents = TableRegistry::get('Institution.Students');
			$obj = $InstitutionStudents->find()
				->contain('StudentStatuses')
				->where([
					$InstitutionStudents->aliasField('institution_id') => $institutionId,
					$InstitutionStudents->aliasField('security_user_id') => $entity->id
				])
				->first();
			$name = $obj->student_status->name;
		} else { // from Institutions -> Students
			if (!empty($entity->student_status)) {
				$name = $entity->student_status->name;
			}
		}
		return $name;
	}

	public function addBeforeAction(Event $event) {
		$name = $this->_table->alias();
		$this->_table->ControllerAction->addField('institution_students.0.institution_id', [
			'type' => 'hidden', 
			'value' => 0
		]);
		$this->_table->fields['openemis_no']['attr']['value'] = $this->_table->getUniqueOpenemisId(['model'=>Inflector::singularize('Student')]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Institution.Students';

		$this->_table->ControllerAction->field('name');
		$this->_table->ControllerAction->field('default_identity_type');
		$this->_table->ControllerAction->field('institution');
		$this->_table->ControllerAction->field('student_status');

		$this->_table->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'institution', 'student_status']);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// this method should rightfully be in institution userbehavior - need to move this in an issue after guardian module is in prod
		if (array_key_exists('new', $this->_table->request->query)) {
			$alias = $this->_table->alias();
			$session = $this->_table->Session;
			if ($session->check($alias.'.add.'.$this->_table->request->query['new'])) {
				$institutionStudentData = $session->read($alias.'.add.'.$this->_table->request->query['new']);
				if (array_key_exists($alias, $data)) {
					if (!array_key_exists('institution_students', $data[$alias])) {
						$data[$alias]['institution_students'] = [];
						$data[$alias]['institution_students'][0] = [];
					}
					$data[$alias]['institution_students'][0]['institution_id'] = $institutionStudentData[$alias]['institution_students'][0]['institution_id'];

					$data[$alias]['institution_students'][0]['student_status_id'] = $institutionStudentData[$alias]['institution_students'][0]['student_status_id'];
					$data[$alias]['institution_students'][0]['education_programme_id'] = $institutionStudentData[$alias]['institution_students'][0]['education_programme_id'];

					// start and end (date and year) handling
					$data[$alias]['institution_students'][0]['start_date'] = $institutionStudentData[$alias]['institution_students'][0]['start_date'];
					$data[$alias]['institution_students'][0]['end_date'] = $institutionStudentData[$alias]['institution_students'][0]['end_date'];
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['Students'];

		// Jeff: workaround, needs to redo this logic
		$alias = $this->_table->alias();
		if (isset($data[$alias]['institution_students'])) {
			$students = $data[$alias]['institution_students'];
			if (!empty($students) && isset($students[0]) && isset($students[0]['institution_id'])) {
				if ($students[0]['institution_id'] == 0) {
					$data[$alias]['institution_students'][0]['start_date'] = date('Y-m-d');
					$data[$alias]['institution_students'][0]['end_date'] = date('Y-m-d', time()+86400);
					$data[$alias]['institution_students'][0]['education_programme_id'] = 0;
					$data[$alias]['institution_students'][0]['student_status_id'] = 0;
				}
			}
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function afterSave(Event $event, Entity $entity, $options) {
		// this method should rightfully be in institution userbehavior - need to move this in an issue after guardian module is in prod
		if ($entity->isNew()) {
			$alias = $this->_table->alias();
			// for attaching student to section
			if (array_key_exists('new', $this->_table->request->query)) {
				$sessionVar = $alias.'.add.'.$this->_table->request->query['new'];
				if ($this->_table->Session->check($sessionVar)) {
					$institutionStudentData = $this->_table->Session->read($sessionVar);
					$sectionData = [];
					$sectionData['student_id'] = $entity->id;
					$sectionData['education_grade_id'] = $institutionStudentData[$alias]['institution_students'][0]['education_grade'];
					$sectionData['institution_section_id'] = $institutionStudentData[$alias]['institution_students'][0]['section'];

					$InstitutionSectionStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
					$InstitutionSectionStudents->autoInsertSectionStudent($sectionData);	
				}
			}
		}
	}
}
