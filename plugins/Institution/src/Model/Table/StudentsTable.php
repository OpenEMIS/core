<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use User\Model\Table\UsersTable as BaseTable;

class StudentsTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Student.Student');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);

		// $this->addBehavior('Institution.Role', ['associatedModel' => $this->InstitutionSiteStudents]);
		// new aftersave
		// existing aftersave update instaed of new one
		// deletion onBeforeDelete new insert or update 
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		parent::indexBeforePaginate($event, $request, $query, $options);
		if ($this->Session->check('Institutions.id')) {
			$institutionId = $this->Session->read('Institutions.id');
			
			$query->where(['InstitutionSiteStudents.institution_site_id' => $institutionId]);
		}
	}


	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		parent::indexBeforeAction($event, $query, $settings);
		$this->ControllerAction->field('programme_section', []);
		$this->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'programme_section', 'student_status']);
	}

	public function addAfterAction(Event $event) {
		if (array_key_exists('new', $this->request->query)) {

		} else {
			$session = $this->request->session();
			$institutionSiteId = $session->read('Institutions.id');
			$associationString = $this->alias().'.'.$this->InstitutionSiteStudents->table().'.0.';
			$this->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionSiteId, 'fieldName' => $associationString.'institution_site_id']);			

			$this->ControllerAction->field('academic_period', ['fieldName' => $associationString.'academic_period']);
			$this->ControllerAction->field('education_programme_id', ['fieldName' => $associationString.'education_programme_id']);
			$this->ControllerAction->field('education_grade', ['fieldName' => $associationString.'education_grade']);
			$this->ControllerAction->field('section', ['fieldName' => $associationString.'section']);
			$this->ControllerAction->field('student_status_id', ['fieldName' => $associationString.'student_status_id']);
			$this->ControllerAction->field('start_date', ['type' => 'date', 'fieldName' => $associationString.'start_date']);
			$this->ControllerAction->field('end_date', [
				'type' => 'date', 
				'fieldName' => $associationString.'end_date',
				'date_options' => ['startDate' => '+1d']
			]);
			// $this->fields['end_date']['value'] = '09-07-2015';
			// $this->fields['end_date’][‘date_options']['start_date'] = '+1d';
			$this->ControllerAction->field('search',['type' => 'autocomplete', 
														     'placeholder' => 'openEMIS ID or Name',
														     'url' => '/Institutions/Students/autoCompleteUserList',
														     'length' => 3 ]);

			$this->ControllerAction->setFieldOrder([
					'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
				, 'search'
				]);	
		}
	}

	public function autoCompleteUserList() {
		if ($this->request->is('ajax')) {
			$this->layout = 'ajax';
			$this->autoRender = false;
			$this->ControllerAction->autoRender = false;
			$term = $this->ControllerAction->request->query('term');
			$search = "";
			if(isset($term)){
				$search = '%'.$term.'%';
			}

			$conditions = array(
				'OR' => array(
					'Users.openemis_no LIKE' => $search,
					'Users.first_name LIKE' => $search,
					'Users.middle_name LIKE' => $search,
					'Users.third_name LIKE' => $search,
					'Users.last_name LIKE' => $search
				)
			);

			$list = $this->InstitutionSiteStudents
					->find('all')
					->contain(['Users'])
					->where($conditions);

			$session = $this->request->session();
			if ($session->check($this->controller->name.'.'.$this->alias)) {
				$filterData = $session->read($this->controller->name.'.'.$this->alias);
				// need to form an exclude list
				$excludeQuery = $this->InstitutionSiteStudents
					->find()
					->select(['security_user_id'])
					->where(
						[
							'AND' => $filterData
						]
					)
					->group('security_user_id')
				;
				$excludeList = [];
				foreach ($excludeQuery as $key => $value) {
					$excludeList[] = $value->security_user_id;
				}

				if(!empty($excludeList)) {
					$list->where([$this->InstitutionSiteStudents->aliasField('security_user_id').' NOT IN' => $excludeList]);
				}
			}


			$list	
				->group('Users.id')
				->order(['Users.first_name asc']);

			$data = array();
			foreach ($list as $obj) {

				//pr($obj->user);

				$data[] = array(
					'label' => $obj->user->nameWithId,
					'value' =>  $obj->user->id
				);
			}
			
			echo json_encode($data);
			die;
		}
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionSiteId
		);

		$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'changePeriod';
		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.academicPeriod');
		}

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		$this->academicPeriodId = null;
		if (array_key_exists('academic_period', $this->fields)) {
			if (array_key_exists('options', $this->fields['academic_period'])) {
				$this->academicPeriodId = key($this->fields['academic_period']['options']);
				if (array_key_exists($this->alias(), $this->request->data)) {
					if (array_key_exists('academic_period', $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0])) {
						if ($this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['academic_period']) {
							$this->academicPeriodId = $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['academic_period'];
						}
					}
				}

			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'changeEducationProgrammeId';
		$attr['options'] = [];
		if (isset($this->academicPeriodId)) {
			$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
			$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($institutionSiteId, $this->academicPeriodId);
			if (empty($attr['options'])) {
				$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.educationProgrammeId');
			}
		}

		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		if (array_key_exists('education_programme_id', $this->fields)) {
			if (array_key_exists('options', $this->fields['education_programme_id'])) {
				$educationProgrammeId = key($this->fields['education_programme_id']['options']);
				if (array_key_exists($this->alias(), $this->request->data)) {
					if (array_key_exists('education_programme_id', $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0])) {
						if ($this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_programme_id']) {
							$educationProgrammeId = $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_programme_id'];
						}
					}
				}
			}
		}

		// this is used for staffTable autocomplete - for filtering of students that are (in institution and of same education programme)
		$session = $this->request->session();
		$session->delete($this->controller->name.'.'.$this->alias);
		if (isset($educationProgrammeId)) {
			$institutionSiteId = $session->read('Institutions.id');
			$session->write($this->controller->name.'.'.$this->alias.'.'.'institution_site_id', $institutionSiteId);
			$session->write($this->controller->name.'.'.$this->alias.'.'.'education_programme_id', $educationProgrammeId);
		}

		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'changeEducationGrade';
		$attr['options'] = [];
		if (isset($educationProgrammeId)) {
			$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$attr['options'] = $InstitutionSiteGrades->getGradeOptions($institutionSiteId, $this->academicPeriodId, $educationProgrammeId);
		}

		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.institutionSiteGrades');
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		if (array_key_exists('education_grade', $this->fields)) {
			if (array_key_exists('options', $this->fields['education_grade'])) {
				$this->education_grade = key($this->fields['education_grade']['options']);
				if (array_key_exists($this->alias(), $this->request->data)) {
					if (array_key_exists('education_grade', $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0])) {
						if ($this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_grade']) {
							$this->education_grade = $this->request->data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_grade'];
						}
					}
				}
			}
		}
		$attr['type'] = 'select';
		$attr['options'] = [];
		if (isset($this->education_grade)) {
			$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
			$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $institutionSiteId, $this->education_grade);
		}

		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.sections');
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->InstitutionSiteStudents->StudentStatuses->getList();

		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStudents.studentStatusId');
		}

		return $attr;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('remove', $buttons)) {
			if (array_key_exists('removeStraightAway', $buttons['remove']) && $buttons['remove']['removeStraightAway']) {
				// pr($entity);cthreeone
				if (isset($entity->institution_site_students)) {
					if (array_key_exists(0, $entity->institution_site_students)) {
						$buttons['remove']['attr']['field-value'] = $entity->institution_site_students[0]->id;
					}
				}
			}
		}
		
		return $buttons;
	}

	public function addOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['InstitutionSiteStudents' => ['validate' => false]];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->addOnReload($event, $entity, $data, $options);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_programme_id']);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_grade']);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['section']);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
	}

	public function addOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->addOnReload($event, $entity, $data, $options);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['education_grade']);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['section']);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
	}

	public function addOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->addOnReload($event, $entity, $data, $options);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
		unset($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]['section']);
		// pr($data[$this->alias()][$this->InstitutionSiteStudents->table()][0]);
	}
}